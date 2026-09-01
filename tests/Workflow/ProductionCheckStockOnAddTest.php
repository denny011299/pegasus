<?php

namespace Tests\Workflow;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #101: the raw-material stock check used to run only on final "Tambah Produksi"
 * submit. It now also runs on every "+ Tambah" click via POST /checkProductionStock, so a
 * user finds out about a shortage as soon as they try to add the offending row instead of
 * after building the whole list. Both endpoints share the same validation
 * (ProductionController::validateProductionItems()) so this asserts they agree.
 *
 * Same fixture shape as ProductionFlowTest — a fully fresh product/supplies/BOM rather than
 * seeded data, since real seeded BOMs hit unit-conversion/dos-pack branches out of this
 * test's scope.
 */
class ProductionCheckStockOnAddTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // "Piece" — real, active, no conversion relations needed
    private const WAREHOUSE_ID = 1; // Gudang Pusat (main), seeded 2026-08-01
    private const BOM_QTY = 1;
    private const BOM_DETAIL_QTY = 2; // 2 units of raw material per 1 unit of BOM output
    private const STARTING_SUPPLIES_STOCK = 20;

    /**
     * @return array{variant: ProductVariant, productStock: ProductStock, supplies: Supplies, suppliesStock: SuppliesStock, bom: Bom, bomDetail: BomDetail}
     */
    private function createFixture(): array
    {
        $category = new Category();
        $category->category_name = 'Add-time Stock Check Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Add-time Stock Check Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Add-time Stock Check Test Variant';
        $variant->product_variant_sku = 'WF-ADDCHECK-'.uniqid();
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
        $supplies->supplies_name = 'Add-time Stock Check Test Supplies';
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

    public function test_check_production_stock_allows_a_row_that_fits_available_stock(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        // Stock is 20, needs 2 per unit — 5 units needs 10, well within stock.
        $pdQty = 5;

        $response = $this->post('/checkProductionStock', [
            'detail' => json_encode([[
                'bom_id' => $fx['bom']->bom_id,
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::UNIT_ID,
            ]]),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        // Read-only: checking must never touch stock.
        $fx['suppliesStock']->refresh();
        $this->assertSame(
            self::STARTING_SUPPLIES_STOCK,
            $fx['suppliesStock']->ss_stock,
            'checkProductionStock must not mutate ingredient stock'
        );
    }

    public function test_check_production_stock_blocks_a_row_that_exceeds_available_stock(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        // Stock is 20, needs 2 per unit — 20 units needs 40, more than available.
        $pdQty = 20;

        $response = $this->post('/checkProductionStock', [
            'detail' => json_encode([[
                'bom_id' => $fx['bom']->bom_id,
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::UNIT_ID,
            ]]),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => -1]);
        $response->assertJsonFragment(['message' => 'Bahan baku tidak mencukupi untuk : '.$fx['supplies']->supplies_name]);

        $fx['suppliesStock']->refresh();
        $this->assertSame(
            self::STARTING_SUPPLIES_STOCK,
            $fx['suppliesStock']->ss_stock,
            'a blocked add-time check must not mutate ingredient stock either'
        );
    }

    public function test_check_production_stock_considers_rows_already_in_the_list(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        // Two rows of the same product at 6 units each (needs 12 each, 24 total) exceed the
        // 20 in stock even though a single row of 6 (needs 12) would fit on its own — this is
        // the whole point of #101: the check must run against the full candidate list (what
        // the client sends as `detail`), not just the row being added in isolation.
        $rowDetail = [
            'bom_id' => $fx['bom']->bom_id,
            'product_variant_id' => $fx['variant']->product_variant_id,
            'pd_qty' => 6,
            'unit_id' => self::UNIT_ID,
        ];

        $response = $this->post('/checkProductionStock', [
            'detail' => json_encode([$rowDetail, $rowDetail]),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => -1]);
    }

    public function test_insert_production_still_rejects_the_same_shortage_at_final_submit(): void
    {
        // insertProduction() now delegates to the same validateProductionItems() that
        // checkProductionStock() calls — this proves the extraction in #101 didn't change
        // final-submit behavior.
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $pdQty = 20;

        $response = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Add-time stock check test (final submit shortage)',
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

        $response->assertStatus(200);
        $response->assertJson(['status' => -1]);
    }
}
