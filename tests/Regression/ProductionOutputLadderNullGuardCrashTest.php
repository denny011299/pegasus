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
 * Bug: `ProductionController::accProduction()`'s finished-goods "ladder split" looks up the
 * `ProductStock` row for the larger unit (`pr_unit_id_1`) with no null-guard:
 *
 *   $ps_depan = ProductStock::where(...)->where("unit_id", $r->pr_unit_id_1)->first();
 *   $ps_depan->ps_stock += $tambah;   // crashes "Attempt to assign property on null" if missing
 *
 * If a product has a `product_relations` unit ladder but is missing the `ProductStock` row at the
 * larger unit, approving any production that outputs at least `pr_unit_value_2` units crashes with
 * a 500. Confirmed 2026-08-01 while building `tests/Workflow/ProductionUnitConversionFlowTest.php`
 * — see `cdocs/testing/KNOWN_ISSUES.md`'s "Production's finished-goods 'ladder split'..." entry
 * for the full history, including a related fix (main commit `2d73633`, merged into this branch)
 * that wraps `accProduction` in a `DB::transaction()`. That fix did NOT touch this null-guard —
 * the crash itself is still live — but it DID change the consequence: the crash now cleanly rolls
 * back instead of leaving stock permanently corrupted. This test characterizes both facts at once:
 * the crash still happens (deliberately not fixed here), but it's now safely contained.
 */
class ProductionOutputLadderNullGuardCrashTest extends TestCase
{
    use ActingAsStaff;

    private const OUTPUT_UNIT_ID = 9; // Piece
    private const DOS_UNIT_ID = 7;    // DOS
    private const WAREHOUSE_ID = 1;

    public function test_missing_larger_unit_stock_row_crashes_but_no_longer_corrupts_stock(): void
    {
        $this->actingAsSuperAdminStaff();

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

        $pdQty = 20; // >= 12, triggers the crashing ladder-split branch

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

        $logCountBefore = DB::table('log_stocks')->count();

        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);

        // The crash is still live — this test does not fix it, only characterizes it.
        $accResponse->assertStatus(500);

        // But the DB::transaction() added in main commit 2d73633 (merged into this branch) means
        // the crash no longer corrupts stock: everything rolls back cleanly.
        $suppliesStock->refresh();
        $productStock->refresh();
        $production->refresh();
        $this->assertSame(1000, $suppliesStock->ss_stock, 'the ingredient deduction must be rolled back, not left applied');
        $this->assertSame(0, $productStock->ps_stock, 'no output credit should survive the rollback');
        $this->assertSame(1, (int) $production->status, 'the production is left pending, not partially approved');
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'no log_stocks rows should survive the rollback');
    }
}
