<?php

namespace Tests\DatabaseTransaction;

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
 * Extends the Phase 3 pilot (tests/Workflow/ProductionFlowTest.php,
 * tests/Workflow/ProductionUnitConversionFlowTest.php,
 * cdocs/testing/workflows/PRODUCTION_FLOW.md).
 *
 * UPDATED 2026-08-01 after merging `main` commit `2d73633` into this branch: `accProduction` used
 * to have no `DB::transaction()` at all (same gap shape as Purchase Order's `accPO`), so a
 * mid-request failure left earlier, already-applied stock mutations permanently committed while
 * the production stayed pending — a real incident (`PR0258`, see `docs/production-acc-stock-safety.md`)
 * another developer hit and fixed independently. This test previously characterized THAT broken
 * behavior on purpose; it now asserts the fixed, atomic behavior instead. See
 * `cdocs/testing/KNOWN_ISSUES.md`'s finished-goods null-guard entry for the full history.
 *
 * Trigger (still an open, separate bug — this fix didn't touch it, only contained its blast
 * radius): the confirmed, currently-active crash documented in `KNOWN_ISSUES.md` ("Production's
 * finished-goods 'ladder split' has no null-guard on the larger-unit stock row") —
 * `ProductionController.php`'s finished-goods increment crashes with "Attempt to assign property
 * on null" when a product has a `product_relations` ladder but is missing the `ProductStock` row
 * at the larger unit. Two production items are approved in ONE `accProduction` call: item A is a
 * plain, fully-valid production; item B has that missing row, so its output credit crashes.
 * Because the whole mutation section is now wrapped in `DB::beginTransaction()`/`rollBack()`, item
 * B's crash rolls back item A's already-applied ingredient deduction and output credit too —
 * nothing is left half-done, even though the underlying crash/500 still happens.
 */
class ProductionApprovalAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private const OUTPUT_UNIT_ID = 9; // Piece
    private const DOS_UNIT_ID = 7;    // DOS
    private const WAREHOUSE_ID = 1;

    /** @return array{variant: ProductVariant, productStock: ProductStock, bom: Bom, supplies: Supplies, suppliesStock: SuppliesStock} */
    private function createSimpleFixture(string $label): array
    {
        $category = new Category();
        $category->category_name = "Atomicity Test Category $label";
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = "Atomicity Test Product $label";
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::OUTPUT_UNIT_ID]);
        $product->unit_id = self::OUTPUT_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = "Atomicity Test Variant $label";
        $variant->product_variant_sku = 'WF-ATOMIC-'.$label.'-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::OUTPUT_UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = 0;
        $productStock->status = 1;
        $productStock->save();

        $supplies = new Supplies();
        $supplies->supplies_name = "Atomicity Test Supplies $label";
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

        return compact('variant', 'productStock', 'bom', 'supplies', 'suppliesStock');
    }

    public function test_a_mid_request_output_crash_now_rolls_back_both_items_entirely(): void
    {
        $this->actingAsSuperAdminStaff();

        $itemA = $this->createSimpleFixture('A');
        $itemB = $this->createSimpleFixture('B');

        // Item B gets a product_relations ladder (12 pieces = 1 DOS) but deliberately NO
        // ProductStock row at the DOS level — reproducing the confirmed null-guard gap.
        $relation = new ProductRelation();
        $relation->product_variant_id = $itemB['variant']->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::OUTPUT_UNIT_ID;
        $relation->pr_unit_value_2 = 12;
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        $pdQtyA = 10;
        $pdQtyB = 20; // >= 12, triggers the crashing ladder-split branch for item B

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'DB transaction atomicity test production',
            'detail' => json_encode([
                [
                    'bom_id' => $itemA['bom']->bom_id,
                    'product_variant_id' => $itemA['variant']->product_variant_id,
                    'pd_qty' => $pdQtyA,
                    'unit_id' => self::OUTPUT_UNIT_ID,
                ],
                [
                    'bom_id' => $itemB['bom']->bom_id,
                    'product_variant_id' => $itemB['variant']->product_variant_id,
                    'pd_qty' => $pdQtyB,
                    'unit_id' => self::OUTPUT_UNIT_ID,
                ],
            ]),
            'list_bahan' => json_encode([
                ['supplies_id' => $itemA['supplies']->supplies_id, 'bom_detail_qty' => 2, 'unit_id' => self::OUTPUT_UNIT_ID],
                ['supplies_id' => $itemB['supplies']->supplies_id, 'bom_detail_qty' => 2, 'unit_id' => self::OUTPUT_UNIT_ID],
            ]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        $logCountBefore = DB::table('log_stocks')->count();

        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);

        // The crash itself still happens — this fix didn't touch the null-guard, only wrapped the
        // mutation section in a transaction around it.
        $accResponse->assertStatus(500);

        // FIXED: neither item's ingredients are deducted anymore — DB::rollBack() reverts item
        // A's already-applied mutation too, instead of leaving it permanently committed.
        $itemA['suppliesStock']->refresh();
        $itemB['suppliesStock']->refresh();
        $this->assertSame(1000, $itemA['suppliesStock']->ss_stock, "item A's ingredient deduction must be rolled back along with item B's failure");
        $this->assertSame(1000, $itemB['suppliesStock']->ss_stock, "item B's ingredient deduction (which never even reaches output) must also be rolled back");

        // FIXED: item A's finished goods are no longer credited either — the whole request is
        // now all-or-nothing.
        $itemA['productStock']->refresh();
        $this->assertSame(0, $itemA['productStock']->ps_stock, "item A's output credit must be rolled back, not left applied");

        $itemB['productStock']->refresh();
        $this->assertSame(0, $itemB['productStock']->ps_stock, "item B's output was never credited to begin with");

        // The production itself is left pending — same as before, just now for the right reason
        // (a clean rollback, not a half-applied mutation).
        $production->refresh();
        $this->assertSame(1, (int) $production->status);
        $this->assertNull($production->acc_by);

        // FIXED: no new log_stocks rows survive the rollback at all.
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'every log_stocks row written during the failed attempt must be rolled back too');
    }
}
