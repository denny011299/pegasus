<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug report user 2026-09-04: "Posisi awal stok Produk A = 90 Dos, 104 Piece. Aku stok opname
 * ubah dos aja jadi 93 Dos. Saat create dan print document jadinya 93 Dos, 104 Piece."
 *
 * Investigasi: angka itu SUDAH BENAR per aturan GH #78 (UnitRollUp::collapse()'s docblock --
 * jangan pernah mengarang angka satuan yang tidak pernah diperiksa staf, Piece TETAP 104 apa
 * adanya). User minta ini ditawarkan sebagai PILIHAN eksplisit di titik dokumen terbit lewat
 * popup konfirmasi.
 *
 * >>> GANTI DESAIN 2026-09-05 <<< (follow-up dari user setelah popup pertama terpasang):
 * Versi pertama (2026-09-04) menulis dokumen (header + baris) DULU cuma untuk bisa mengecek
 * peluang gulung, baru membalas status=2 kalau ada -- artinya kalau staf klik "Batal", dokumen
 * yang sudah ditulis itu tetap disimpan (cuma tanpa gulung penuh). User bilang itu salah: klik
 * "Batal" harus berarti BATAL AJUKAN SAMA SEKALI, tidak ada DB yang tersentuh. Sekarang deteksi
 * dipisah total dari penulisan: /previewStockOpnameRollup (baca-saja, OpnameLifecycle::
 * detectRollupOpportunitiesFromPayload()) dipanggil DULU dengan payload mentah dari form -- kalau
 * staf klik Lanjut, browser BARU memanggil insertStockOpname()/submitStockOpname() sungguhan
 * dengan rollup_decision='full'. insertStockOpname() sendiri sekarang selalu single-shot lagi
 * (tidak ada lagi jalur tulis-dulu-tanya-nanti).
 *
 * Skenario di sini:
 *  1. /previewStockOpnameRollup mendeteksi peluang gulung (Dos diisi, Piece TIDAK, existing Piece
 *     > 1 ratio Dos) TANPA MENULIS APA PUN -- tidak ada StockOpname/StockOpnameLine yang tercipta.
 *  2. rollup_decision diabaikan/'skip' (mis. staf klik Batal, TIDAK melanjutkan ke
 *     insertStockOpname() sama sekali) -> tidak ada dokumen yang tercipta sama sekali.
 *  3. rollup_decision='full' (staf klik Lanjut) -> insertStockOpname() single-shot langsung
 *     menggulung penuh, Piece ikut dilipat ke Dos.
 *  4. submitStockOpname() (ajukan draft) menerima rollup_decision dengan cara yang sama.
 *  5. Gudang ECERAN: /previewStockOpnameRollup tidak pernah mendeteksi peluang di sana.
 */
class StockOpnameRollupConfirmTest extends TestCase
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

    private function categoryId(): int
    {
        $id = (int) DB::table('categories')->where('status', 1)->value('category_id');
        if ($id) {
            return $id;
        }

        $c = new Category();
        $c->category_name = 'Opname Rollup Confirm Test Category';
        $c->status = 1;
        $c->save();

        return $c->category_id;
    }

    /** Produk + ladder (1 DOS = $ratio pcs) + stok awal di kedua satuan, gudang utama (id 1). */
    private function makeLadderedItem(int $dosStock, int $pcsStock, int $ratio = 12, ?int $warehouseId = 1): ProductVariant
    {
        $product = new Product();
        $product->product_name = 'ROLLUP CONFIRM TEST PRODUCT';
        $product->category_id = $this->categoryId();
        $product->product_unit = json_encode([$this->units['dos']->unit_id, $this->units['pcs']->unit_id]);
        $product->unit_id = $this->units['dos']->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'ROLLUP-CONFIRM-'.uniqid();
        $variant->product_variant_sku = 'RUC-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        foreach ([['dos', $dosStock], ['pcs', $pcsStock]] as [$key, $qty]) {
            $s = new ProductStock();
            $s->product_id = $product->product_id;
            $s->product_variant_id = $variant->product_variant_id;
            $s->unit_id = $this->units[$key]->unit_id;
            $s->warehouse_id = $warehouseId;
            $s->ps_stock = $qty;
            $s->status = 1;
            $s->save();
        }

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = $this->units['dos']->unit_id; // besar
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = $this->units['pcs']->unit_id; // kecil
        $relation->pr_unit_value_2 = $ratio;
        $relation->status = 1;
        $relation->save();

        return $variant;
    }

    /** Payload item[]: cuma Dos yang diisi ($dosQty), Piece dikirim sebagai unit_id + real_qty:null. */
    private function dosOnlyItemPayload(ProductVariant $v, int $dosQty): array
    {
        return [[
            'product_id' => $v->product_id,
            'product_variant_id' => $v->product_variant_id,
            'units' => [
                ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => $dosQty],
                ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
            ],
        ]];
    }

    private function preview(array $items, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/previewStockOpnameRollup', array_merge([
            'item' => json_encode($items),
        ], $extra));
    }

    private function insertOpname(array $items, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/insertStockOpname', array_merge([
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Rollup confirm test',
            'is_draft' => 0,
            'item' => json_encode($items),
        ], $extra));
    }

    public function test_preview_detects_a_rollup_opportunity_without_writing_anything(): void
    {
        $this->actingAsSuperAdminStaff();

        // Meniru laporan bug: 90 Dos, 104 Piece -- staf cuma mengoreksi Dos jadi 93.
        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104);
        $stoCountBefore = StockOpname::count();
        $lineCountBefore = StockOpnameLine::count();

        $response = $this->preview($this->dosOnlyItemPayload($v, 93));
        $response->assertStatus(200);

        $candidates = $response->json('rollup_candidates');
        $this->assertCount(1, $candidates);
        $this->assertSame($v->product_variant_id, $candidates[0]['product_variant_id']);
        // Rincian per satuan, diurutkan BESAR -> KECIL (keputusan user 2026-09-05: DOS di kiri).
        $this->assertSame($this->units['dos']->unit_id, $candidates[0]['changes'][0]['unit_id']);
        $dosChange = $candidates[0]['changes'][0];
        $this->assertSame(93, $dosChange['before']);
        $this->assertSame(101, $dosChange['after']);
        $this->assertSame($this->units['dos']->unit_short_name, $dosChange['unit_short_name']);
        $pcsChange = $candidates[0]['changes'][1];
        $this->assertSame($this->units['pcs']->unit_id, $pcsChange['unit_id']);
        $this->assertSame(104, $pcsChange['before']);
        $this->assertSame(8, $pcsChange['after']);

        // "Data bayangan" (keputusan user 2026-09-05): TIDAK ADA satu baris pun tertulis ke DB.
        $this->assertSame($stoCountBefore, StockOpname::count(), 'preview tidak boleh membuat dokumen apa pun');
        $this->assertSame($lineCountBefore, StockOpnameLine::count(), 'preview tidak boleh membuat baris apa pun');
    }

    /**
     * Bug report user 2026-09-05: ajukan langsung (tanpa draft) -> popup menampilkan 3 baris
     * termasuk baris yang benar-benar diisi. Simpan sebagai draft (cuma baris yang diisi ikut
     * tersimpan, sesuai desain) -> buka lagi & ajukan ulang -> cuma 1 baris yang tersisa di
     * popup. Akar masalah: .btn-save mengirim SELURUH katalog yang sedang tampil di tabel (bukan
     * cuma yang diisi -- itu memang disengaja untuk dokumen non-draft), dan
     * UnitRollUp::collapseFull() tidak punya guard "produk ini tidak disentuh sama sekali" --
     * jadi produk APA PUN yang kebetulan sedang ter-render DAN kebetulan stok sistemnya sendiri
     * tidak kanonik (mis. dari data lama) ikut ditawarkan sebagai "peluang gulung", padahal staf
     * tidak pernah menyentuhnya. Daftarnya jadi berbeda-beda tergantung produk mana yang
     * kebetulan ter-render (katalog penuh vs cuma baris draft yang tersimpan), bukan berdasarkan
     * input staf -- persis simtom yang dilaporkan.
     */
    public function test_an_entirely_untouched_product_is_never_a_rollup_candidate_even_with_noncanonical_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        // Produk yang BENAR-BENAR diisi staf.
        $touched = $this->makeLadderedItem(dosStock: 90, pcsStock: 104);
        // Produk LAIN yang kebetulan sedang ter-render di tabel (mis. katalog penuh saat create
        // langsung) tapi TIDAK PERNAH disentuh staf sama sekali -- stok sistemnya sendiri sudah
        // tidak kanonik (104 pcs > 1 ratio Dos), persis kondisi yang memicu bug.
        $untouched = $this->makeLadderedItem(dosStock: 5, pcsStock: 30, ratio: 12);

        $items = array_merge(
            $this->dosOnlyItemPayload($touched, 93),
            [[
                'product_id' => $untouched->product_id,
                'product_variant_id' => $untouched->product_variant_id,
                'units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                ],
            ]],
        );

        $response = $this->preview($items);
        $response->assertStatus(200);

        $candidates = $response->json('rollup_candidates');
        $this->assertCount(1, $candidates, 'produk yang tidak disentuh staf tidak boleh ikut jadi kandidat gulung');
        $this->assertSame($touched->product_variant_id, $candidates[0]['product_variant_id']);
    }

    public function test_batal_never_touches_the_database_at_all(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104);
        $items = $this->dosOnlyItemPayload($v, 93);
        $stoCountBefore = StockOpname::count();
        $lineCountBefore = StockOpnameLine::count();

        // Staf melihat popup lewat preview, lalu klik "Batal" -- browser TIDAK PERNAH memanggil
        // insertStockOpname() sama sekali. Cukup pastikan preview sendiri tidak menulis apa pun
        // (sudah dites di atas) dan tidak ada endpoint lain yang perlu dipanggil untuk "batal".
        $this->preview($items)->assertStatus(200);

        $this->assertSame($stoCountBefore, StockOpname::count());
        $this->assertSame($lineCountBefore, StockOpnameLine::count());
        $this->assertSame(90, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['dos']->unit_id)->value('ps_stock'));
        $this->assertSame(104, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['pcs']->unit_id)->value('ps_stock'));
    }

    public function test_insert_without_rollup_decision_defaults_to_the_old_safe_partial_behavior(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104);
        $items = $this->dosOnlyItemPayload($v, 93);

        // Tidak ada rollup_decision dikirim -- default 'skip' (pemanggil yang tidak lewat preview
        // sama sekali, mis. panggilan API langsung) tidak pernah diam-diam menggulung penuh.
        $response = $this->insertOpname($items);
        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);
        $stoId = (int) $response->json('sto_id');

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(93, (int) $lines[$this->units['dos']->unit_id]->sol_counted_qty, 'Dos tetap 93, tidak berubah');
        $this->assertNull($lines[$this->units['pcs']->unit_id]->sol_counted_qty, 'Piece TETAP null -- perilaku lama, tidak digulung');

        $sto = StockOpname::find($stoId);
        $this->assertNotNull($sto->sto_staff_name, 'publish() harus tetap jalan untuk dokumen non-draft');

        // ACC: cuma Dos yang tertulis ke ps_stock, Piece tidak tersentuh -- persis laporan bug user.
        $this->post('/accStockOpname', ['sto_id' => $stoId, 'item' => json_encode([['dummy' => 1]])])->assertOk();
        $this->assertSame(93, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['dos']->unit_id)->value('ps_stock'));
        $this->assertSame(104, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['pcs']->unit_id)->value('ps_stock'));
    }

    public function test_lanjut_creates_the_document_in_one_shot_with_full_rollup(): void
    {
        $this->actingAsSuperAdminStaff();

        // 1 DOS = 12 pcs. 93 Dos (diisi) + 104 pcs (existing, tidak diisi) = 1116 + 104 = 1220 pcs
        // = 101 DOS + 8 pcs kanonik.
        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104, ratio: 12);
        $items = $this->dosOnlyItemPayload($v, 93);
        $stoCountBefore = StockOpname::count();

        // Staf klik Lanjut -- browser memanggil insertStockOpname() SATU KALI dengan
        // rollup_decision=full, tidak ada panggilan pertama yang menulis apa pun sebelumnya.
        $response = $this->insertOpname($items, ['rollup_decision' => 'full']);
        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);
        $stoId = (int) $response->json('sto_id');
        $this->assertSame($stoCountBefore + 1, StockOpname::count(), 'cuma SATU dokumen yang tercipta, bukan dua');

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(101, (int) $lines[$this->units['dos']->unit_id]->sol_counted_qty, 'Dos ikut menyerap kelebihan Piece');
        $this->assertSame(8, (int) $lines[$this->units['pcs']->unit_id]->sol_counted_qty, 'Piece jadi sisa kanonik, bukan NULL lagi');

        $this->post('/accStockOpname', ['sto_id' => $stoId, 'item' => json_encode([['dummy' => 1]])])->assertOk();
        $this->assertSame(101, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['dos']->unit_id)->value('ps_stock'));
        $this->assertSame(8, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['pcs']->unit_id)->value('ps_stock'));
    }

    public function test_submit_stock_opname_still_accepts_an_explicit_rollup_decision(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104);
        $items = $this->dosOnlyItemPayload($v, 93);

        // Draft-nya sendiri (halaman "Ajukan" selalu autosave draft dulu) -- ini pra-syarat yang
        // TIDAK berubah oleh perubahan 2026-09-05 (menyimpan draft bukan "menerbitkan").
        $draft = $this->insertOpname($items, ['is_draft' => 1]);
        $draft->assertStatus(200);
        $stoId = (int) $draft->json('sto_id');

        // Frontend sudah menjawab popup lewat preview SEBELUM memanggil ini -- langsung bawa
        // rollup_decision, satu panggilan saja.
        $this->post('/submitStockOpname', ['sto_id' => $stoId, 'rollup_decision' => 'full'])->assertStatus(200);
        $this->assertFalse((bool) StockOpname::find($stoId)->refresh()->is_draft);

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(101, (int) $lines[$this->units['dos']->unit_id]->sol_counted_qty);
        $this->assertSame(8, (int) $lines[$this->units['pcs']->unit_id]->sol_counted_qty);
    }

    public function test_retail_warehouse_never_offers_rollup_confirmation(): void
    {
        $this->actingAsSuperAdminStaff();

        $type = WarehouseType::where('is_main_warehouse', '!=', 1)->first();
        $warehouse = Warehouse::where('warehouse_type_id', $type->id ?? 0)->first();
        if (! $type || ! $warehouse) {
            $this->markTestSkipped('fixture butuh gudang eceran (warehouse_types.is_main_warehouse != 1)');
        }

        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104, warehouseId: $warehouse->id);

        $response = $this->preview($this->dosOnlyItemPayload($v, 93), ['warehouse_id' => $warehouse->id]);
        $response->assertStatus(200);
        $this->assertSame([], $response->json('rollup_candidates'), 'gudang eceran tidak pernah punya peluang gulung');
    }
}
