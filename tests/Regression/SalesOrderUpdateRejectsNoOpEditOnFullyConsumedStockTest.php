<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `CustomerController::updateSalesOrder()`'s post-approval path
 * (`CustomerController.php:154-235`) calls `SalesOrderStock::buildPlan()` to check stock
 * sufficiency for the NEW line items BEFORE restoring the OLD line items' already-reserved stock
 * (`executeRestore()` only runs inside the `DB::transaction()` afterward). If an approved Sales
 * Order fully consumed a product's available stock (a common, unremarkable case — e.g. selling
 * exactly what's left), editing that Sales Order afterward — even re-submitting the EXACT SAME
 * quantity, changing nothing about the actual stock commitment — is wrongly rejected as
 * "Stok tidak cukup," because `buildPlan` sees the current (already-deducted-to-zero) stock and
 * has no way to know the old reservation is about to be given back.
 *
 * See cdocs/testing/workflows/SALES_ORDER_FLOW.md. Confirmed 2026-08-02, deliberately deferred per
 * this project's "queue bugs, don't fix" policy. This test characterizes the CURRENT (wrongly
 * rejected) behavior on purpose.
 */
class SalesOrderUpdateRejectsNoOpEditOnFullyConsumedStockTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // Piece
    private const WAREHOUSE_ID = 1;

    public function test_editing_an_approved_so_that_fully_consumed_stock_is_wrongly_rejected_even_with_an_unchanged_qty(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'SO Update Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Update Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Update Regression Variant';
        $variant->product_variant_sku = 'WF-SOUPDATE-REG-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = 5; // exactly enough for the order below, nothing more
        $productStock->status = 1;
        $productStock->save();

        $customerId = (int) DB::table('customers')->where('status', 1)->value('customer_id');

        $productLine = fn (int $qty, ?int $sodId = null) => array_filter([
            'product_variant_id' => $variant->product_variant_id,
            'pr_name' => 'SO Update Regression Product',
            'product_name' => 'SO Update Regression Product',
            'product_variant_name' => $variant->product_variant_name,
            'product_variant_sku' => $variant->product_variant_sku,
            'unit_id' => self::UNIT_ID,
            'product_variant_price' => 1000,
            'so_qty' => $qty,
            'so_subtotal' => $qty * 1000,
            'sod_id' => $sodId,
        ], fn ($v) => $v !== null);

        $insertResponse = $this->post('/insertSalesOrder', [
            'so_customer' => $customerId,
            'so_date' => now()->toDateString(),
            'so_total' => 5000,
            'so_img' => json_encode([]),
            'products' => json_encode([$productLine(5)]),
        ]);
        $insertResponse->assertStatus(200);
        $soId = (int) SalesOrder::orderByDesc('so_id')->value('so_id');

        $this->post('/accSO', ['so_id' => $soId])->assertStatus(200);

        $productStock->refresh();
        $this->assertSame(0, $productStock->ps_stock, 'the order consumed exactly all available stock');

        $so = SalesOrder::findOrFail($soId);
        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();

        // A genuinely no-op edit: same product, same qty (5) — nothing about the actual stock
        // commitment changes. A correct implementation would restore-then-deduct back to the same
        // net result and succeed.
        $response = $this->post('/updateSalesOrder', [
            'so_id' => $soId,
            'so_number' => $so->so_number,
            'so_customer' => $customerId,
            'so_date' => now()->toDateString(),
            'so_invoice_no' => 'INV-NOOP-EDIT',
            'so_total' => 5000,
            'products' => json_encode([$productLine(5, $detail->sod_id)]),
        ]);

        $response->assertJson(fn ($json) => $json->where('header', 'Stok tidak cukup')->etc());

        $productStock->refresh();
        $this->assertSame(0, $productStock->ps_stock, 'the rejected update leaves stock untouched');

        $detail->refresh();
        $this->assertSame(5, (int) $detail->sod_qty, 'the rejected update must not have changed the detail row either');
    }
}
