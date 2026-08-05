<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED, re-verified 2026-08-05 — was: `CustomerController::updateSalesOrder()`'s post-approval
 * path used to check stock sufficiency for the NEW line items BEFORE restoring the OLD line
 * items' already-reserved stock, so editing an SO that had fully consumed a product's available
 * stock (a common, unremarkable case — selling exactly what's left) was wrongly rejected as "Stok
 * tidak cukup," even for a pure no-op re-submission that changed nothing about the actual stock
 * commitment.
 *
 * That implementation (`SalesOrderStock::buildPlan()`/`executeRestore()`/`executeDeduct()`, a
 * class that no longer exists anywhere in this codebase) has since been replaced entirely by a
 * 4-stage inline flow in `CustomerController::updateSalesOrder()`: revert the old line items'
 * stock first (TAHAP 1), aggregate + validate the new line items against stock that already
 * reflects that revert (TAHAP 2-3), then persist (TAHAP 4). That ordering is exactly what this
 * bug's original "When it gets addressed" note asked for — confirmed empirically (not assumed)
 * that a no-op edit on a fully-consumed SO now succeeds instead of being rejected.
 *
 * This test previously failed for an unrelated fixture reason: `accSO()`/`updateSalesOrder()`
 * both require at least one `ProductRelation` row per variant before touching stock at all
 * ("Mohon masukkan relasi produk" otherwise) — every real product in this app has one (confirmed:
 * zero `product_relations` rows in the seed data have a null `pr_unit_id_1`), but this test's
 * from-scratch fixture never created one. Added, see below.
 *
 * See cdocs/testing/workflows/SALES_ORDER_FLOW.md and cdocs/testing/KNOWN_ISSUES.md.
 */
class SalesOrderUpdateRejectsNoOpEditOnFullyConsumedStockTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // Piece
    private const DOS_UNIT_ID = 7; // Dos — see the ProductRelation fixture note below
    private const WAREHOUSE_ID = 1;

    public function test_editing_an_approved_so_that_fully_consumed_stock_succeeds_on_a_no_op_qty(): void
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

        // accSO()/updateSalesOrder() both require at least one ProductRelation row per variant
        // before they'll touch stock at all (`Mohon masukkan relasi produk` otherwise) — every
        // real product in this app has one (confirmed: zero product_relations rows in the seed
        // data have a null pr_unit_id_1), even products effectively sold/stocked in a single unit
        // day-to-day. DOS_UNIT_ID here is a placeholder upper unit this test never stocks or
        // orders — bongkar from it is never triggered (5 available already meets the qty-5 order),
        // it exists purely so this fixture matches the shape every real product actually has.
        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::UNIT_ID;
        $relation->pr_unit_value_2 = 12;
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

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
        // commitment changes, only the invoice number. updateSalesOrder() restores the old
        // reservation (TAHAP 1) before validating/re-deducting the new one (TAHAP 2-4), so this
        // must succeed and land back at the same net stock, not be rejected.
        $response = $this->post('/updateSalesOrder', [
            'so_id' => $soId,
            'so_number' => $so->so_number,
            'so_customer' => $customerId,
            'so_date' => now()->toDateString(),
            'so_invoice_no' => 'INV-NOOP-EDIT',
            'so_total' => 5000,
            'products' => json_encode([$productLine(5, $detail->sod_id)]),
        ]);

        $response->assertStatus(200);
        $this->assertSame('1', trim($response->getContent(), '"'), 'a genuine no-op qty edit must succeed, not be rejected as insufficient stock');

        $productStock->refresh();
        $this->assertSame(0, $productStock->ps_stock, 'a no-op edit must land back at the same net stock (restored 5, re-deducted 5)');

        $detail->refresh();
        $this->assertSame(5, (int) $detail->sod_qty, 'the qty itself is unchanged by this edit');

        $so->refresh();
        $this->assertSame('INV-NOOP-EDIT', $so->so_invoice_no, 'the actual edited field (invoice number) must be persisted');
    }
}
