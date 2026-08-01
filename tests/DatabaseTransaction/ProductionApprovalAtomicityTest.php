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
 * cdocs/testing/workflows/PRODUCTION_FLOW.md). `accProduction` has no `DB::transaction()`
 * (same gap shape as Purchase Order's `accPO`), so this documents exactly how far a mid-request
 * failure gets rather than assuming the whole request is all-or-nothing.
 *
 * Trigger: the confirmed, currently-active bug documented in KNOWN_ISSUES.md ("Production's
 * finished-goods 'ladder split' has no null-guard on the larger-unit stock row") —
 * `ProductionController.php:846-851` crashes with "Attempt to assign property on null" when a
 * product has a `product_relations` ladder but is missing the ProductStock row at the larger
 * unit. Two production items are approved in ONE `accProduction` call: item A is a plain,
 * fully-valid production; item B has that missing row. Because the ingredient deduction for
 * BOTH items happens in one aggregated loop that runs to completion BEFORE the finished-goods
 * loop even starts, both items' raw materials are consumed and permanently committed —
 * regardless of item order — while only item A's finished goods are actually credited before
 * item B's crash. Item B's production is left having consumed its ingredients for nothing.
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

    public function test_a_mid_request_output_crash_leaves_both_items_ingredients_consumed_but_only_one_credited(): void
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

        // Documents current behavior: an uncaught error, not a clean {status:-1, ...} response.
        $accResponse->assertStatus(500);

        // Both items' ingredients are deducted and permanently committed — the ingredient loop
        // runs to completion for ALL aggregated requirements before the output loop even starts.
        $itemA['suppliesStock']->refresh();
        $itemB['suppliesStock']->refresh();
        $this->assertSame(1000 - ($pdQtyA * 2), $itemA['suppliesStock']->ss_stock, "item A's ingredient is deducted despite item B's later crash");
        $this->assertSame(1000 - ($pdQtyB * 2), $itemB['suppliesStock']->ss_stock, "item B's ingredient is ALSO deducted, even though its output crashes and is never credited");

        // Item A's finished goods (processed first in the output loop) are fully credited.
        $itemA['productStock']->refresh();
        $this->assertSame($pdQtyA, $itemA['productStock']->ps_stock, "item A's output is credited before item B's crash halts the loop");

        // Item B's finished goods are never credited — its ingredients were consumed for nothing.
        $itemB['productStock']->refresh();
        $this->assertSame(0, $itemB['productStock']->ps_stock, "item B's output is never credited — the crash happens before its own stock write");

        // The production itself is left stuck: neither approved nor rejected.
        $production->refresh();
        $this->assertSame(1, (int) $production->status, 'the production is left pending, despite having already consumed real ingredient stock for both items');
        $this->assertNull($production->acc_by);

        // Log-wise: both ingredient deductions get their log_stocks rows; only item A's output does.
        $this->assertGreaterThanOrEqual($logCountBefore + 3, DB::table('log_stocks')->count());
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $itemA['variant']->product_variant_id,
            'log_jumlah' => $pdQtyA,
        ]);
        $this->assertDatabaseMissing('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $itemB['variant']->product_variant_id,
        ]);
    }
}
