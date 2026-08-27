<?php

namespace Tests\Workflow;

use App\Models\StockOpnameBahan;
use App\Models\StockOpnameBahanLine;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Kembaran persis Tests\Workflow\StockOpnameV2EndToEndTest, untuk Stock Opname BAHAN. Sama-sama
 * men-POST/GET ke endpoint sungguhan (/insertStockOpnameBahan, /updateStockOpnameBahan,
 * /submitStockOpnameBahan, /accStockOpnameBahan, /tolakStockOpnameBahan,
 * /generateStockOpnameBahan/{id}), bukan memanggil kelas lifecycle/reader langsung.
 *
 * Lihat StockOpnameV2EndToEndTest untuk daftar lengkap skenario dan alasannya -- di sini cukup
 * disebut bedanya: identitas Bahan cuma nama (tidak ada varian/SKU/product_id), payload memakai
 * `sp_units` (bukan `units`), dan approval memakai log_type=2 + ss_stock (bukan ps_stock).
 */
class StockOpnameBahanV2EndToEndTest extends TestCase
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

    private function makeCatalogItem(string $name, string $sku, int $dosStock, int $pcsStock = 0): Supplies
    {
        $supplies = new Supplies();
        $supplies->supplies_name = $name.' '.$sku.'-'.uniqid();
        $supplies->supplies_unit = json_encode([$this->units['dos']->unit_id, $this->units['pcs']->unit_id]);
        $supplies->supplies_default_unit = $this->units['dos']->unit_id;
        $supplies->status = 1;
        $supplies->save();

        foreach ([['dos', $dosStock], ['pcs', $pcsStock]] as [$key, $qty]) {
            $s = new SuppliesStock();
            $s->supplies_id = $supplies->supplies_id;
            $s->unit_id = $this->units[$key]->unit_id;
            $s->warehouse_id = 1;
            $s->ss_stock = $qty;
            $s->status = 1;
            $s->save();
        }

        return $supplies;
    }

    /** Pasang relasi satuan (1 DOS = $ratio pcs) di atas makeCatalogItem() -- untuk skenario roll-up. */
    private function withLadder(Supplies $supplies, int $ratio = 12): Supplies
    {
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

    /**
     * @param  array<int, array{supplies: Supplies, dos: int|null, pcs: int|null}>  $lines
     *         null = satuan itu dibiarkan tidak dihitung.
     */
    private function insertOpname(array $lines, bool $isDraft = false, string $notes = 'E2E Bahan test'): int
    {
        $items = [];
        foreach ($lines as $line) {
            $sp = $line['supplies'];
            $items[] = [
                'supplies_id' => $sp->supplies_id,
                'stobd_notes' => $line['notes'] ?? null,
                'sp_units' => [
                    [
                        'unit_id' => $this->units['dos']->unit_id,
                        'system_qty' => $this->currentStock($sp, 'dos'),
                        'real_qty' => $line['dos'],
                    ],
                    [
                        'unit_id' => $this->units['pcs']->unit_id,
                        'system_qty' => $this->currentStock($sp, 'pcs'),
                        'real_qty' => $line['pcs'],
                    ],
                ],
            ];
        }

        $response = $this->post('/insertStockOpnameBahan', [
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => $notes,
            'is_draft' => $isDraft ? 1 : 0,
            'item' => json_encode($items),
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        return (int) $response->json('stob_id');
    }

    private function currentStock(Supplies $sp, string $unitKey): int
    {
        return (int) SuppliesStock::where('supplies_id', $sp->supplies_id)
            ->where('unit_id', $this->units[$unitKey]->unit_id)
            ->value('ss_stock');
    }

    private function approve(int $stobId): \Illuminate\Testing\TestResponse
    {
        return $this->post('/accStockOpnameBahan', ['stob_id' => $stobId, 'item' => json_encode([])]);
    }

    private function reject(int $stobId): \Illuminate\Testing\TestResponse
    {
        return $this->post('/tolakStockOpnameBahan', ['stob_id' => $stobId]);
    }

    private function assertPdfEndpointWorks(int $stobId): void
    {
        $response = $this->get('/generateStockOpnameBahan/'.$stobId);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    private function renderedHtml(int $stobId): string
    {
        $stob = StockOpnameBahan::find($stobId);
        $statusLabel = match ((int) $stob->status) {
            1 => 'Menunggu', 2 => 'Disetujui', 3 => 'Ditolak', default => '-',
        };

        return \Illuminate\Support\Facades\View::make('Backoffice.PDF.OpnameBahan', [
            'stockOpname' => ['stob_code' => $stob->stob_code, 'stob_date' => $stob->stob_date, 'stob_notes' => $stob->stob_notes],
            'staff_name' => ['staff_name' => $stob->stob_staff_name ?: '-'],
            'status' => $statusLabel,
            'detail' => (new \App\Support\StockOpname\BahanOpnameLineReader())->read($stob),
        ])->render();
    }

    private function pdf(int $stobId): string
    {
        $this->assertPdfEndpointWorks($stobId);

        return $this->renderedHtml($stobId);
    }

    private function extractRow(string $html, string $needle): string
    {
        $pos = strpos($html, $needle);
        $this->assertNotFalse($pos, "Teks '$needle' tidak ditemukan di PDF yang dicetak");

        $trStart = strrpos(substr($html, 0, $pos), '<tr>');
        $trEnd = strpos($html, '</tr>', $pos);

        return substr($html, $trStart, $trEnd - $trStart);
    }

    private const YELLOW = 'background-color: #FFF9C4;';
    private const GREEN = 'background-color: #C8E6C9;';

    // ================================================================== 1. menunggu, campuran

    public function test_pending_document_with_mixed_units_shows_correct_highlight_per_row(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->makeCatalogItem('MINYAK REM HIKARI', 'MRHK1L', dosStock: 3, pcsStock: 20);

        $stobId = $this->insertOpname([[
            'supplies' => $sp, 'dos' => 8, 'pcs' => null,
        ]]);

        $stob = StockOpnameBahan::find($stobId);
        $this->assertFalse((bool) $stob->is_old_version);
        $this->assertSame(1, (int) $stob->status);

        $html = $this->pdf($stobId);
        $row = $this->extractRow($html, $sp->supplies_name);

        $this->assertStringContainsString(self::YELLOW, $row);
        $this->assertStringContainsString('5 '.$this->units['dos']->unit_short_name, $row);
        $this->assertStringNotContainsString('- '.$this->units['pcs']->unit_short_name, $row);
        $this->assertStringContainsString('20 '.$this->units['pcs']->unit_short_name, $row);

        $line = StockOpnameBahanLine::getLines($stobId)->firstWhere('unit_id', $this->units['dos']->unit_id);
        $this->assertNull($line->sobl_system_qty_final);
        $this->assertNotNull($line->sobl_supplies_name);
    }

    // ================================================================== 2. stok bergerak saat menunggu

    public function test_pending_document_pdf_tracks_live_stock_and_writes_nothing(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->makeCatalogItem('HIKARI SUPER GREASE', 'HSG450GR', dosStock: 16, pcsStock: 0);
        $stobId = $this->insertOpname([['supplies' => $sp, 'dos' => 16, 'pcs' => 0]]);

        $rowBefore = $this->extractRow($this->pdf($stobId), $sp->supplies_name);
        $this->assertStringContainsString(self::GREEN, $rowBefore);

        SuppliesStock::where('supplies_id', $sp->supplies_id)
            ->where('unit_id', $this->units['dos']->unit_id)
            ->update(['ss_stock' => 10]);

        $rowAfter = $this->extractRow($this->pdf($stobId), $sp->supplies_name);
        $this->assertStringContainsString(self::YELLOW, $rowAfter);
        $this->assertStringContainsString('6 '.$this->units['dos']->unit_short_name, $rowAfter);

        $line = StockOpnameBahanLine::where('stob_id', $stobId)->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertNull($line->sobl_system_qty_final);
    }

    // ================================================================== 3. disetujui

    public function test_approving_writes_stock_freezes_snapshot_and_stops_tracking_live_movement(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->makeCatalogItem('RADIATOR COOLANT HIKARI', 'RCHK5L', dosStock: 694, pcsStock: 5);
        $stobId = $this->insertOpname([['supplies' => $sp, 'dos' => 689, 'pcs' => null]]);

        $logCountBefore = DB::table('log_stocks')->count();
        $this->approve($stobId)->assertOk();

        $stob = StockOpnameBahan::find($stobId);
        $this->assertSame(2, (int) $stob->status);
        $this->assertNotNull($stob->stob_acc_name);
        $this->assertNotNull($stob->stob_decided_at);

        $dosStock = SuppliesStock::where('supplies_id', $sp->supplies_id)
            ->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertSame(689, $dosStock->ss_stock);
        $this->assertSame($logCountBefore + 2, DB::table('log_stocks')->count());
        $this->assertDatabaseHas('log_stocks', ['log_type' => 2, 'log_item_id' => $sp->supplies_id, 'unit_id' => $this->units['dos']->unit_id, 'log_jumlah' => 694]);
        $this->assertDatabaseHas('log_stocks', ['log_type' => 2, 'log_item_id' => $sp->supplies_id, 'unit_id' => $this->units['dos']->unit_id, 'log_jumlah' => 689]);

        $pcsStock = SuppliesStock::where('supplies_id', $sp->supplies_id)
            ->where('unit_id', $this->units['pcs']->unit_id)->first();
        $this->assertSame(5, $pcsStock->ss_stock);

        $line = StockOpnameBahanLine::where('stob_id', $stobId)->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertSame(694, $line->sobl_system_qty_final);

        SuppliesStock::where('ss_id', $dosStock->ss_id)->update(['ss_stock' => 1]);
        $row = $this->extractRow($this->pdf($stobId), $sp->supplies_name);
        $this->assertStringContainsString('694 '.$this->units['dos']->unit_short_name, $row);
        $this->assertStringContainsString(self::YELLOW, $row);
    }

    // ================================================================== 4. ditolak

    public function test_rejecting_leaves_stock_untouched_but_still_freezes_the_document(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->makeCatalogItem('HIKARI SIGNATURE HI TEMP GREASE', 'HKSG450GR', dosStock: 385, pcsStock: 0);
        $stobId = $this->insertOpname([['supplies' => $sp, 'dos' => 85, 'pcs' => 0]]);

        $this->reject($stobId)->assertOk();

        $stob = StockOpnameBahan::find($stobId);
        $this->assertSame(3, (int) $stob->status);
        $this->assertNotNull($stob->stob_decided_at);

        $stock = SuppliesStock::where('supplies_id', $sp->supplies_id)
            ->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertSame(385, $stock->ss_stock);

        SuppliesStock::where('ss_id', $stock->ss_id)->update(['ss_stock' => 999]);
        $row = $this->extractRow($this->pdf($stobId), $sp->supplies_name);
        $this->assertStringContainsString('385 '.$this->units['dos']->unit_short_name, $row);
        $this->assertStringContainsString('-300 '.$this->units['dos']->unit_short_name, $row);
        $this->assertStringContainsString(self::YELLOW, $row);
    }

    // ================================================================== 5. semua hijau

    public function test_document_where_every_line_matches_system_is_entirely_green(): void
    {
        $this->actingAsSuperAdminStaff();

        $lines = [
            $this->makeCatalogItem('AIR AKI HIKARI', 'AAHK1L', dosStock: 109, pcsStock: 0),
            $this->makeCatalogItem('AIR ZUUR HIKARI', 'AZHK1L', dosStock: 131, pcsStock: 0),
        ];

        $stobId = $this->insertOpname([
            ['supplies' => $lines[0], 'dos' => 109, 'pcs' => 0],
            ['supplies' => $lines[1], 'dos' => 131, 'pcs' => 0],
        ]);

        $html = $this->pdf($stobId);
        foreach ($lines as $sp) {
            $row = $this->extractRow($html, $sp->supplies_name);
            $this->assertStringContainsString(self::GREEN, $row);
            $this->assertStringNotContainsString(self::YELLOW, $row);
        }
    }

    // ================================================================== 6. semua kuning

    public function test_document_where_every_line_differs_is_entirely_yellow(): void
    {
        $this->actingAsSuperAdminStaff();

        $lines = [
            $this->makeCatalogItem('MINYAK REM HIKARI MERAH', 'MRHK30MLM', dosStock: 4, pcsStock: 0),
            $this->makeCatalogItem('HIKARI GREENTECH GREASE', 'HGT450GR', dosStock: 149, pcsStock: 0),
        ];

        $stobId = $this->insertOpname([
            ['supplies' => $lines[0], 'dos' => 8, 'pcs' => 0],
            ['supplies' => $lines[1], 'dos' => 139, 'pcs' => 0],
        ]);

        $html = $this->pdf($stobId);
        foreach ($lines as $sp) {
            $row = $this->extractRow($html, $sp->supplies_name);
            $this->assertStringContainsString(self::YELLOW, $row);
            $this->assertStringNotContainsString(self::GREEN, $row);
        }
    }

    // ================================================================== 7. multi-bahan gaya SP0071

    public function test_realistic_multi_supplies_document_colors_each_row_independently(): void
    {
        $this->actingAsSuperAdminStaff();

        $matched = $this->makeCatalogItem('AIR AKI PEGASUS', 'AAP1L', dosStock: 126, pcsStock: 0);
        $increased = $this->makeCatalogItem('KUDA INTAN AIR ZUUR', 'AZKI1L', dosStock: 0, pcsStock: 0);
        $decreased = $this->makeCatalogItem('RADIATOR COOLANT HIKARI HIJAU', 'RCHK5LH', dosStock: 694, pcsStock: 0);
        $uncounted = $this->makeCatalogItem('PERTAMINA TURALIK', 'PERT209L', dosStock: 1, pcsStock: 0);
        $small = $this->makeCatalogItem('MINYAK REM HIKARI MERAH 1L', 'MRHK1LM', dosStock: 3, pcsStock: 0);

        $stobId = $this->insertOpname([
            ['supplies' => $matched, 'dos' => 126, 'pcs' => 0],
            ['supplies' => $increased, 'dos' => 50, 'pcs' => 0],
            ['supplies' => $decreased, 'dos' => 689, 'pcs' => 0],
            ['supplies' => $uncounted, 'dos' => null, 'pcs' => null],
            ['supplies' => $small, 'dos' => 8, 'pcs' => 0],
        ]);

        $html = $this->pdf($stobId);

        $r1 = $this->extractRow($html, $matched->supplies_name);
        $this->assertStringContainsString(self::GREEN, $r1);

        $r2 = $this->extractRow($html, $increased->supplies_name);
        $this->assertStringContainsString(self::YELLOW, $r2);
        $this->assertStringContainsString('50 '.$this->units['dos']->unit_short_name, $r2);

        $r3 = $this->extractRow($html, $decreased->supplies_name);
        $this->assertStringContainsString(self::YELLOW, $r3);
        $this->assertStringContainsString('-5 '.$this->units['dos']->unit_short_name, $r3);

        $r4 = $this->extractRow($html, $uncounted->supplies_name);
        $this->assertStringNotContainsString(self::YELLOW, $r4);
        $this->assertStringNotContainsString(self::GREEN, $r4);

        $r5 = $this->extractRow($html, $small->supplies_name);
        $this->assertStringContainsString(self::YELLOW, $r5);
        $this->assertStringContainsString('5 '.$this->units['dos']->unit_short_name, $r5);

        $this->assertCount(5, StockOpnameBahanLine::getLines($stobId)->pluck('supplies_id')->unique());
    }

    // ================================================================== 8. draft -> submit

    public function test_draft_has_no_snapshot_until_submitted(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->makeCatalogItem('SILICONE PEGASUS', 'SILP400ML', dosStock: 0, pcsStock: 0);
        $stobId = $this->insertOpname([['supplies' => $sp, 'dos' => 12, 'pcs' => 0]], isDraft: true);

        $stob = StockOpnameBahan::find($stobId);
        $this->assertTrue((bool) $stob->is_draft);
        $this->assertNull($stob->stob_staff_name);

        $line = StockOpnameBahanLine::getLines($stobId)->first();
        $this->assertNull($line->sobl_supplies_name);
        $this->assertSame(12, (int) $line->sobl_counted_qty);

        $this->post('/submitStockOpnameBahan', ['stob_id' => $stobId])->assertStatus(200);

        $stob->refresh();
        $this->assertFalse((bool) $stob->is_draft);
        $this->assertNotNull($stob->stob_staff_name);
        $this->assertNotNull(StockOpnameBahanLine::getLines($stobId)->first()->sobl_supplies_name);
    }

    // ================================================================== 9. edit dokumen menunggu

    public function test_editing_a_pending_document_upserts_and_keeps_frozen_name(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->makeCatalogItem('SAM OIL 4T', 'SAMOIL4T800ML', dosStock: 6, pcsStock: 0);
        $stobId = $this->insertOpname([['supplies' => $sp, 'dos' => 6, 'pcs' => 0]]);

        $frozenName = StockOpnameBahanLine::getLines($stobId)->first()->sobl_supplies_name;

        Supplies::where('supplies_id', $sp->supplies_id)->update(['supplies_name' => 'NAMA BARU SETELAH EDIT']);

        $updateResponse = $this->post('/updateStockOpnameBahan', [
            'stob_id' => $stobId,
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => 'Dikoreksi',
            'item' => json_encode([[
                'supplies_id' => $sp->supplies_id,
                'sp_units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 6, 'real_qty' => 4],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => 0],
                ],
            ]]),
        ]);
        $updateResponse->assertStatus(200);

        $lines = StockOpnameBahanLine::getLines($stobId);
        $this->assertCount(2, $lines, 'edit tidak boleh menggandakan baris');

        $dosLine = $lines->firstWhere('unit_id', $this->units['dos']->unit_id);
        $this->assertSame(4, (int) $dosLine->sobl_counted_qty);
        $this->assertSame($frozenName, $dosLine->sobl_supplies_name, 'nama yang sudah beku tidak boleh ikut menyegarkan diri');
    }

    // ================================================================== 10. integritas ujung ke ujung

    public function test_end_to_end_data_integrity_across_the_full_lifecycle(): void
    {
        $this->actingAsSuperAdminStaff();

        $a = $this->makeCatalogItem('PEGASUS CHAMOIS', 'PGCHS', dosStock: 87, pcsStock: 0);
        $b = $this->makeCatalogItem('SPONGE WASH', 'SW60X1', dosStock: 35, pcsStock: 0);

        $stobId = $this->insertOpname([
            ['supplies' => $a, 'dos' => 87, 'pcs' => 0],
            ['supplies' => $b, 'dos' => 30, 'pcs' => 0],
        ]);

        $this->assertDatabaseMissing('stock_opname_detail_bahans', ['stob_id' => $stobId]);
        $this->assertSame(4, StockOpnameBahanLine::where('stob_id', $stobId)->count());
        $this->assertSame(2, StockOpnameBahanLine::where('stob_id', $stobId)->pluck('supplies_id')->unique()->count());

        $this->approve($stobId)->assertOk();

        $stockA = SuppliesStock::where('supplies_id', $a->supplies_id)
            ->where('unit_id', $this->units['dos']->unit_id)->first();
        $stockB = SuppliesStock::where('supplies_id', $b->supplies_id)
            ->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertSame(87, $stockA->ss_stock);
        $this->assertSame(30, $stockB->ss_stock);

        $html = $this->pdf($stobId);
        $rowA = $this->extractRow($html, $a->supplies_name);
        $rowB = $this->extractRow($html, $b->supplies_name);
        $this->assertStringContainsString('87 '.$this->units['dos']->unit_short_name, $rowA);
        $this->assertStringContainsString(self::GREEN, $rowA);
        $this->assertStringContainsString('-5 '.$this->units['dos']->unit_short_name, $rowB);
        $this->assertStringContainsString(self::YELLOW, $rowB);

        $this->assertDatabaseHas('log_stocks', ['log_type' => 2, 'log_item_id' => $a->supplies_id, 'log_jumlah' => 87]);
        $this->assertDatabaseHas('log_stocks', ['log_type' => 2, 'log_item_id' => $b->supplies_id, 'log_jumlah' => 35]);
        $this->assertDatabaseHas('log_stocks', ['log_type' => 2, 'log_item_id' => $b->supplies_id, 'log_jumlah' => 30]);
    }

    // ================================================================== 11. multi-satuan, cuma satuan pertama diisi

    public function test_supplies_with_multiple_units_where_only_the_first_is_filled_in(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->makeCatalogItem('HIKARI TYRE POLISH', 'HKTP1L', dosStock: 20, pcsStock: 7);

        $stobId = $this->insertOpname([[
            'supplies' => $sp, 'dos' => 15, 'pcs' => null,
        ]]);

        $lines = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');
        $this->assertCount(2, $lines);
        $this->assertSame(15, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty);
        $this->assertNull($lines[$this->units['pcs']->unit_id]->sobl_counted_qty);

        $row = $this->extractRow($this->pdf($stobId), $sp->supplies_name);
        $this->assertStringContainsString(self::YELLOW, $row);
        $this->assertStringContainsString('-5 '.$this->units['dos']->unit_short_name, $row);
        $this->assertStringContainsString('7 '.$this->units['pcs']->unit_short_name, $row);
        $this->assertStringNotContainsString('- '.$this->units['pcs']->unit_short_name, $row);

        $pcsBefore = $this->currentStock($sp, 'pcs');
        $this->approve($stobId)->assertOk();

        $this->assertSame(15, $this->currentStock($sp, 'dos'));
        $this->assertSame($pcsBefore, $this->currentStock($sp, 'pcs'), 'satuan yang tak diisi TIDAK BOLEH tersentuh sama sekali');
        $this->assertDatabaseMissing('log_stocks', ['log_item_id' => $sp->supplies_id, 'unit_id' => $this->units['pcs']->unit_id]);

        $frozen = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');
        $this->assertSame(20, $frozen[$this->units['dos']->unit_id]->sobl_system_qty_final);
        $this->assertSame($pcsBefore, $frozen[$this->units['pcs']->unit_id]->sobl_system_qty_final);
    }

    /** Kembaran test_a_units_entry_missing_entirely_from_the_payload_never_creates_a_line() (Produk). */
    public function test_a_sp_units_entry_missing_entirely_from_the_payload_never_creates_a_line(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->makeCatalogItem('SILICONE OIL PEGASUS', 'SOP100ML', dosStock: 11, pcsStock: 9);

        $response = $this->post('/insertStockOpnameBahan', [
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => 'Payload tidak lengkap, sengaja',
            'is_draft' => 0,
            'item' => json_encode([[
                'supplies_id' => $sp->supplies_id,
                'sp_units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 11, 'real_qty' => 8],
                ],
            ]]),
        ]);
        $response->assertStatus(200);
        $stobId = (int) $response->json('stob_id');

        $lines = StockOpnameBahanLine::getLines($stobId);
        $this->assertCount(1, $lines);
        $this->assertSame((int) $this->units['dos']->unit_id, (int) $lines->first()->unit_id);

        $row = $this->extractRow($this->pdf($stobId), $sp->supplies_name);
        $this->assertStringNotContainsString($this->units['pcs']->unit_short_name, $row);

        $this->approve($stobId)->assertOk();
        $this->assertSame(0, StockOpnameBahanLine::getLines($stobId)->where('unit_id', $this->units['pcs']->unit_id)->count());
        $this->assertSame(9, $this->currentStock($sp, 'pcs'));
    }

    // ================================================================== 13. gulung satuan lewat endpoint sungguhan

    /** Kembaran persis Produk -- contoh PM lewat /insertStockOpnameBahan sungguhan. */
    public function test_insert_endpoint_rolls_up_a_filled_small_unit_automatically(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->withLadder($this->makeCatalogItem('HIKARI SUPER GREASE', 'HSG450GR', dosStock: 0, pcsStock: 0));

        $stobId = $this->insertOpname([['supplies' => $sp, 'dos' => null, 'pcs' => 30]]);

        $lines = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');
        $this->assertSame(6, (int) $lines[$this->units['pcs']->unit_id]->sobl_counted_qty);
        $this->assertSame(2, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty, 'DOS harus otomatis terisi dari kelebihan pcs, lewat endpoint sungguhan');

        $row = $this->extractRow($this->pdf($stobId), $sp->supplies_name);
        $this->assertStringContainsString('6 '.$this->units['pcs']->unit_short_name, $row);
        $this->assertStringContainsString('2 '.$this->units['dos']->unit_short_name, $row);
    }

    /** "Baik draft maupun langsung menunggu" -- draft lewat /insertStockOpnameBahan juga harus tergulung. */
    public function test_insert_endpoint_rolls_up_on_a_draft_document_too(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->withLadder($this->makeCatalogItem('MINYAK REM HIKARI', 'MRHK1L', dosStock: 0, pcsStock: 0));

        $stobId = $this->insertOpname([['supplies' => $sp, 'dos' => null, 'pcs' => 30]], isDraft: true);

        $stob = StockOpnameBahan::find($stobId);
        $this->assertTrue((bool) $stob->is_draft);

        $lines = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');
        $this->assertSame(6, (int) $lines[$this->units['pcs']->unit_id]->sobl_counted_qty);
        $this->assertSame(2, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty, 'draft pun harus tergulung');
    }

    /** Mengedit dokumen menunggu lewat /updateStockOpnameBahan juga harus menggulung ulang. */
    public function test_update_endpoint_rolls_up_after_editing_the_count(): void
    {
        $this->actingAsSuperAdminStaff();

        $sp = $this->withLadder($this->makeCatalogItem('AIR AKI PEGASUS', 'AAP1L', dosStock: 0, pcsStock: 0));
        $stobId = $this->insertOpname([['supplies' => $sp, 'dos' => null, 'pcs' => 5]]);

        $this->assertNull(StockOpnameBahanLine::getLines($stobId)->firstWhere('unit_id', $this->units['dos']->unit_id)->sobl_counted_qty);

        $updateResponse = $this->post('/updateStockOpnameBahan', [
            'stob_id' => $stobId,
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => 'Dikoreksi jadi 30 pcs',
            'item' => json_encode([[
                'supplies_id' => $sp->supplies_id,
                'sp_units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => 30],
                ],
            ]]),
        ]);
        $updateResponse->assertStatus(200);

        $lines = StockOpnameBahanLine::getLines($stobId)->keyBy('unit_id');
        $this->assertSame(6, (int) $lines[$this->units['pcs']->unit_id]->sobl_counted_qty);
        $this->assertSame(2, (int) $lines[$this->units['dos']->unit_id]->sobl_counted_qty, 'edit yang menaikkan hitungan harus ikut menggulung ulang');
    }
}
