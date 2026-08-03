<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/SALES_ORDER_FLOW.md's "Deliberately out of scope" note — the base
 * pilot picked a plain, non-retail, non-unit-converted line item on purpose. This file covers the
 * two sub-flows it deferred: retail-warehouse routing (product_variants.retail_unit +
 * sales_orders.retail_warehouse_id) and Sales Order's own unit-conversion "bongkar" mechanism,
 * which lives in App\Support\SalesOrderStock / App\Support\ProductUnitStock — a completely
 * separate implementation from Production's inline ladder logic (per that class's own docblock:
 * "Helper BARU ... Tidak mengubah ProductionController").
 *
 * Real warehouse ids from the committed seed snapshot: 1 = Gudang Pusat (main), 2 = Gudang
 * Eceran Toko (retail). Real unit ids: 7 = DOS, 9 = Piece.
 */
class SalesOrderRetailAndUnitConversionFlowTest extends TestCase
{
    use ActingAsStaff;

    private const MAIN_WAREHOUSE_ID = 1;
    private const RETAIL_WAREHOUSE_ID = 2;
    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;

    private function customerId(): int
    {
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    /** @return array{variant: ProductVariant} */
    private function createProductFixture(?int $retailUnit = null): array
    {
        $category = new Category();
        $category->category_name = 'SO Retail/Unit Conversion Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Retail/Unit Conversion Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Retail/Unit Conversion Test Variant';
        $variant->product_variant_sku = 'WF-SORETAIL-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = $retailUnit;
        $variant->status = 1;
        $variant->save();

        return compact('variant');
    }

    private function createProductStock(ProductVariant $variant, int $warehouseId, int $unitId, int $stock): ProductStock
    {
        $ps = new ProductStock();
        $ps->product_id = $variant->product_id;
        $ps->product_variant_id = $variant->product_variant_id;
        $ps->unit_id = $unitId;
        $ps->warehouse_id = $warehouseId;
        $ps->ps_stock = $stock;
        $ps->status = 1;
        $ps->save();

        return $ps;
    }

    private function insertSalesOrder(array $product, ?int $retailWarehouseId, int $qty, int $unitId): int
    {
        $payload = [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => $qty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'product_variant_id' => $product['variant']->product_variant_id,
                'pr_name' => 'Retail/Unit Conversion test product',
                'product_variant_name' => 'Test Variant',
                'product_variant_sku' => 'WF-TEST-SKU',
                'unit_id' => $unitId,
                'product_variant_price' => 1000,
                'so_qty' => $qty,
                'so_subtotal' => $qty * 1000,
            ]]),
        ];
        if ($retailWarehouseId !== null) {
            $payload['retail_warehouse_id'] = $retailWarehouseId;
        }

        $response = $this->post('/insertSalesOrder', $payload);
        $response->assertStatus(200);
        $this->assertSame('1', $response->getContent());

        return (int) SalesOrder::orderByDesc('so_id')->value('so_id');
    }

    public function test_a_retail_unit_line_deducts_from_the_retail_warehouse_not_main(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture(retailUnit: self::PIECE_UNIT_ID);
        $mainStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::PIECE_UNIT_ID, 100);
        $retailStock = $this->createProductStock($fx['variant'], self::RETAIL_WAREHOUSE_ID, self::PIECE_UNIT_ID, 50);

        $soId = $this->insertSalesOrder($fx, retailWarehouseId: self::RETAIL_WAREHOUSE_ID, qty: 10, unitId: self::PIECE_UNIT_ID);

        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(self::RETAIL_WAREHOUSE_ID, (int) $so->retail_warehouse_id);

        $this->post('/accSO', ['so_id' => $soId])->assertStatus(200);

        $so->refresh();
        $this->assertSame(2, (int) $so->status);

        $mainStock->refresh();
        $retailStock->refresh();
        $this->assertSame(100, $mainStock->ps_stock, 'a retail-unit line must NOT touch the main warehouse at all');
        $this->assertSame(40, $retailStock->ps_stock, 'a retail-unit line must deduct from the retail warehouse (50-10=40)');
    }

    public function test_inserting_a_retail_unit_line_without_a_retail_warehouse_is_blocked(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture(retailUnit: self::PIECE_UNIT_ID);
        $this->createProductStock($fx['variant'], self::RETAIL_WAREHOUSE_ID, self::PIECE_UNIT_ID, 50);
        $soCountBefore = SalesOrder::count();

        $response = $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => 10000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pr_name' => 'Retail test product',
                'product_variant_name' => 'Test Variant',
                'product_variant_sku' => 'WF-TEST-SKU',
                'unit_id' => self::PIECE_UNIT_ID,
                'product_variant_price' => 1000,
                'so_qty' => 10,
                'so_subtotal' => 10000,
            ]]),
            // retail_warehouse_id deliberately omitted
        ]);

        $response->assertJson(['status' => 0, 'header' => 'Gudang eceran wajib']);
        $this->assertSame($soCountBefore, SalesOrder::count(), 'a blocked retail-selection error must create no sales_orders row at all');
    }

    public function test_ordering_in_a_smaller_unit_bongkars_from_larger_unit_stock_at_the_main_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();

        // No retail_unit set -> always routes to the main warehouse, exercising the plain
        // (non-retail) unit-conversion path instead.
        $fx = $this->createProductFixture(retailUnit: null);

        $pieceStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::PIECE_UNIT_ID, 2);
        $dosStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::DOS_UNIT_ID, 5);

        $relation = new ProductRelation();
        $relation->product_variant_id = $fx['variant']->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID;   // larger unit
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::PIECE_UNIT_ID; // smaller unit
        $relation->pr_unit_value_2 = 12; // 1 DOS = 12 Piece
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        $logCountBefore = DB::table('log_stocks')->count();

        // Only 2 Piece in stock, ordering 22 -> needs 20 more -> ceil(20/12)=2 DOS broken down.
        $soId = $this->insertSalesOrder($fx, retailWarehouseId: null, qty: 22, unitId: self::PIECE_UNIT_ID);

        $this->post('/accSO', ['so_id' => $soId])->assertStatus(200);

        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(2, (int) $so->status);

        $pieceStock->refresh();
        $dosStock->refresh();
        $this->assertSame(3, $dosStock->ps_stock, '2 of the 5 DOS were broken down to cover the shortfall (5-2=3)');
        $this->assertSame(4, $pieceStock->ps_stock, '2(2) + 2 DOS x 12 = 26 available, minus the 22 needed, leaves 4');

        $this->assertSame($logCountBefore + 3, DB::table('log_stocks')->count(), 'bongkar (1) + hasil bongkar (1) + final deduction (1) = 3 new log_stocks rows');
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $fx['variant']->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'log_jumlah' => 2,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $fx['variant']->product_variant_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'log_jumlah' => 24,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $fx['variant']->product_variant_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'log_jumlah' => 22,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
        ]);
    }
}
