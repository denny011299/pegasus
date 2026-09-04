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
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Rancang ulang Stock Opname 2026-08-27 -- ujung ke ujung LEWAT HTTP SUNGGUHAN.
 *
 * Beda dengan Tests\Workflow\StockOpnameV2LifecycleTest (yang memanggil OpnameLifecycle/
 * OpnameLineReader langsung sebagai test unit/integrasi), berkas ini men-POST/GET ke endpoint
 * yang benar-benar dipakai CreateStockOpname.js: /insertStockOpname, /updateStockOpname,
 * /submitStockOpname, /accStockOpname, /tolakStockOpname, /generateStockOpname/{id}. Bentuk
 * dokumennya ditarik dari kasus nyata (pola SP0071, PDF produksi 2026-08-27: campuran satuan
 * yang cocok, berselisih, dan tak terhitung dalam satu dokumen, beberapa produk sekaligus).
 *
 * Sepuluh skenario, tiap satu men-cover kombinasi status x highlight x integritas data yang
 * berbeda:
 *   1. Dokumen menunggu, 3 baris (tak dihitung / cocok / berselisih) -- highlight per baris benar,
 *      stok sistem ikut live, TIDAK ADA yang tertulis ke DB hanya karena dibaca.
 *   2. Stok bergerak selagi menunggu -- PDF kedua harus ikut bergeser (bukan snapshot dini).
 *   3. Disetujui -- ps_stock berubah HANYA untuk satuan yang dihitung, log_stocks tercatat,
 *      snapshot dibekukan, PDF sesudahnya tidak lagi ikut stok yang terus bergerak.
 *   4. Ditolak -- ps_stock TIDAK berubah tapi snapshot tetap dibekukan (dokumen final).
 *   5. Semua baris cocok -> semua hijau, nihil kuning.
 *   6. Semua baris berselisih -> semua kuning, nihil hijau.
 *   7. Dokumen multi-produk gaya SP0071 (5 produk, tanda selisih campur) -- tiap baris diperiksa
 *      SENDIRI-SENDIRI, warna tidak boleh bocor ke baris tetangga.
 *   8. Draft -> submit -- snapshot NOL saat draft, muncul persis saat /submitStockOpname dipanggil.
 *   9. Edit dokumen yang sudah menunggu -- upsert, bukan duplikasi baris; nama yang sudah beku
 *      tidak ikut menyegarkan diri.
 *  10. Integritas end-to-end: jumlah baris, sumber tunggal (stock_opname_lines terisi,
 *      stock_opname_details TIDAK), dan angka PDF konsisten dengan apa yang disetujui.
 */
class StockOpnameV2EndToEndTest extends TestCase
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
        $c->category_name = 'Opname E2E Test Category';
        $c->status = 1;
        $c->save();

        return $c->category_id;
    }

    /**
     * Satu produk-varian dengan stok di satuan DOS dan pcs, nama meniru katalog nyata (Hikari/
     * Pegasus) supaya dokumen tesnya terbaca seperti dokumen produksi sungguhan, bukan "Test 1".
     */
    private function makeCatalogItem(string $productName, string $variantName, string $sku, int $dosStock, int $pcsStock = 0): ProductVariant
    {
        $product = new Product();
        $product->product_name = $productName;
        $product->category_id = $this->categoryId();
        $product->product_unit = json_encode([$this->units['dos']->unit_id, $this->units['pcs']->unit_id]);
        $product->unit_id = $this->units['dos']->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = $variantName;
        $variant->product_variant_sku = $sku.'-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        foreach ([['dos', $dosStock], ['pcs', $pcsStock]] as [$key, $qty]) {
            $s = new ProductStock();
            $s->product_id = $product->product_id;
            $s->product_variant_id = $variant->product_variant_id;
            $s->unit_id = $this->units[$key]->unit_id;
            $s->warehouse_id = 1;
            $s->ps_stock = $qty;
            $s->status = 1;
            $s->save();
        }

        return $variant;
    }

    /** Pasang relasi satuan (1 DOS = $ratio pcs) di atas makeCatalogItem() -- untuk skenario roll-up. */
    private function withLadder(ProductVariant $variant, int $ratio = 12): ProductVariant
    {
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

    /**
     * @param  array<int, array{variant: ProductVariant, dos: int|null, pcs: int|null}>  $lines
     *         null = satuan itu dibiarkan tidak dihitung.
     */
    private function insertOpname(array $lines, bool $isDraft = false, string $notes = 'E2E test', ?string $rollupDecision = null): int
    {
        $items = [];
        foreach ($lines as $line) {
            $v = $line['variant'];
            $items[] = [
                'product_id' => $v->product_id,
                'product_variant_id' => $v->product_variant_id,
                'stod_notes' => $line['notes'] ?? null,
                'units' => [
                    [
                        'unit_id' => $this->units['dos']->unit_id,
                        'system_qty' => $this->currentStock($v, 'dos'),
                        'real_qty' => $line['dos'],
                    ],
                    [
                        'unit_id' => $this->units['pcs']->unit_id,
                        'system_qty' => $this->currentStock($v, 'pcs'),
                        'real_qty' => $line['pcs'],
                    ],
                ],
            ];
        }

        $response = $this->post('/insertStockOpname', array_filter([
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => $notes,
            'is_draft' => $isDraft ? 1 : 0,
            'item' => json_encode($items),
            'rollup_decision' => $rollupDecision,
        ], fn ($v) => $v !== null));
        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        return (int) $response->json('sto_id');
    }

    private function currentStock(ProductVariant $v, string $unitKey): int
    {
        return (int) ProductStock::where('product_variant_id', $v->product_variant_id)
            ->where('unit_id', $this->units[$unitKey]->unit_id)
            ->value('ps_stock');
    }

    private function approve(int $stoId): \Illuminate\Testing\TestResponse
    {
        return $this->post('/accStockOpname', ['sto_id' => $stoId, 'item' => json_encode([])]);
    }

    private function reject(int $stoId): \Illuminate\Testing\TestResponse
    {
        return $this->post('/tolakStockOpname', ['sto_id' => $stoId]);
    }

    /**
     * /generateStockOpname/{id} men-download PDF BINER (DomPDF), bukan HTML -- mencari teks di
     * dalamnya tidak akan pernah cocok. Dipakai di sini murni sebagai asap: endpoint sungguhan
     * harus benar-benar dipanggil dan tidak boleh crash untuk tiap skenario.
     */
    private function assertPdfEndpointWorks(int $stoId): void
    {
        $response = $this->get('/generateStockOpname/'.$stoId);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Render ulang HTML-nya lewat jalur data yang SAMA PERSIS dengan yang dipakai
     * StockController::generateStockOpname() untuk dokumen versi baru (OpnameLineReader ->
     * Backoffice.PDF.Opname) supaya isinya bisa diperiksa dengan strpos/assertStringContainsString
     * seperti test PDF lain di repo ini (lihat StockOpnamePdfHighlightTest::renderOpnameRow()).
     */
    private function renderedHtml(int $stoId): string
    {
        $sto = StockOpname::find($stoId);
        $statusLabel = match ((int) $sto->status) {
            1 => 'Menunggu', 2 => 'Disetujui', 3 => 'Ditolak', default => '-',
        };

        return \Illuminate\Support\Facades\View::make('Backoffice.PDF.Opname', [
            'stockOpname' => ['sto_code' => $sto->sto_code, 'sto_date' => $sto->sto_date, 'sto_notes' => $sto->sto_notes],
            'staff_name' => ['staff_name' => $sto->sto_staff_name ?: '-'],
            'status' => $statusLabel,
            'detail' => (new \App\Support\StockOpname\OpnameLineReader())->read($sto),
        ])->render();
    }

    /** Panggil endpoint sungguhan (asap) DAN kembalikan HTML yang isinya bisa diperiksa. */
    private function pdf(int $stoId): string
    {
        $this->assertPdfEndpointWorks($stoId);

        return $this->renderedHtml($stoId);
    }

    /** Potong satu baris <tr>...</tr> dari HTML PDF berdasarkan teks unik di dalamnya (SKU/nama). */
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

        // Meniru pola SP0071: satu produk, satu satuan cocok, satu berselisih, satu tak dihitung.
        $v = $this->makeCatalogItem('MINYAK REM HIKARI', '16 X 1 LITER', 'MRHK1L', dosStock: 3, pcsStock: 20);

        $stoId = $this->insertOpname([[
            'variant' => $v,
            'dos' => 8,   // 3 -> 8: berselisih +5, harus kuning
            'pcs' => null, // tidak dihitung: tanpa highlight
        ]]);

        $sto = StockOpname::find($stoId);
        $this->assertFalse((bool) $sto->is_old_version);
        $this->assertSame(1, (int) $sto->status, 'dokumen baru langsung berstatus Menunggu');

        $html = $this->pdf($stoId);
        $row = $this->extractRow($html, $v->product_variant_sku);

        $this->assertStringContainsString(self::YELLOW, $row, 'satuan yang berselisih (DOS) harus kuning');
        $this->assertStringContainsString('5 '.$this->units['dos']->unit_short_name, $row, 'selisih tercetak harus 8 - 3 = 5');
        // GitHub #78 follow-up: satuan tak dihitung TIDAK tercetak tanda hubung telanjang (terbaca
        // sebagai data hilang di atas kertas) -- tercetak "cocok dengan sistem" (20 pcs / selisih 0).
        $this->assertStringNotContainsString('- '.$this->units['pcs']->unit_short_name, $row);
        $this->assertStringContainsString('20 '.$this->units['pcs']->unit_short_name, $row, 'satuan pcs tak dihitung tercetak sama dengan stok sistem live (20)');

        // Belum diputuskan -> belum ada apa pun yang dibekukan.
        $line = StockOpnameLine::getLines($stoId)->firstWhere('unit_id', $this->units['dos']->unit_id);
        $this->assertNull($line->sol_system_qty_final, 'dokumen menunggu belum boleh membekukan stok sistem');
        $this->assertNotNull($line->sol_product_name, 'identitas sudah dibekukan sejak publish (insert non-draft)');
    }

    // ================================================================== 2. stok bergerak saat menunggu

    public function test_pending_document_pdf_tracks_live_stock_and_writes_nothing(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeCatalogItem('HIKARI SUPER GREASE', '18 X 450 GR', 'HSG450GR', dosStock: 16, pcsStock: 0);
        $stoId = $this->insertOpname([[
            'variant' => $v, 'dos' => 16, 'pcs' => 0,
        ]]);

        // Cocok persis saat diinput -> hijau.
        $rowBefore = $this->extractRow($this->pdf($stoId), $v->product_variant_sku);
        $this->assertStringContainsString(self::GREEN, $rowBefore);
        $this->assertStringNotContainsString(self::YELLOW, $rowBefore);

        // Stok bergerak karena dokumen/peristiwa LAIN sebelum ini diputuskan.
        ProductStock::where('product_variant_id', $v->product_variant_id)
            ->where('unit_id', $this->units['dos']->unit_id)
            ->update(['ps_stock' => 10]);

        $rowAfter = $this->extractRow($this->pdf($stoId), $v->product_variant_sku);
        $this->assertStringContainsString(self::YELLOW, $rowAfter, 'PDF kedua harus ikut stok live yang baru, bukan snapshot lama');
        $this->assertStringContainsString('6 '.$this->units['dos']->unit_short_name, $rowAfter, '16 - 10 = 6');

        // Dua kali cetak, dua kali tidak ada apa pun tertulis balik.
        $line = StockOpnameLine::where('sto_id', $stoId)->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertNull($line->sol_system_qty_final);
    }

    // ================================================================== 3. disetujui

    public function test_approving_writes_stock_freezes_snapshot_and_stops_tracking_live_movement(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeCatalogItem('RADIATOR COOLANT HIKARI', '4 X 5 LITER', 'RCHK5L', dosStock: 694, pcsStock: 5);
        $stoId = $this->insertOpname([[
            'variant' => $v,
            'dos' => 689, // 694 -> 689: berkurang 5, persis pola SP0071 RCHK5LH
            'pcs' => null, // tidak dihitung
        ]]);

        $logCountBefore = DB::table('log_stocks')->count();
        $this->approve($stoId)->assertOk();

        $sto = StockOpname::find($stoId);
        $this->assertSame(2, (int) $sto->status);
        $this->assertNotNull($sto->sto_acc_name, 'nama pemutus harus dibekukan');
        $this->assertNotNull($sto->sto_decided_at);

        // Satuan yang dihitung: ps_stock ditimpa hasil hitung + log before/after tercatat.
        $dosStock = ProductStock::where('product_variant_id', $v->product_variant_id)
            ->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertSame(689, $dosStock->ps_stock);
        $this->assertSame($logCountBefore + 2, DB::table('log_stocks')->count(), 'satu satuan dihitung -> 2 log (before + after)');
        $this->assertDatabaseHas('log_stocks', ['log_item_id' => $v->product_variant_id, 'unit_id' => $this->units['dos']->unit_id, 'log_jumlah' => 694]);
        $this->assertDatabaseHas('log_stocks', ['log_item_id' => $v->product_variant_id, 'unit_id' => $this->units['dos']->unit_id, 'log_jumlah' => 689]);

        // Satuan yang TIDAK dihitung: ps_stock tidak boleh tersentuh sama sekali (inti #78).
        $pcsStock = ProductStock::where('product_variant_id', $v->product_variant_id)
            ->where('unit_id', $this->units['pcs']->unit_id)->first();
        $this->assertSame(5, $pcsStock->ps_stock);

        // Snapshot beku: stok sistem yang tercatat adalah 694 (sebelum ditimpa), bukan 689.
        $line = StockOpnameLine::where('sto_id', $stoId)->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertSame(694, $line->sol_system_qty_final);

        // Stok terus bergerak sesudahnya -- dokumen yang sudah final tidak boleh ikut bergeser.
        ProductStock::where('ps_id', $dosStock->ps_id)->update(['ps_stock' => 1]);
        $row = $this->extractRow($this->pdf($stoId), $v->product_variant_sku);
        $this->assertStringContainsString('694 '.$this->units['dos']->unit_short_name, $row, 'PDF dokumen final harus tetap 694, bukan 1');
        $this->assertStringContainsString(self::YELLOW, $row);
    }

    // ================================================================== 4. ditolak

    public function test_rejecting_leaves_stock_untouched_but_still_freezes_the_document(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeCatalogItem('HIKARI SIGNATURE HI TEMP GREASE', '6 X 450 GR', 'HKSG450GR', dosStock: 385, pcsStock: 0);
        $stoId = $this->insertOpname([[
            'variant' => $v, 'dos' => 85, 'pcs' => 0, // pola SP0071 HKSG450GR: -300
        ]]);

        $this->reject($stoId)->assertOk();

        $sto = StockOpname::find($stoId);
        $this->assertSame(3, (int) $sto->status);
        $this->assertNotNull($sto->sto_decided_at, 'ditolak pun harus dianggap final dan dibekukan');

        $stock = ProductStock::where('product_variant_id', $v->product_variant_id)
            ->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertSame(385, $stock->ps_stock, 'ditolak tidak boleh menulis stok sama sekali');

        // Stok bergerak sesudahnya -- dokumen yang ditolak juga tidak boleh ikut bergeser.
        ProductStock::where('ps_id', $stock->ps_id)->update(['ps_stock' => 999]);
        $row = $this->extractRow($this->pdf($stoId), $v->product_variant_sku);
        $this->assertStringContainsString('385 '.$this->units['dos']->unit_short_name, $row);
        $this->assertStringContainsString('-300 '.$this->units['dos']->unit_short_name, $row);
        $this->assertStringContainsString(self::YELLOW, $row);
    }

    // ================================================================== 5. semua hijau

    public function test_document_where_every_line_matches_system_is_entirely_green(): void
    {
        $this->actingAsSuperAdminStaff();

        $lines = [
            $this->makeCatalogItem('AIR AKI HIKARI', '12 X 1000 ML', 'AAHK1L', dosStock: 109, pcsStock: 0),
            $this->makeCatalogItem('AIR ZUUR HIKARI', '12 X 1000 ML', 'AZHK1L', dosStock: 131, pcsStock: 0),
        ];

        $stoId = $this->insertOpname([
            ['variant' => $lines[0], 'dos' => 109, 'pcs' => 0],
            ['variant' => $lines[1], 'dos' => 131, 'pcs' => 0],
        ]);

        $html = $this->pdf($stoId);
        foreach ($lines as $v) {
            $row = $this->extractRow($html, $v->product_variant_sku);
            $this->assertStringContainsString(self::GREEN, $row);
            $this->assertStringNotContainsString(self::YELLOW, $row);
        }
    }

    // ================================================================== 6. semua kuning

    public function test_document_where_every_line_differs_is_entirely_yellow(): void
    {
        $this->actingAsSuperAdminStaff();

        $lines = [
            $this->makeCatalogItem('MINYAK REM HIKARI MERAH', '30 X 300 ML', 'MRHK30MLM', dosStock: 4, pcsStock: 0),
            $this->makeCatalogItem('HIKARI GREENTECH GREASE', '18 X 450 GR', 'HGT450GR', dosStock: 149, pcsStock: 0),
        ];

        $stoId = $this->insertOpname([
            ['variant' => $lines[0], 'dos' => 8, 'pcs' => 0],    // +4
            ['variant' => $lines[1], 'dos' => 139, 'pcs' => 0],  // -10
        ]);

        $html = $this->pdf($stoId);
        foreach ($lines as $v) {
            $row = $this->extractRow($html, $v->product_variant_sku);
            $this->assertStringContainsString(self::YELLOW, $row);
            $this->assertStringNotContainsString(self::GREEN, $row);
        }
    }

    // ================================================================== 7. multi-produk gaya SP0071

    public function test_realistic_multi_product_document_colors_each_row_independently(): void
    {
        $this->actingAsSuperAdminStaff();

        $matched = $this->makeCatalogItem('AIR AKI PEGASUS', '12 X 1000 ML', 'AAP1L', dosStock: 126, pcsStock: 0);
        $increased = $this->makeCatalogItem('KUDA INTAN AIR ZUUR', '12 X 1 LITER', 'AZKI1L', dosStock: 0, pcsStock: 0);
        $decreased = $this->makeCatalogItem('RADIATOR COOLANT HIKARI HIJAU', '4 X 5 LITER', 'RCHK5LH', dosStock: 694, pcsStock: 0);
        $uncounted = $this->makeCatalogItem('PERTAMINA TURALIK', '209 LITER', 'PERT209L', dosStock: 1, pcsStock: 0);
        $small = $this->makeCatalogItem('MINYAK REM HIKARI MERAH 1L', '16 X 1 LITER', 'MRHK1LM', dosStock: 3, pcsStock: 0);

        $stoId = $this->insertOpname([
            ['variant' => $matched, 'dos' => 126, 'pcs' => 0],
            ['variant' => $increased, 'dos' => 50, 'pcs' => 0],
            ['variant' => $decreased, 'dos' => 689, 'pcs' => 0],
            ['variant' => $uncounted, 'dos' => null, 'pcs' => null],
            ['variant' => $small, 'dos' => 8, 'pcs' => 0],
        ]);

        $html = $this->pdf($stoId);

        $r1 = $this->extractRow($html, $matched->product_variant_sku);
        $this->assertStringContainsString(self::GREEN, $r1);

        $r2 = $this->extractRow($html, $increased->product_variant_sku);
        $this->assertStringContainsString(self::YELLOW, $r2);
        $this->assertStringContainsString('50 '.$this->units['dos']->unit_short_name, $r2);

        $r3 = $this->extractRow($html, $decreased->product_variant_sku);
        $this->assertStringContainsString(self::YELLOW, $r3);
        $this->assertStringContainsString('-5 '.$this->units['dos']->unit_short_name, $r3);

        $r4 = $this->extractRow($html, $uncounted->product_variant_sku);
        $this->assertStringNotContainsString(self::YELLOW, $r4);
        $this->assertStringNotContainsString(self::GREEN, $r4);

        $r5 = $this->extractRow($html, $small->product_variant_sku);
        $this->assertStringContainsString(self::YELLOW, $r5);
        $this->assertStringContainsString('5 '.$this->units['dos']->unit_short_name, $r5);

        // Lima produk masuk, lima baris tersimpan -- tidak ada yang bocor/hilang/tergabung.
        $this->assertCount(5, StockOpnameLine::getLines($stoId)->pluck('product_variant_id')->unique());
    }

    // ================================================================== 8. draft -> submit

    public function test_draft_has_no_snapshot_until_submitted(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeCatalogItem('SILICONE PEGASUS', '30X400ML', 'SILP400ML', dosStock: 0, pcsStock: 0);
        $stoId = $this->insertOpname([[
            'variant' => $v, 'dos' => 12, 'pcs' => 0,
        ]], isDraft: true);

        $sto = StockOpname::find($stoId);
        $this->assertTrue((bool) $sto->is_draft);
        $this->assertNull($sto->sto_staff_name, 'draft tidak boleh dibekukan');

        $line = StockOpnameLine::getLines($stoId)->first();
        $this->assertNull($line->sol_product_name, 'draft tidak boleh punya snapshot identitas');
        $this->assertSame(12, (int) $line->sol_counted_qty, 'hasil hitung tetap tersimpan walau masih draft');

        $this->post('/submitStockOpname', ['sto_id' => $stoId])->assertStatus(200);

        $sto->refresh();
        $this->assertFalse((bool) $sto->is_draft);
        $this->assertNotNull($sto->sto_staff_name, 'submit harus membekukan identitas');
        $this->assertNotNull(StockOpnameLine::getLines($stoId)->first()->sol_product_name);
    }

    // ================================================================== 9. edit dokumen menunggu

    public function test_editing_a_pending_document_upserts_and_keeps_frozen_name(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeCatalogItem('SAM OIL 4T', '12 X 800 ML', 'SAMOIL4T800ML', dosStock: 6, pcsStock: 0);
        $stoId = $this->insertOpname([[
            'variant' => $v, 'dos' => 6, 'pcs' => 0,
        ]]);

        $frozenName = StockOpnameLine::getLines($stoId)->first()->sol_product_name;

        // Katalog berubah nama setelah dokumen diajukan.
        Product::where('product_id', $v->product_id)->update(['product_name' => 'NAMA BARU SETELAH EDIT']);

        // Staf mengedit hasil hitungnya (mis. mengoreksi ketikan).
        $updateResponse = $this->post('/updateStockOpname', [
            'sto_id' => $stoId,
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Dikoreksi',
            'item' => json_encode([[
                'product_id' => $v->product_id,
                'product_variant_id' => $v->product_variant_id,
                'units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 6, 'real_qty' => 4],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => 0],
                ],
            ]]),
        ]);
        $updateResponse->assertStatus(200);

        $lines = StockOpnameLine::getLines($stoId);
        $this->assertCount(2, $lines, 'edit tidak boleh menggandakan baris (satu per satuan, tetap 2)');

        $dosLine = $lines->firstWhere('unit_id', $this->units['dos']->unit_id);
        $this->assertSame(4, (int) $dosLine->sol_counted_qty, 'hasil hitung yang dikoreksi harus tersimpan');
        $this->assertSame($frozenName, $dosLine->sol_product_name, 'nama yang sudah beku tidak boleh ikut menyegarkan diri');
    }

    // ================================================================== 10. integritas ujung ke ujung

    public function test_end_to_end_data_integrity_across_the_full_lifecycle(): void
    {
        $this->actingAsSuperAdminStaff();

        $a = $this->makeCatalogItem('PEGASUS CHAMOIS', 'STANDARD', 'PGCHS', dosStock: 87, pcsStock: 0);
        $b = $this->makeCatalogItem('SPONGE WASH', '60 X 1', 'SW60X1', dosStock: 35, pcsStock: 0);

        $stoId = $this->insertOpname([
            ['variant' => $a, 'dos' => 87, 'pcs' => 0],  // cocok
            ['variant' => $b, 'dos' => 30, 'pcs' => 0],  // berkurang 5
        ]);

        // Satu-satunya sumber tulis untuk dokumen baru: stock_opname_lines, TIDAK PERNAH
        // stock_opname_details -- itu tabel khusus dokumen lama.
        $this->assertDatabaseMissing('stock_opname_details', ['sto_id' => $stoId]);
        // 2 produk x 2 satuan (DOS + pcs) tiap produk = 4 baris (satu baris per satuan, bukan
        // per produk) -- tapi tetap harus 2 produk yang berbeda, tidak ada yang bocor/tergabung.
        $this->assertSame(4, StockOpnameLine::where('sto_id', $stoId)->count());
        $this->assertSame(2, StockOpnameLine::where('sto_id', $stoId)->pluck('product_variant_id')->unique()->count());

        $this->approve($stoId)->assertOk();

        $stockA = ProductStock::where('product_variant_id', $a->product_variant_id)
            ->where('unit_id', $this->units['dos']->unit_id)->first();
        $stockB = ProductStock::where('product_variant_id', $b->product_variant_id)
            ->where('unit_id', $this->units['dos']->unit_id)->first();
        $this->assertSame(87, $stockA->ps_stock);
        $this->assertSame(30, $stockB->ps_stock);

        // Angka di PDF final harus persis sama dengan yang benar-benar disetujui (tidak ada
        // sumber kebenaran kedua yang bisa berbeda).
        $html = $this->pdf($stoId);
        $rowA = $this->extractRow($html, $a->product_variant_sku);
        $rowB = $this->extractRow($html, $b->product_variant_sku);
        $this->assertStringContainsString('87 '.$this->units['dos']->unit_short_name, $rowA);
        $this->assertStringContainsString('0 '.$this->units['dos']->unit_short_name, $rowA); // selisih 0
        $this->assertStringContainsString(self::GREEN, $rowA);
        $this->assertStringContainsString('-5 '.$this->units['dos']->unit_short_name, $rowB);
        $this->assertStringContainsString(self::YELLOW, $rowB);

        // Setiap satuan yang benar-benar ditimpa punya sepasang log before/after yang bisa dilacak.
        $this->assertDatabaseHas('log_stocks', ['log_item_id' => $a->product_variant_id, 'log_jumlah' => 87]);
        $this->assertDatabaseHas('log_stocks', ['log_item_id' => $b->product_variant_id, 'log_jumlah' => 35]);
        $this->assertDatabaseHas('log_stocks', ['log_item_id' => $b->product_variant_id, 'log_jumlah' => 30]);
    }

    // ================================================================== 11. produk multi-satuan, cuma satuan pertama diisi

    /**
     * Pertanyaan: "apa yang terjadi kalau satu produk punya lebih dari satu satuan, tapi cuma
     * satuan pertama yang diisi?"
     *
     * Di form sungguhan ini AMAN: refreshStockOpname() merender satu input .real-stock per
     * satuan yang produk itu MILIKI stoknya (item.stock.forEach di CreateStockOpname.js), dan
     * insertData() selalu mengirim units[] untuk SETIAP input itu -- diisi atau tidak. Jadi
     * "cuma satuan pertama diisi" di form sungguhan tetap mengirim satuan kedua sebagai
     * {unit_id, real_qty: null}, BUKAN menghilangkannya dari payload sama sekali. Itulah yang
     * dites di sini secara eksplisit, ujung ke ujung.
     *
     * Lihat juga test_realistic_case_where_a_units_entry_is_missing_entirely_from_the_payload()
     * di bawah untuk kasus BERBEDA (dan jauh lebih jarang): satuan yang benar-benar tidak pernah
     * disebut payload sama sekali.
     */
    public function test_product_with_multiple_units_where_only_the_first_is_filled_in(): void
    {
        $this->actingAsSuperAdminStaff();

        // Produk 2 satuan: DOS (stok 20, akan diisi -> berselisih) dan pcs (stok 7, dibiarkan
        // kosong sama sekali).
        $v = $this->makeCatalogItem('HIKARI TYRE POLISH', '12 X 1000 ML', 'HKTP1L', dosStock: 20, pcsStock: 7);

        $stoId = $this->insertOpname([[
            'variant' => $v,
            'dos' => 15, // diisi -> selisih -5
            'pcs' => null, // dibiarkan kosong -- TETAP terkirim sebagai unit_id + real_qty:null
        ]]);

        // Kedua satuan tersimpan sebagai baris terpisah -- satuan kedua bukan hilang, tapi NULL.
        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertCount(2, $lines, 'kedua satuan harus punya baris, satu diisi satu NULL');
        $this->assertSame(15, (int) $lines[$this->units['dos']->unit_id]->sol_counted_qty);
        $this->assertNull($lines[$this->units['pcs']->unit_id]->sol_counted_qty, 'satuan yang dibiarkan kosong harus NULL, bukan hilang atau 0');

        // PDF: satu baris produk, DUA satuan tercetak berdampingan (comma-joined) --
        // satuan yang diisi menunjukkan selisihnya, satuan yang kosong tercetak "cocok dengan
        // sistem" (7 pcs / selisih 0), bukan tanda hubung telanjang, dan warna baris ikut
        // satuan yang benar-benar berselisih (kuning), bukan diam-diam disembunyikan.
        $row = $this->pdf($stoId);
        $row = $this->extractRow($row, $v->product_variant_sku);
        $this->assertStringContainsString(self::YELLOW, $row);
        $this->assertStringContainsString('-5 '.$this->units['dos']->unit_short_name, $row);
        $this->assertStringContainsString('7 '.$this->units['pcs']->unit_short_name, $row, 'satuan yang tak diisi tercetak = stok sistem, bukan "-"');
        $this->assertStringNotContainsString('- '.$this->units['pcs']->unit_short_name, $row);

        // ACC: HANYA satuan yang diisi menimpa ps_stock. Satuan yang tak diisi tidak tersentuh
        // sama sekali -- ini inti GitHub #78, dan berlaku sama walau satuannya "cuma yang kedua".
        $pcsBefore = $this->currentStock($v, 'pcs');
        $this->approve($stoId)->assertOk();

        $this->assertSame(15, $this->currentStock($v, 'dos'), 'satuan yang diisi harus ditimpa hasil hitung');
        $this->assertSame($pcsBefore, $this->currentStock($v, 'pcs'), 'satuan yang tak diisi TIDAK BOLEH tersentuh sama sekali');
        $this->assertDatabaseMissing('log_stocks', ['log_item_id' => $v->product_variant_id, 'unit_id' => $this->units['pcs']->unit_id]);

        // Kedua satuan tetap dibekukan sol_system_qty_final (catatan historis "stok sistem saat
        // diputuskan"), meski cuma satu yang benar-benar ditimpa.
        $frozen = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(20, $frozen[$this->units['dos']->unit_id]->sol_system_qty_final);
        $this->assertSame($pcsBefore, $frozen[$this->units['pcs']->unit_id]->sol_system_qty_final, 'satuan tak diisi tetap dibekukan untuk catatan historis, walau tidak ditimpa');
    }

    /**
     * Kasus BERBEDA dan jauh lebih jarang dari test di atas: satuan kedua tidak muncul di
     * payload SAMA SEKALI (bukan unit_id + real_qty:null, tapi entrinya benar-benar tidak ada).
     * Ini TIDAK bisa terjadi lewat form sungguhan (lihat komentar test sebelumnya) -- cuma
     * mungkin dari pemanggil non-standar (payload mentah/lama, integrasi lain).
     *
     * Dikarakterisasi di sini secara sengaja, bukan dianggap bug: baris yang tidak pernah
     * disebutkan tidak pernah tercipta di stock_opname_lines, jadi TIDAK PERNAH tercetak di PDF
     * sama sekali (beda dari NULL yang tetap tercetak "cocok dengan sistem") dan tidak pernah ikut
     * dibekukan saat approve/tolak. Batasan yang sama persis ada di sistem LAMA (string
     * stod_system/stod_real dibangun dari input DOM yang sama) -- redesign ini tidak
     * memperkenalkan maupun memperbaiki batasan ini, cuma mewarisinya apa adanya.
     */
    public function test_a_units_entry_missing_entirely_from_the_payload_never_creates_a_line(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeCatalogItem('SILICONE OIL PEGASUS', '16 X 100 ML', 'SOP100ML', dosStock: 11, pcsStock: 9);

        // Payload mentah, BUKAN lewat insertOpname() helper -- sengaja cuma menyebut satu satuan.
        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Payload tidak lengkap, sengaja',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $v->product_id,
                'product_variant_id' => $v->product_variant_id,
                'units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 11, 'real_qty' => 8],
                    // satuan pcs SAMA SEKALI tidak disebut di sini.
                ],
            ]]),
        ]);
        $response->assertStatus(200);
        $stoId = (int) $response->json('sto_id');

        $lines = StockOpnameLine::getLines($stoId);
        $this->assertCount(1, $lines, 'satuan yang tidak pernah disebut payload tidak boleh punya baris sama sekali');
        $this->assertSame((int) $this->units['dos']->unit_id, (int) $lines->first()->unit_id);

        // PDF hanya mencetak satu satuan untuk produk ini -- satuan kedua benar-benar tidak
        // tampak, beda dari kasus NULL (yang tetap tampak, dihumanisasi).
        $row = $this->extractRow($this->pdf($stoId), $v->product_variant_sku);
        $this->assertStringNotContainsString($this->units['pcs']->unit_short_name, $row);

        // ACC: satuan yang tidak pernah disebut juga tidak pernah dibekukan -- bukan cuma tidak
        // ditimpa (sama seperti NULL), tapi sol_system_qty_final-nya juga tidak pernah ada baris
        // untuk diisi.
        $this->approve($stoId)->assertOk();
        $this->assertSame(0, StockOpnameLine::getLines($stoId)->where('unit_id', $this->units['pcs']->unit_id)->count());
        $this->assertSame(9, $this->currentStock($v, 'pcs'), 'stok satuan yang tidak disebut tidak berubah');
    }

    // ================================================================== 13. gulung satuan lewat endpoint sungguhan

    /**
     * Contoh persis dari PM, LEWAT /insertStockOpname sungguhan (bukan lifecycle langsung seperti
     * StockOpnameV2LifecycleTest): 1 DOS = 12 pcs, isi 30 pcs -> tersimpan 2 DOS + 6 pcs, dan itu
     * yang tercetak di PDF-nya juga.
     *
     * >>> GANTI KEPUTUSAN (user, 2026-09-06): "setiap kali ada roll up yang terdeteksi,
     * munculkan popup konfirmasinya" -- gulung ini dulu terjadi OTOMATIS tanpa konfirmasi sama
     * sekali (rollUpUnits() dipanggil tanpa syarat dari insertStockOpname()). Sekarang SEMUA
     * gulung, termasuk mengisi satuan terkecil sendirian seperti contoh PM ini, wajib lewat
     * /previewStockOpnameRollup dan rollup_decision='full' -- lihat OpnameLifecycle::
     * computeFullProjectionChanges()'s docblock untuk bug yang mendorong perubahan ini
     * (mengisi 1000 pcs sendirian dulu tergulung tanpa staf pernah melihat popup-nya).
     */
    public function test_insert_endpoint_rolls_up_a_filled_small_unit_when_confirmed(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->withLadder($this->makeCatalogItem('HIKARI SUPER GREASE', '18 X 450 GR', 'HSG450GR', dosStock: 0, pcsStock: 0));

        $stoId = $this->insertOpname([[
            'variant' => $v, 'dos' => null, 'pcs' => 30,
        ]], rollupDecision: 'full');

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(6, (int) $lines[$this->units['pcs']->unit_id]->sol_counted_qty);
        $this->assertSame(2, (int) $lines[$this->units['dos']->unit_id]->sol_counted_qty, 'DOS harus terisi dari kelebihan pcs setelah rollup_decision=full');

        $row = $this->extractRow($this->pdf($stoId), $v->product_variant_sku);
        $this->assertStringContainsString('6 '.$this->units['pcs']->unit_short_name, $row);
        $this->assertStringContainsString('2 '.$this->units['dos']->unit_short_name, $row);
    }

    /** Kembaran test di atas: TANPA rollup_decision='full', tidak ada gulung sama sekali lagi. */
    public function test_insert_endpoint_no_longer_rolls_up_a_filled_small_unit_without_confirmation(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->withLadder($this->makeCatalogItem('HIKARI SUPER GREASE B', '18 X 450 GR', 'HSG450GRB', dosStock: 0, pcsStock: 0));

        $stoId = $this->insertOpname([[
            'variant' => $v, 'dos' => null, 'pcs' => 30,
        ]]);

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(30, (int) $lines[$this->units['pcs']->unit_id]->sol_counted_qty, 'pcs tersimpan apa adanya tanpa konfirmasi');
        $this->assertNull($lines[$this->units['dos']->unit_id]->sol_counted_qty, 'DOS TETAP null tanpa konfirmasi -- tidak ada gulung otomatis lagi');
    }

    /**
     * GANTI keputusan (GitHub #132, 2026-09-03): draft lewat /insertStockOpname TIDAK BOLEH
     * tergulung -- angka mentah dibiarkan apa adanya sampai benar-benar diajukan/terbit. Lihat
     * OpnameLifecycle::rollUpUnits() untuk alasan lengkap (kasus nyata SP0110).
     */
    public function test_insert_endpoint_leaves_a_draft_document_unrolled(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->withLadder($this->makeCatalogItem('MINYAK REM HIKARI', '16 X 1 LITER', 'MRHK1L', dosStock: 0, pcsStock: 0));

        $stoId = $this->insertOpname([[
            'variant' => $v, 'dos' => null, 'pcs' => 30,
        ]], isDraft: true);

        $sto = StockOpname::find($stoId);
        $this->assertTrue((bool) $sto->is_draft);

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(30, (int) $lines[$this->units['pcs']->unit_id]->sol_counted_qty, 'draft tidak boleh tergulung sama sekali');
        $this->assertNull($lines[$this->units['dos']->unit_id]->sol_counted_qty, 'draft tidak boleh tergulung sama sekali');
    }

    /**
     * GANTI keputusan (GitHub #132, 2026-09-03): /updateStockOpname TIDAK BOLEH lagi menggulung
     * ulang -- koreksi sebelum ACC/tolak harus tersimpan persis seperti yang diketik staf. Gulung
     * cuma terjadi sekali, saat dokumen terbit (insert langsung non-draft, atau /submitStockOpname
     * saat draft diajukan) -- lihat StockController::submitStockOpname().
     */
    public function test_update_endpoint_no_longer_rolls_up(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->withLadder($this->makeCatalogItem('AIR AKI PEGASUS', '12 X 1000 ML', 'AAP1L', dosStock: 0, pcsStock: 0));
        $stoId = $this->insertOpname([['variant' => $v, 'dos' => null, 'pcs' => 5]]); // di bawah rasio, belum menggulung

        $this->assertNull(StockOpnameLine::getLines($stoId)->firstWhere('unit_id', $this->units['dos']->unit_id)->sol_counted_qty);

        $updateResponse = $this->post('/updateStockOpname', [
            'sto_id' => $stoId,
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Dikoreksi jadi 30 pcs',
            'item' => json_encode([[
                'product_id' => $v->product_id,
                'product_variant_id' => $v->product_variant_id,
                'units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => 30],
                ],
            ]]),
        ]);
        $updateResponse->assertStatus(200);

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(30, (int) $lines[$this->units['pcs']->unit_id]->sol_counted_qty, 'update tidak boleh menggulung, angka mentah harus tersimpan apa adanya');
        $this->assertNull($lines[$this->units['dos']->unit_id]->sol_counted_qty, 'update tidak boleh menggulung, angka mentah harus tersimpan apa adanya');
    }
}
