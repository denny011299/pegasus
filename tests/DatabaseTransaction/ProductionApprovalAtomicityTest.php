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
 * History: `accProduction` used to have no `DB::transaction()` at all — a real incident (`PR0258`)
 * another developer hit and fixed on `main` (commit `2d73633`, merged 2026-08-01). This test then
 * used the finished-goods "ladder split" null-guard crash (`KNOWN_ISSUES.md`) as its trigger to
 * prove a mid-request failure rolled back cleanly instead of leaving item A's mutations permanently
 * committed while item B crashed.
 *
 * UPDATED 2026-08-02: that null-guard was itself fixed (`ProductionController::ensureProductStockRow()`
 * now auto-provisions the missing larger-unit `ProductStock` row instead of crashing), which removes
 * this test's only trigger — item B no longer crashes, so there's nothing left to roll back. Rewritten
 * to verify the resulting positive-path guarantee instead: the exact scenario that used to crash
 * (two items in one `accProduction` call, one of them hitting a previously-missing ladder row) now
 * succeeds cleanly for BOTH items in a single request. Kept in `tests/DatabaseTransaction/` rather
 * than moved, since it's still meaningfully about "what happens across a multi-item accProduction
 * call" — just a success case now instead of a rollback case.
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

    public function test_a_multi_item_request_with_a_previously_crashing_ladder_item_now_succeeds_atomically(): void
    {
        $this->actingAsSuperAdminStaff();

        $itemA = $this->createSimpleFixture('A');
        $itemB = $this->createSimpleFixture('B');

        // Item B gets a product_relations ladder (12 pieces = 1 DOS) but deliberately NO
        // ProductStock row at the DOS level — this used to crash before ensureProductStockRow().
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
        $pdQtyB = 20; // >= 12, exercises the ladder-split branch for item B

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

        // confirm_create_stock=1: the missing-row confirmation flow itself (added 2026-08-02) is
        // its own dedicated test, see ProductionOutputLadderNullGuardCrashTest — this test is about
        // multi-item atomicity, so it confirms upfront to reach the actual mutation.
        $accResponse = $this->post('/accProduction', [
            'production_id' => $production->production_id,
            'confirm_create_stock' => 1,
        ]);

        // FIXED: no crash at all now — both items succeed in the same request.
        $accResponse->assertStatus(200);

        // Item A: plain production, 10 pieces produced, 20 (10*2) ingredient units consumed.
        $itemA['suppliesStock']->refresh();
        $itemA['productStock']->refresh();
        $this->assertSame(980, $itemA['suppliesStock']->ss_stock, "item A's ingredient deduction applies normally");
        $this->assertSame(10, $itemA['productStock']->ps_stock, "item A's output is credited normally");

        // Item B: ladder split, 20 pieces => floor(20/12)=1 DOS + 8 Piece remainder; 40 (20*2)
        // ingredient units consumed. The previously-missing DOS-level row is auto-provisioned.
        $itemB['suppliesStock']->refresh();
        $itemB['productStock']->refresh();
        $this->assertSame(960, $itemB['suppliesStock']->ss_stock, "item B's ingredient deduction applies normally");
        $this->assertSame(8, $itemB['productStock']->ps_stock, "item B's Piece-level remainder is credited correctly");

        $dosStock = ProductStock::where('product_variant_id', $itemB['variant']->product_variant_id)
            ->where('unit_id', self::DOS_UNIT_ID)
            ->where('status', 1)
            ->first();
        $this->assertNotNull($dosStock, "item B's DOS-level row is auto-provisioned instead of crashing");
        $this->assertSame(1, $dosStock->ps_stock, "item B's DOS-level row is credited correctly");

        $production->refresh();
        $this->assertSame(2, (int) $production->status, 'the production is fully approved, not left pending');
        $this->assertNotNull($production->acc_by);
    }
}
