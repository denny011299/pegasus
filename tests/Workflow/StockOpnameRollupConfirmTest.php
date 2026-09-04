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
 * adanya). Tapi user secara eksplisit minta ini ditawarkan sebagai PILIHAN eksplisit di titik
 * dokumen terbit (insertStockOpname is_draft=0, atau submitStockOpname) -- bukan otomatis --
 * lewat popup konfirmasi. Baca App\Support\StockOpname\OpnameLifecycle::detectRollupOpportunities()/
 * rollUpUnitsFull() untuk desainnya.
 *
 * Ketiga skenario di sini:
 *  1. Ada peluang gulung (Dos diisi, Piece TIDAK, existing Piece > 1 ratio Dos) -> endpoint
 *     membalas status=2 + daftar produk, TIDAK menulis apa pun ke stock_opname_lines yang
 *     mengubah publish/rollup, dokumen sudah tersimpan tapi belum terbit.
 *  2. Staf menjawab "Batal" (rollup_decision=skip) -> perilaku SEBELUMNYA (parsial, aman) --
 *     Piece tetap NULL/tidak tersentuh, sama seperti sebelum fitur ini ada.
 *  3. Staf menjawab "Lanjut" (rollup_decision=full) -> Piece ikut dilipat ke Dos.
 *  4. Gudang ECERAN: tidak pernah ditawari popup sama sekali (rollUpUnits() pun sudah
 *     mengecualikan eceran, lihat OpnameLifecycle::isRetailWarehouse()).
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

    /** Insert dokumen: cuma Dos yang diisi ($dosQty), Piece dikirim sebagai unit_id + real_qty:null. */
    private function insertDosOnlyOpname(ProductVariant $v, int $dosQty, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/insertStockOpname', array_merge([
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Rollup confirm test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $v->product_id,
                'product_variant_id' => $v->product_variant_id,
                'units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => $dosQty],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                ],
            ]]),
        ], $extra));
    }

    public function test_direct_publish_detects_and_offers_rollup_confirmation(): void
    {
        $this->actingAsSuperAdminStaff();

        // Meniru laporan bug: 90 Dos, 104 Piece -- staf cuma mengoreksi Dos jadi 93.
        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104);

        $response = $this->insertDosOnlyOpname($v, 93);
        $response->assertStatus(200);
        $response->assertJson(['status' => 2]);
        $stoId = (int) $response->json('sto_id');
        $this->assertNotSame(0, $stoId);
        $this->assertSame(1, count($response->json('rollup_candidates')));

        // Dokumen SUDAH tersimpan (baris ditulis) tapi BELUM terbit -- identitas belum dibekukan
        // (publish() belum jalan), sesuai desain "belum ada keputusan staf".
        $sto = StockOpname::find($stoId);
        $this->assertNull($sto->sto_staff_name, 'publish() belum boleh jalan sebelum staf menjawab popup');
        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(93, (int) $lines[$this->units['dos']->unit_id]->sol_counted_qty);
        $this->assertNull($lines[$this->units['pcs']->unit_id]->sol_counted_qty);
    }

    public function test_answering_batal_keeps_the_old_safe_partial_behavior(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104);
        $first = $this->insertDosOnlyOpname($v, 93);
        $stoId = (int) $first->json('sto_id');

        // Panggilan kedua: staf klik "Batal" -> rollup_decision=skip, kirim ulang sto_id yang sama.
        $second = $this->insertDosOnlyOpname($v, 93, [
            'rollup_decision' => 'skip',
            'sto_id' => $stoId,
        ]);
        $second->assertStatus(200);
        $second->assertJson(['status' => 1, 'sto_id' => $stoId]);

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(93, (int) $lines[$this->units['dos']->unit_id]->sol_counted_qty, 'Dos tetap 93, tidak berubah');
        $this->assertNull($lines[$this->units['pcs']->unit_id]->sol_counted_qty, 'Piece TETAP null -- perilaku lama, tidak digulung');

        $sto = StockOpname::find($stoId);
        $this->assertNotNull($sto->sto_staff_name, 'publish() harus jalan setelah keputusan dijawab');

        // ACC: cuma Dos yang tertulis ke ps_stock, Piece tidak tersentuh -- persis laporan bug user.
        $accResp = $this->post('/accStockOpname', ['sto_id' => $stoId, 'item' => json_encode([['dummy' => 1]])]);
        $accResp->assertOk();
        $this->assertSame(93, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['dos']->unit_id)->value('ps_stock'));
        $this->assertSame(104, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['pcs']->unit_id)->value('ps_stock'));
    }

    public function test_answering_lanjut_rolls_untouched_piece_into_dos(): void
    {
        $this->actingAsSuperAdminStaff();

        // 1 DOS = 12 pcs. 93 Dos (diisi) + 104 pcs (existing, tidak diisi) = 1116 + 104 = 1220 pcs
        // = 101 DOS + 8 pcs kanonik.
        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104, ratio: 12);
        $first = $this->insertDosOnlyOpname($v, 93);
        $stoId = (int) $first->json('sto_id');

        $second = $this->insertDosOnlyOpname($v, 93, [
            'rollup_decision' => 'full',
            'sto_id' => $stoId,
        ]);
        $second->assertStatus(200);
        $second->assertJson(['status' => 1, 'sto_id' => $stoId]);

        $lines = StockOpnameLine::getLines($stoId)->keyBy('unit_id');
        $this->assertSame(101, (int) $lines[$this->units['dos']->unit_id]->sol_counted_qty, 'Dos ikut menyerap kelebihan Piece');
        $this->assertSame(8, (int) $lines[$this->units['pcs']->unit_id]->sol_counted_qty, 'Piece jadi sisa kanonik, bukan NULL lagi');

        $this->post('/accStockOpname', ['sto_id' => $stoId, 'item' => json_encode([['dummy' => 1]])])->assertOk();
        $this->assertSame(101, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['dos']->unit_id)->value('ps_stock'));
        $this->assertSame(8, (int) ProductStock::where('product_variant_id', $v->product_variant_id)->where('unit_id', $this->units['pcs']->unit_id)->value('ps_stock'));
    }

    public function test_submit_stock_opname_offers_the_same_confirmation_for_a_draft(): void
    {
        $this->actingAsSuperAdminStaff();

        $v = $this->makeLadderedItem(dosStock: 90, pcsStock: 104);
        $draft = $this->insertDosOnlyOpname($v, 93, ['is_draft' => 1]);
        $draft->assertStatus(200);
        $draft->assertJson(['status' => 1]);
        $stoId = (int) $draft->json('sto_id');

        $first = $this->post('/submitStockOpname', ['sto_id' => $stoId]);
        $first->assertStatus(200);
        $first->assertJson(['status' => 2]);
        $this->assertTrue((bool) StockOpname::find($stoId)->is_draft, 'belum ada keputusan -- draft belum diajukan');

        $second = $this->post('/submitStockOpname', ['sto_id' => $stoId, 'rollup_decision' => 'full']);
        $second->assertStatus(200);
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

        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Rollup confirm test (eceran)',
            'is_draft' => 0,
            'warehouse_id' => $warehouse->id,
            'item' => json_encode([[
                'product_id' => $v->product_id,
                'product_variant_id' => $v->product_variant_id,
                'units' => [
                    ['unit_id' => $this->units['dos']->unit_id, 'system_qty' => 0, 'real_qty' => 93],
                    ['unit_id' => $this->units['pcs']->unit_id, 'system_qty' => 0, 'real_qty' => null],
                ],
            ]]),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 1], 'gudang eceran tidak pernah ditawari popup gulung');
    }
}
