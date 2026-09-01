<?php

namespace Tests\Regression;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Fixed 2026-08-02: `ProductionController::accProduction()`'s finished-goods "ladder split" used
 * to look up the `ProductStock` row for the larger unit (`pr_unit_id_1`) with no null-guard:
 *
 *   $ps_depan = ProductStock::where(...)->where("unit_id", $r->pr_unit_id_1)->first();
 *   $ps_depan->ps_stock += $tambah;   // crashed "Attempt to assign property on null" if missing
 *
 * If a product had a `product_relations` unit ladder but was missing the `ProductStock` row at the
 * larger unit, approving any production that output at least `pr_unit_value_2` units crashed with
 * a 500. Confirmed 2026-08-01 while building `tests/Workflow/ProductionUnitConversionFlowTest.php`
 * — see `cdocs/testing/KNOWN_ISSUES.md`'s "Production's finished-goods 'ladder split'..." entry
 * for the full history, including an earlier, related fix (main commit `2d73633`) that wrapped
 * `accProduction` in a `DB::transaction()` so this crash (while still live) no longer corrupted
 * stock.
 *
 * Fix applied 2026-08-02: rather than silently auto-creating the missing row, `accProduction()`
 * now runs a pre-check pass (no mutation) before entering its `DB::transaction()` — if a ladder
 * split would need a `ProductStock` row that doesn't exist yet, it returns
 * `{"status": -3, "header": "Konfirmasi Diperlukan", "message": "...", "missing_stock": [...]}`
 * instead of proceeding. Only once the request is resent with `confirm_create_stock: 1` does
 * `ProductionController::ensureProductStockRow()` (mirroring `ensureSuppliesStockRows()` on the
 * ingredient side) actually create the row at 0 stock and let the approval proceed. This test
 * verifies both steps: the first call is blocked pending confirmation (no mutation at all), the
 * confirmed retry succeeds and lands the ladder split correctly.
 */
class ProductionOutputLadderNullGuardCrashTest extends TestCase
{
    use ActingAsStaff;

    private const OUTPUT_UNIT_ID = 9; // Piece
    private const DOS_UNIT_ID = 7;    // DOS
    private const WAREHOUSE_ID = 1;

    public function test_missing_larger_unit_stock_row_asks_for_confirmation_then_provisions_on_confirm(): void
    {
        $this->actingAsSuperAdminStaff();
        // Wajib di fase2/main: insertProduction()/accProduction() menolak kalau gudang aktif sesi
        // bukan gudang utama ("Pilih gudang utama sebagai gudang aktif"), dan pegasus_testing punya
        // LEBIH DARI SATU gudang utama aktif (id 1 dan 13) -- tanpa ini insert-nya gagal diam-diam
        // (respons 200 berisi error), lalu Production::orderByDesc() mengambil dokumen lama yang
        // sudah di-ACC dan test-nya gagal dengan "-2 sudah diterima/ditolak", bukan pada perilaku
        // yang sebenarnya diuji. Lihat memory pegasus-testing-db-multiwarehouse-drift.
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'Null-Guard Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Null-Guard Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::OUTPUT_UNIT_ID]);
        $product->unit_id = self::OUTPUT_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Null-Guard Regression Variant';
        $variant->product_variant_sku = 'WF-NULLGUARD-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        // Deliberately only a Piece-level ProductStock row — no DOS-level row, reproducing the gap.
        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::OUTPUT_UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = 0;
        $productStock->status = 1;
        $productStock->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Null-Guard Regression Supplies';
        $supplies->supplies_unit = json_encode([self::OUTPUT_UNIT_ID]);
        $supplies->supplies_default_unit = self::OUTPUT_UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::OUTPUT_UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = 1000;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
        $bom->unit_id = self::OUTPUT_UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 2;
        $bomDetail->unit_id = self::OUTPUT_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID; // larger unit — NO ProductStock row exists here
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::OUTPUT_UNIT_ID;
        $relation->pr_unit_value_2 = 12;
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        $pdQty = 20; // >= 12, triggers the ladder-split branch

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Null-guard regression test',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::OUTPUT_UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'bom_detail_qty' => 2,
                'unit_id' => self::OUTPUT_UNIT_ID,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        // First call: no confirmation flag — must be blocked, with no mutation at all.
        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertStatus(200);
        $accResponse->assertJson(['status' => -3, 'header' => 'Konfirmasi Diperlukan']);
        $accResponse->assertJsonFragment([
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
        ]);

        $suppliesStock->refresh();
        $productStock->refresh();
        $production->refresh();
        $this->assertSame(1, (int) $production->status, 'blocked pending confirmation — must not be approved yet');
        $this->assertSame(1000, $suppliesStock->ss_stock, 'blocked pending confirmation — no ingredient deduction yet');
        $this->assertSame(0, $productStock->ps_stock, 'blocked pending confirmation — no output credit yet');
        $this->assertNull(
            ProductStock::where('product_variant_id', $variant->product_variant_id)
                ->where('unit_id', self::DOS_UNIT_ID)
                ->where('status', 1)
                ->first(),
            'blocked pending confirmation — the missing row must not be created yet either'
        );

        // Second call: with confirm_create_stock=1 — now it actually proceeds.
        $accResponse = $this->post('/accProduction', [
            'production_id' => $production->production_id,
            'confirm_create_stock' => 1,
        ]);
        $accResponse->assertStatus(200);

        $suppliesStock->refresh();
        $productStock->refresh();
        $production->refresh();
        $this->assertSame(2, (int) $production->status, 'approval succeeds once confirmed');

        // 20 pieces, 12-per-DOS ladder: floor(20/12)=1 DOS, remainder 8 pieces.
        $this->assertSame(8, $productStock->ps_stock, 'the remainder lands on the pre-existing Piece-level row');

        $dosStock = ProductStock::where('product_variant_id', $variant->product_variant_id)
            ->where('unit_id', self::DOS_UNIT_ID)
            ->where('status', 1)
            ->first();
        $this->assertNotNull($dosStock, 'the previously-missing DOS-level row is auto-provisioned instead of crashing');
        $this->assertSame(1, $dosStock->ps_stock, 'the DOS-level row is credited correctly once provisioned');

        // 40 (20 * bom_detail_qty 2) ingredient units consumed, matching the un-crashed happy path.
        $this->assertSame(960, $suppliesStock->ss_stock);

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'log_jumlah' => 1,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::OUTPUT_UNIT_ID,
            'log_jumlah' => 8,
        ]);
    }
}
