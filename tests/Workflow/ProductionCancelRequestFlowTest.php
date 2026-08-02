<?php

namespace Tests\Workflow;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PRODUCTION_FLOW.md's "Cancel-request sub-flow" section for the
 * fully-traced flow this asserts against. Cancelling an already-approved production is its own
 * request-then-approve mini-workflow (`deleteProduction` -> `tolakDeleteProduction`/
 * `accDeleteProduction`), mirroring the shape of approve/decline but for reversal. Uses the same
 * fresh, single-ingredient, no-unit-conversion fixture shape as `ProductionFlowTest`'s pilot — the
 * atomicity gap in `accDeleteProduction` has its own dedicated test,
 * tests/DatabaseTransaction/ProductionCancelRequestAtomicityTest.php.
 */
class ProductionCancelRequestFlowTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // Piece
    private const WAREHOUSE_ID = 1;
    private const BOM_DETAIL_QTY = 2;
    private const STARTING_SUPPLIES_STOCK = 1000;

    /** @return array{variant: ProductVariant, productStock: ProductStock, supplies: Supplies, suppliesStock: SuppliesStock, bom: Bom} */
    private function createFixture(): array
    {
        $category = new Category();
        $category->category_name = 'Cancel Request Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Cancel Request Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Cancel Request Test Variant';
        $variant->product_variant_sku = 'WF-CANCELREQ-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = 0;
        $productStock->status = 1;
        $productStock->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Cancel Request Test Supplies';
        $supplies->supplies_unit = json_encode([self::UNIT_ID]);
        $supplies->supplies_default_unit = self::UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = self::STARTING_SUPPLIES_STOCK;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
        $bom->unit_id = self::UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = self::BOM_DETAIL_QTY;
        $bomDetail->unit_id = self::UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        return compact('variant', 'productStock', 'supplies', 'suppliesStock', 'bom');
    }

    private function insertAndApprove(array $fx, int $pdQty): int
    {
        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Cancel-request test production',
            'detail' => json_encode([[
                'bom_id' => $fx['bom']->bom_id,
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $fx['supplies']->supplies_id,
                'bom_detail_qty' => self::BOM_DETAIL_QTY,
                'unit_id' => self::UNIT_ID,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        $this->post('/accProduction', ['production_id' => $production->production_id])->assertStatus(200);

        return (int) $production->production_id;
    }

    public function test_full_cancel_request_round_trip_restores_stock_exactly(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture();
        $pdQty = 10;
        $consumed = $pdQty * self::BOM_DETAIL_QTY;

        $productionId = $this->insertAndApprove($fx, $pdQty);

        $fx['productStock']->refresh();
        $fx['suppliesStock']->refresh();
        $this->assertSame($pdQty, $fx['productStock']->ps_stock, 'approval credited the produced qty');
        $this->assertSame(self::STARTING_SUPPLIES_STOCK - $consumed, $fx['suppliesStock']->ss_stock, 'approval deducted the consumed ingredient qty');

        // Request cancellation.
        $this->post('/deleteProduction', [
            'production_id' => $productionId,
            'delete_reason' => 'Workflow test cancel request',
        ])->assertStatus(200);

        $production = Production::findOrFail($productionId);
        $this->assertSame(4, (int) $production->status, 'a cancel request sets status to 4 (awaiting cancellation)');
        $this->assertNotNull($production->cancel_requested_by);

        // Reject the cancel request — production stays approved, nothing about stock changes.
        $this->post('/tolakDeleteProduction', ['production_id' => $productionId])->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status, 'rejecting the cancel request restores status to 2 (approved)');
        $this->assertNull($production->notes);

        $fx['productStock']->refresh();
        $fx['suppliesStock']->refresh();
        $this->assertSame($pdQty, $fx['productStock']->ps_stock, 'rejecting a cancel request must not touch stock at all');
        $this->assertSame(self::STARTING_SUPPLIES_STOCK - $consumed, $fx['suppliesStock']->ss_stock);

        // Request cancellation again, this time approve it.
        $this->post('/deleteProduction', [
            'production_id' => $productionId,
            'delete_reason' => 'Workflow test cancel request, take 2',
        ])->assertStatus(200);
        $production->refresh();
        $this->assertSame(4, (int) $production->status);

        $this->post('/accDeleteProduction', ['production_id' => $productionId])->assertStatus(200);

        $production->refresh();
        $this->assertSame(3, (int) $production->status, 'approving the cancel request sets status to 3 (cancelled)');

        $fx['productStock']->refresh();
        $fx['suppliesStock']->refresh();
        $this->assertSame(0, $fx['productStock']->ps_stock, 'the produced qty is subtracted back out exactly');
        $this->assertSame(self::STARTING_SUPPLIES_STOCK, $fx['suppliesStock']->ss_stock, 'the consumed ingredient qty is added back exactly, restoring the pre-approval state');
    }

    public function test_approving_a_cancel_request_is_rejected_if_produced_stock_was_already_consumed_elsewhere(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture();
        $pdQty = 10;

        $productionId = $this->insertAndApprove($fx, $pdQty);

        // Simulate the produced goods being sold/consumed elsewhere before the cancel request is
        // approved — only 4 of the 10 produced units are still in stock.
        $fx['productStock']->refresh();
        $fx['productStock']->ps_stock = 4;
        $fx['productStock']->save();

        $this->post('/deleteProduction', [
            'production_id' => $productionId,
            'delete_reason' => 'Workflow test cancel request',
        ])->assertStatus(200);

        $response = $this->post('/accDeleteProduction', ['production_id' => $productionId]);
        $response->assertJson(['status' => -1]);

        $production = Production::findOrFail($productionId);
        $this->assertSame(4, (int) $production->status, 'a rejected cancel-approval must leave the production still awaiting cancellation, not cancelled');

        $fx['productStock']->refresh();
        $fx['suppliesStock']->refresh();
        $this->assertSame(4, $fx['productStock']->ps_stock, 'a rejected cancel-approval must not touch stock at all');
        $this->assertSame(self::STARTING_SUPPLIES_STOCK - ($pdQty * self::BOM_DETAIL_QTY), $fx['suppliesStock']->ss_stock);
    }
}
