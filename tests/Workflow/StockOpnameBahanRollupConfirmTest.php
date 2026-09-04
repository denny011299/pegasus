<?php

namespace Tests\Workflow;

use App\Models\StockOpnameBahan;
use App\Models\StockOpnameBahanLine;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use App\Support\StockOpname\OpnameLifecycle;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Kembaran persis tests/Workflow/StockOpnameRollupConfirmTest.php (Produk), untuk Stock Opname
 * BAHAN (Supplies) -- porting 2026-09-06 atas permintaan user setelah fitur popup konfirmasi
 * gulung selesai dibangun di sisi Produk. Lihat file itu untuk seluruh sejarah/keputusan
 * (2026-09-04 s.d. 2026-09-06); di sini cuma disebut bedanya:
 *
 *  - Identitasnya `supplies_id`, bukan product_id + product_variant_id.
 *  - Payload JS mengirim satuannya di bawah key `sp_units`, bukan `units`.
 *  - Endpoint: /previewStockOpnameRollupBahan, /insertStockOpnameBahan, /submitStockOpnameBahan.
 *  - Saklar popup (show_popup) memakai const yang SAMA dengan Produk --
 *    App\Support\StockOpname\OpnameLifecycle::ROLLUP_PROJECTION_ENABLED, BUKAN const terpisah.
 */
class StockOpnameBahanRollupConfirmTest extends TestCase
{
    use ActingAsStaff;

    private array $units = [];

    protected function setUp(): void
    {
        parent::setUp();
        $rows = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $rows->count(), 'fixture butuh minimal 2 satuan aktif');
        $this->units = ['dos' => $rows[0], 'pcs' => $rows[1]];
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    /** Bahan + ladder (1 DOS = $ratio pcs) + stok awal di kedua satuan, gudang utama (id 1). */
    private function makeLadderedSupplies(int $dosStock, int $pcsStock, int $ratio = 12, ?int $warehouseId = 1): Supplies
    {
        $supplies = new Supplies();
        $supplies->supplies_name = 'ROLLUP CONFIRM BAHAN TEST '.uniqid();
        $supplies->supplies_unit = json_encode([$this->units['dos']->unit_id, $this->units['pcs']->unit_id]);
        $supplies->supplies_default_unit = $this->units['dos']->unit_id;
        $supplies->status = 1;
        $supplies->save();

        foreach ([['dos', $dosStock], ['pcs', $pcsStock]] as [$key, $qty]) {
            $s = new SuppliesStock();
            $s->supplies_id = $supplies->supplies_id;
            $s->unit_id = $this->units[$key]->unit_id;
            $s->warehouse_id = $warehouseId;
            $s->ss_stock = $qty;
            $s->status = 1;
            $s->save();
        }

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = $this->units['dos']->unit_id; // besar
        $relation->sr_value_1 = 1;
        $relation->su_id_2 = $this->units['pcs']->unit_id; // kecil
        $relation->sr_value_2 = $ratio;
        $relation->status = 1;
        $relation->save();

        return $supplies;
    }

    /** Payload item[]: cuma Dos yang diisi ($dosQty), Piece dikirim sebagai unit_id + real_qty:null. */
    private function dosOnlyItemPayload(Supplies $s, int $dosQty): array
    {
        return [[
            'supplies_id' => $s->supplies_id,
            'sp_units' => [
                ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => $dosQty],
                ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
            ],
        ]];
    }

    private function preview(array $items, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/previewStockOpnameRollupBahan', array_merge([
            'item' => json_encode($items),
        ], $extra));
    }

    private function insertOpname(array $items, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/insertStockOpnameBahan', array_merge([
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => 'Rollup confirm test bahan',
            'is_draft' => 0,
            'item' => json_encode($items),
        ], $extra));
    }

    public function test_preview_detects_a_rollup_opportunity_without_writing_anything(): void
    {
        $this->actingAsSuperAdminStaff();

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104);
        $stobCountBefore = StockOpnameBahan::count();
        $lineCountBefore = StockOpnameBahanLine::count();

        $response = $this->preview($this->dosOnlyItemPayload($s, 93));
        $response->assertStatus(200);

        $candidates = $response->json('rollup_candidates');
        $this->assertCount(1, $candidates);
        $this->assertSame($s->supplies_id, $candidates[0]['supplies_id']);
        // Rincian per satuan, diurutkan BESAR -> KECIL (DOS di kiri).
        $this->assertSame($this->units['dos']->unit_id, $candidates[0]['changes'][0]['unit_id']);
        $dosChange = $candidates[0]['changes'][0];
        $this->assertSame(93, $dosChange['before']);
        $this->assertSame(101, $dosChange['after']);
        $this->assertSame($this->units['dos']->unit_short_name, $dosChange['unit_short_name']);
        $pcsChange = $candidates[0]['changes'][1];
        $this->assertSame($this->units['pcs']->unit_id, $pcsChange['unit_id']);
        $this->assertSame(104, $pcsChange['before']);
        $this->assertSame(8, $pcsChange['after']);

        $this->assertSame($stobCountBefore, StockOpnameBahan::count(), 'preview tidak boleh membuat dokumen apa pun');
        $this->assertSame($lineCountBefore, StockOpnameBahanLine::count(), 'preview tidak boleh membuat baris apa pun');
    }

    /** Bahan yang SAMA SEKALI tidak disentuh staf TETAP jadi kandidat kalau stoknya tidak kanonik. */
    public function test_an_entirely_untouched_supplies_is_still_a_rollup_candidate_when_its_own_stock_is_noncanonical(): void
    {
        $this->actingAsSuperAdminStaff();

        $touched = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104);
        $untouched = $this->makeLadderedSupplies(dosStock: 5, pcsStock: 30, ratio: 12);

        $items = array_merge(
            $this->dosOnlyItemPayload($touched, 93),
            [[
                'supplies_id' => $untouched->supplies_id,
                'sp_units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                ],
            ]],
        );

        $response = $this->preview($items);
        $response->assertStatus(200);

        $candidates = collect($response->json('rollup_candidates'))->keyBy('supplies_id');
        $this->assertCount(2, $candidates, 'bahan yang tidak disentuh staf tetap harus jadi kandidat kalau stoknya tidak kanonik');

        $untouchedCandidate = $candidates[$untouched->supplies_id];
        $changes = collect($untouchedCandidate['changes'])->keyBy('unit_id');
        $this->assertSame(5, $changes[$this->units['dos']->unit_id]['before']);
        $this->assertSame(7, $changes[$this->units['dos']->unit_id]['after']);
        $this->assertSame(30, $changes[$this->units['pcs']->unit_id]['before']);
        $this->assertSame(6, $changes[$this->units['pcs']->unit_id]['after']);
    }

    public function test_batal_never_touches_the_database_at_all(): void
    {
        $this->actingAsSuperAdminStaff();

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104);
        $items = $this->dosOnlyItemPayload($s, 93);
        $stobCountBefore = StockOpnameBahan::count();
        $lineCountBefore = StockOpnameBahanLine::count();

        $this->preview($items)->assertStatus(200);

        $this->assertSame($stobCountBefore, StockOpnameBahan::count());
        $this->assertSame($lineCountBefore, StockOpnameBahanLine::count());
        $this->assertSame(90, (int) SuppliesStock::where('supplies_id', $s->supplies_id)->where('unit_id', $this->units['dos']->unit_id)->value('ss_stock'));
        $this->assertSame(104, (int) SuppliesStock::where('supplies_id', $s->supplies_id)->where('unit_id', $this->units['pcs']->unit_id)->value('ss_stock'));
    }

    public function test_insert_with_explicit_skip_decision_applies_no_rollup_at_all(): void
    {
        $this->actingAsSuperAdminStaff();

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104);
        $items = $this->dosOnlyItemPayload($s, 93);

        $response = $this->insertOpname($items, ['rollup_decision' => 'skip']);
        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);
        $stobId = (int) $response->json('stob_id');

        $lines = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');
        $this->assertSame(93, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty, 'Dos tetap 93, tidak berubah');
        $this->assertNull($lines[$this->units['pcs']->unit_id]->sobl_counted_qty, 'Piece TETAP null -- tidak digulung');

        $stob = StockOpnameBahan::find($stobId);
        $this->assertNotNull($stob->stob_staff_name, 'publish() harus tetap jalan untuk dokumen non-draft');

        $this->post('/accStockOpnameBahan', ['stob_id' => $stobId, 'item' => json_encode([['dummy' => 1]])])->assertOk();
        $this->assertSame(93, (int) SuppliesStock::where('supplies_id', $s->supplies_id)->where('unit_id', $this->units['dos']->unit_id)->value('ss_stock'));
        $this->assertSame(104, (int) SuppliesStock::where('supplies_id', $s->supplies_id)->where('unit_id', $this->units['pcs']->unit_id)->value('ss_stock'));
    }

    public function test_filling_only_the_smallest_unit_now_also_requires_confirmation(): void
    {
        $this->actingAsSuperAdminStaff();

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 0, ratio: 12);
        $items = [[
            'supplies_id' => $s->supplies_id,
            'sp_units' => [
                ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => 1000],
            ],
        ]];

        $preview = $this->preview($items);
        $preview->assertStatus(200);
        $candidates = $preview->json('rollup_candidates');
        $this->assertCount(1, $candidates, 'mengisi satuan terkecil sendirian sekarang HARUS terdeteksi juga');
        $changes = collect($candidates[0]['changes'])->keyBy('unit_id');
        $this->assertSame(90, $changes[$this->units['dos']->unit_id]['before']);
        $this->assertSame(173, $changes[$this->units['dos']->unit_id]['after']);
        $this->assertSame(1000, $changes[$this->units['pcs']->unit_id]['before']);
        $this->assertSame(4, $changes[$this->units['pcs']->unit_id]['after']);

        $noConfirm = $this->insertOpname($items, ['rollup_decision' => 'skip']);
        $noConfirmId = (int) $noConfirm->json('stob_id');
        $lines = StockOpnameBahanLine::getLines($noConfirmId)->keyBy('unit_id');
        $this->assertNull($lines[$this->units['dos']->unit_id]->sobl_counted_qty, 'Dos TETAP null tanpa konfirmasi -- tidak ada gulung otomatis lagi');
        $this->assertSame(1000, (int) $lines[$this->units['pcs']->unit_id]->sobl_counted_qty, 'Piece tersimpan 1000 apa adanya');

        $confirmed = $this->insertOpname($items, ['rollup_decision' => 'full']);
        $confirmedId = (int) $confirmed->json('stob_id');
        $lines2 = StockOpnameBahanLine::getLines($confirmedId)->keyBy('unit_id');
        $this->assertSame(173, (int) $lines2[$this->units['dos']->unit_id]->sobl_counted_qty);
        $this->assertSame(4, (int) $lines2[$this->units['pcs']->unit_id]->sobl_counted_qty);
    }

    public function test_lanjut_creates_the_document_in_one_shot_with_full_rollup(): void
    {
        $this->actingAsSuperAdminStaff();

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104, ratio: 12);
        $items = $this->dosOnlyItemPayload($s, 93);
        $stobCountBefore = StockOpnameBahan::count();

        $response = $this->insertOpname($items, ['rollup_decision' => 'full']);
        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);
        $stobId = (int) $response->json('stob_id');
        $this->assertSame($stobCountBefore + 1, StockOpnameBahan::count(), 'cuma SATU dokumen yang tercipta, bukan dua');

        $lines = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');
        $this->assertSame(101, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty, 'Dos ikut menyerap kelebihan Piece');
        $this->assertSame(8, (int) $lines[$this->units['pcs']->unit_id]->sobl_counted_qty, 'Piece jadi sisa kanonik, bukan NULL lagi');

        $this->post('/accStockOpnameBahan', ['stob_id' => $stobId, 'item' => json_encode([['dummy' => 1]])])->assertOk();
        $this->assertSame(101, (int) SuppliesStock::where('supplies_id', $s->supplies_id)->where('unit_id', $this->units['dos']->unit_id)->value('ss_stock'));
        $this->assertSame(8, (int) SuppliesStock::where('supplies_id', $s->supplies_id)->where('unit_id', $this->units['pcs']->unit_id)->value('ss_stock'));
    }

    /** Kembaran persis test PDF-zero-highlight Produk -- lihat StockOpnameRollupConfirmTest untuk bug lengkapnya. */
    public function test_lanjut_never_overwrites_an_untouched_already_canonical_supplies_with_zero(): void
    {
        $this->actingAsSuperAdminStaff();

        $touched = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104, ratio: 12);
        $untouchedCanonical = $this->makeLadderedSupplies(dosStock: 0, pcsStock: 0, ratio: 12);

        $items = array_merge(
            $this->dosOnlyItemPayload($touched, 93),
            [[
                'supplies_id' => $untouchedCanonical->supplies_id,
                'sp_units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                ],
            ]],
        );

        $preview = $this->preview($items);
        $candidateIds = collect($preview->json('rollup_candidates'))->pluck('supplies_id');
        $this->assertNotContains($untouchedCanonical->supplies_id, $candidateIds);

        $response = $this->insertOpname($items, ['rollup_decision' => 'full']);
        $response->assertStatus(200);
        $stobId = (int) $response->json('stob_id');

        $untouchedLines = StockOpnameBahanLine::where('stob_id', $stobId)
            ->where('supplies_id', $untouchedCanonical->supplies_id)
            ->get();
        foreach ($untouchedLines as $line) {
            $this->assertNull($line->sobl_counted_qty, 'bahan yang tidak disentuh & sudah kanonik harus TETAP null, bukan 0');
        }

        $stob = StockOpnameBahan::find($stobId);
        $row = collect((new \App\Support\StockOpname\BahanOpnameLineReader())->read($stob))
            ->firstWhere('supplies_id', $untouchedCanonical->supplies_id);
        $this->assertNotNull($row);
        $this->assertFalse($row['counted'], 'baris ini tidak boleh dianggap "pernah dihitung"');
        $this->assertNull($row['highlight'], 'baris ini tidak boleh ter-highlight sama sekali');
    }

    public function test_submit_stock_opname_bahan_still_accepts_an_explicit_rollup_decision(): void
    {
        $this->actingAsSuperAdminStaff();

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104);
        $items = $this->dosOnlyItemPayload($s, 93);

        $draft = $this->insertOpname($items, ['is_draft' => 1]);
        $draft->assertStatus(200);
        $stobId = (int) $draft->json('stob_id');

        $this->post('/submitStockOpnameBahan', ['stob_id' => $stobId, 'rollup_decision' => 'full'])->assertStatus(200);
        $this->assertFalse((bool) StockOpnameBahan::find($stobId)->refresh()->is_draft);

        $lines = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');
        $this->assertSame(101, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty);
        $this->assertSame(8, (int) $lines[$this->units['pcs']->unit_id]->sobl_counted_qty);
    }

    /** Kembaran persis test konsistensi draft-sparse Produk -- lihat itu untuk bug lengkapnya. */
    public function test_preview_result_is_identical_before_and_after_a_sparse_draft_save_when_the_same_full_payload_is_sent(): void
    {
        $this->actingAsSuperAdminStaff();

        $touched = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104);
        $untouchedA = $this->makeLadderedSupplies(dosStock: 5, pcsStock: 30, ratio: 12);
        $untouchedB = $this->makeLadderedSupplies(dosStock: 3, pcsStock: 50, ratio: 12);

        $fullCatalogPayload = fn () => array_merge(
            $this->dosOnlyItemPayload($touched, 93),
            [[
                'supplies_id' => $untouchedA->supplies_id,
                'sp_units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                ],
            ], [
                'supplies_id' => $untouchedB->supplies_id,
                'sp_units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                ],
            ]],
        );

        $first = $this->preview($fullCatalogPayload());
        $first->assertStatus(200);
        $firstIds = collect($first->json('rollup_candidates'))->pluck('supplies_id')->sort()->values();
        $this->assertCount(3, $firstIds, 'ketiga bahan (1 diisi + 2 tidak kanonik) harus jadi kandidat');

        $draft = $this->insertOpname($this->dosOnlyItemPayload($touched, 93), ['is_draft' => 1]);
        $draft->assertStatus(200);
        $stobId = (int) $draft->json('stob_id');
        $draftSuppliesIds = StockOpnameBahanLine::getLines($stobId)->pluck('supplies_id')->unique();
        $this->assertCount(1, $draftSuppliesIds, 'draft cuma menyimpan bahan yang diisi, bukan seluruh katalog');

        $second = $this->preview($fullCatalogPayload());
        $second->assertStatus(200);
        $secondIds = collect($second->json('rollup_candidates'))->pluck('supplies_id')->sort()->values();

        $this->assertSame($firstIds->all(), $secondIds->all(), 'proyeksi rollup harus sama persis sebelum dan sesudah draft disimpan');
    }

    public function test_retail_warehouse_never_offers_rollup_confirmation(): void
    {
        $this->actingAsSuperAdminStaff();

        $type = WarehouseType::where('is_main_warehouse', '!=', 1)->first();
        $warehouse = Warehouse::where('warehouse_type_id', $type->id ?? 0)->first();
        if (! $type || ! $warehouse) {
            $this->markTestSkipped('fixture butuh gudang eceran (warehouse_types.is_main_warehouse != 1)');
        }

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104, warehouseId: $warehouse->id);

        $response = $this->preview($this->dosOnlyItemPayload($s, 93), ['warehouse_id' => $warehouse->id]);
        $response->assertStatus(200);
        $this->assertSame([], $response->json('rollup_candidates'), 'gudang eceran tidak pernah punya peluang gulung');
    }

    /** Saklar popup Bahan memakai const yang SAMA dengan Produk -- satu saklar untuk keduanya. */
    public function test_preview_reports_the_same_show_popup_flag_as_produk(): void
    {
        $this->actingAsSuperAdminStaff();

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104);
        $response = $this->preview($this->dosOnlyItemPayload($s, 93));
        $response->assertStatus(200);

        $this->assertNotEmpty($response->json('rollup_candidates'), 'sanity: skenario ini memang punya peluang gulung');
        $this->assertSame(
            OpnameLifecycle::ROLLUP_PROJECTION_ENABLED,
            $response->json('show_popup'),
            'show_popup Bahan harus persis mengikuti const yang sama dengan Produk',
        );
    }

    public function test_insert_without_an_explicit_decision_follows_the_show_popup_flag(): void
    {
        $this->actingAsSuperAdminStaff();

        $s = $this->makeLadderedSupplies(dosStock: 90, pcsStock: 104);
        $items = $this->dosOnlyItemPayload($s, 93);

        $response = $this->insertOpname($items);
        $response->assertStatus(200);
        $stobId = (int) $response->json('stob_id');
        $lines = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');

        if (OpnameLifecycle::ROLLUP_PROJECTION_ENABLED) {
            $this->assertSame(93, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty, 'flag ON -> default aman, tidak digulung tanpa keputusan eksplisit');
            $this->assertNull($lines[$this->units['pcs']->unit_id]->sobl_counted_qty);
        } else {
            $this->assertSame(101, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty, 'flag OFF -> otomatis "Lanjut", tetap tergulung penuh walau tidak ada rollup_decision');
            $this->assertSame(8, (int) $lines[$this->units['pcs']->unit_id]->sobl_counted_qty);
        }
    }
}
