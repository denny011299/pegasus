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
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PRODUCTION_FLOW.md for the fully-traced flow this asserts against.
 * Uses a fully fresh fixture (own product/supplies/BOM) rather than real seeded data — every real
 * BOM in the seed snapshot has 4-5 ingredient lines and near-certainly hits the unit-conversion or
 * "dos/pack" special-case math, which are their own dedicated test targets, not this pilot's scope.
 */
class ProductionFlowTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // "Piece" — real, active, no conversion relations needed
    private const WAREHOUSE_ID = 1; // Gudang Pusat (main), seeded 2026-08-01
    private const BOM_QTY = 1;
    private const BOM_DETAIL_QTY = 2; // 2 units of raw material per 1 unit of BOM output
    private const STARTING_SUPPLIES_STOCK = 1000;

    /**
     * @return array{variant: ProductVariant, productStock: ProductStock, supplies: Supplies, suppliesStock: SuppliesStock, bom: Bom, bomDetail: BomDetail}
     */
    private function createFixture(): array
    {
        $category = new Category();
        $category->category_name = 'Workflow Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Workflow Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Workflow Test Variant';
        $variant->product_variant_sku = 'WF-PROD-'.uniqid();
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
        $supplies->supplies_name = 'Workflow Test Supplies';
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

        // NOTE: boms.product_id actually stores a product_variant_id — see the flow doc.
        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = self::BOM_QTY;
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

        return compact('variant', 'productStock', 'supplies', 'suppliesStock', 'bom', 'bomDetail');
    }

    public function test_insert_then_approve_moves_stock_and_writes_log(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture();
        $pdQty = 10;
        $suppliesNeeded = $pdQty * self::BOM_DETAIL_QTY; // batchCount(10) * bom_detail_qty(2) = 20
        $logCountBefore = DB::table('log_stocks')->count();

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Workflow test production',
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
        $insertResponse->assertJson(['status' => 1]);

        $production = Production::orderByDesc('production_id')->firstOrFail();
        $this->assertSame(1, (int) $production->status, 'a freshly inserted production should be pending approval');
        $this->assertDatabaseHas('production_details', [
            'production_id' => $production->production_id,
            'product_variant_id' => $fx['variant']->product_variant_id,
            'pd_qty' => $pdQty,
            'bom_id' => $fx['bom']->bom_id,
        ]);

        $fx['productStock']->refresh();
        $fx['suppliesStock']->refresh();
        $this->assertSame(0, $fx['productStock']->ps_stock, 'inserting a production must not touch output stock before approval');
        $this->assertSame(self::STARTING_SUPPLIES_STOCK, $fx['suppliesStock']->ss_stock, 'inserting a production must not touch ingredient stock before approval');
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'inserting a production must not write a log_stocks row');

        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status, 'approving a production sets status to 2');
        $this->assertNotNull($production->acc_by);

        $fx['productStock']->refresh();
        $fx['suppliesStock']->refresh();
        $this->assertSame($pdQty, $fx['productStock']->ps_stock, 'approval must add the produced qty to output stock');
        $this->assertSame(
            self::STARTING_SUPPLIES_STOCK - $suppliesNeeded,
            $fx['suppliesStock']->ss_stock,
            'approval must deduct the BOM-computed ingredient qty from supplies stock'
        );

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $fx['variant']->product_variant_id,
            'log_jumlah' => $pdQty,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $fx['supplies']->supplies_id,
            'log_jumlah' => $suppliesNeeded,
        ]);
    }

    public function test_approve_is_rejected_cleanly_when_ingredient_stock_is_insufficient(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture();
        $pdQty = 10;

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Workflow test production (shortage)',
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

        // Simulate the ingredient being consumed elsewhere between insert and approval.
        $fx['suppliesStock']->ss_stock = 5; // need 20, only 5 left
        $fx['suppliesStock']->save();

        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertJson(['status' => -1]);

        $production->refresh();
        $this->assertSame(1, (int) $production->status, 'a rejected approval must leave the production pending, not partially approved');

        $fx['productStock']->refresh();
        $fx['suppliesStock']->refresh();
        $this->assertSame(0, $fx['productStock']->ps_stock, 'a rejected approval must not add any output stock');
        $this->assertSame(5, $fx['suppliesStock']->ss_stock, 'a rejected approval must not touch ingredient stock at all');
    }

    public function test_decline_a_pending_production_leaves_stock_untouched(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture();
        $pdQty = 10;

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Workflow test production (decline)',
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

        $this->post('/declineProduction', ['production_id' => $production->production_id])
            ->assertStatus(200);

        $production->refresh();
        $this->assertSame(3, (int) $production->status, 'declining a pending production sets status to 3');
        $this->assertNotNull($production->acc_by, 'declineProduction records who declined it, same as accProduction');

        $fx['productStock']->refresh();
        $fx['suppliesStock']->refresh();
        $this->assertSame(0, $fx['productStock']->ps_stock, 'declining must not add any output stock — nothing was ever produced');
        $this->assertSame(self::STARTING_SUPPLIES_STOCK, $fx['suppliesStock']->ss_stock, 'declining must not touch ingredient stock — nothing was ever deducted');
    }

    public function test_declining_an_already_decided_production_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture();

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Workflow test production (double decline)',
            'detail' => json_encode([[
                'bom_id' => $fx['bom']->bom_id,
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pd_qty' => 10,
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

        $this->post('/declineProduction', ['production_id' => $production->production_id])->assertStatus(200);

        $response = $this->post('/declineProduction', ['production_id' => $production->production_id]);
        $response->assertJson(['status' => -2]);

        $production->refresh();
        $this->assertSame(3, (int) $production->status, 'a blocked repeat decline must leave status exactly as the first decline left it');
    }
}
