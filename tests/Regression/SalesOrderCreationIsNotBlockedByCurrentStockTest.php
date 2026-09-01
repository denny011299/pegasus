<?php

namespace Tests\Regression;

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
 * ✅ FIXED 2026-09-01 (GitHub #99) — was: `CustomerController::insertSalesOrder()` (and
 * `updateSalesOrder()`'s pre-ACC branch, status != 2) called
 * `SalesOrderStock::assertStockAvailable()` and refused to even CREATE a Pengiriman document when
 * current stock was short, with "Stok tidak cukup".
 *
 * That is the wrong moment to check. Creating a Pengiriman only files a request (status 1,
 * "Sedang Diajukan") — no stock is reserved or deducted. The real check + deduction happens at
 * ACC (`accSO()` -> `SalesOrderApproval::confirm()` -> `buildPlan()` + `executeDeduct()` inside
 * one transaction). Blocking creation against TODAY's stock makes it impossible to schedule a
 * shipment that will be covered by production/PO/transfer arriving before the ACC.
 *
 * This test pins both halves: creation must succeed on zero stock and touch nothing, and ACC must
 * still refuse while the stock is short — i.e. the guard moved, it did not disappear.
 *
 * See cdocs/testing/workflows/SALES_ORDER_FLOW.md.
 */
class SalesOrderCreationIsNotBlockedByCurrentStockTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // Piece
    private const DOS_UNIT_ID = 7; // Dos — placeholder upper unit, never stocked or ordered here
    private const WAREHOUSE_ID = 1;

    /** @return array{variant: ProductVariant, productStock: ProductStock} */
    private function createFixture(int $startingStock): array
    {
        $category = new Category();
        $category->category_name = 'SO Create Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Create Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Create Test Variant';
        $variant->product_variant_sku = 'RG-SOCREATE-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = $startingStock;
        $productStock->status = 1;
        $productStock->save();

        // accSO() refuses with "Mohon masukkan relasi produk" unless the variant has at least one
        // ProductRelation row — every real product in this app has one, so the fixture needs it
        // too (same note as tests/Workflow/SalesOrderUpdateFlowTest.php).
        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::UNIT_ID;
        $relation->pr_unit_value_2 = 12;
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        return compact('variant', 'productStock');
    }

    private function customerId(): int
    {
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    private function productLine(ProductVariant $variant, int $qty): array
    {
        return [
            'product_variant_id' => $variant->product_variant_id,
            'pr_name' => 'SO Create Test Product',
            'product_name' => 'SO Create Test Product',
            'product_variant_name' => $variant->product_variant_name,
            'product_variant_sku' => $variant->product_variant_sku,
            'unit_id' => self::UNIT_ID,
            'product_variant_price' => 1000,
            'so_qty' => $qty,
            'so_subtotal' => $qty * 1000,
        ];
    }

    public function test_a_pengiriman_can_be_created_while_stock_is_still_zero(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture(startingStock: 0);

        $response = $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => 5 * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([$this->productLine($fx['variant'], 5)]),
        ]);
        $response->assertStatus(200);
        $this->assertSame(
            '1',
            trim($response->getContent(), '"'),
            'creating a Pengiriman must not be blocked by current stock — stock is only checked at ACC'
        );

        $soId = (int) SalesOrder::orderByDesc('so_id')->value('so_id');
        $this->assertSame(1, (int) SalesOrder::find($soId)->status, 'a newly created Pengiriman stays pending');

        $fx['productStock']->refresh();
        $this->assertSame(0, (int) $fx['productStock']->ps_stock, 'creating a Pengiriman must not touch stock at all');
    }

    public function test_acc_still_refuses_while_stock_is_short_then_succeeds_once_stock_arrives(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture(startingStock: 0);

        $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => 5 * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([$this->productLine($fx['variant'], 5)]),
        ])->assertStatus(200);

        $soId = (int) SalesOrder::orderByDesc('so_id')->value('so_id');

        $refused = $this->post('/accSO', ['so_id' => $soId]);
        $refused->assertStatus(200);
        $this->assertNotSame('1', trim($refused->getContent(), '"'), 'ACC must still refuse while stock is short');
        $this->assertSame(1, (int) SalesOrder::find($soId)->status, 'a refused ACC leaves the Pengiriman pending');

        // Stock arrives (production finished / PO received) after the document was filed.
        $fx['productStock']->refresh();
        $fx['productStock']->ps_stock = 5;
        $fx['productStock']->save();

        $accepted = $this->post('/accSO', ['so_id' => $soId]);
        $accepted->assertStatus(200);
        $this->assertSame('1', trim($accepted->getContent(), '"'), 'ACC must succeed once the stock is there');

        $fx['productStock']->refresh();
        $this->assertSame(0, (int) $fx['productStock']->ps_stock, 'ACC is the point where stock is deducted');
    }
}
