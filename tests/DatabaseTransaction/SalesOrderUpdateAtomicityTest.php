<?php

namespace Tests\DatabaseTransaction;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-24): `CustomerController::updateSalesOrder()` had NO `DB::transaction()` at
 * all — and this was the single most dangerous of the stock-atomicity gaps, because it creates
 * stock out of nothing rather than merely losing some.
 *
 * Note the method's shape: for an already-approved SO (`status == 2`) it first REVERTS the old
 * stock (TAHAP 1 — `$sOld->ps_stock += $rev['qty']`, saved immediately), and only much later
 * deducts the new quantities (TAHAP 4). Between those two phases sits TAHAP 3's stock-sufficiency
 * validation, whose failure path is a plain `return implode(", ", $p);`. So the *normal business
 * path* of "you tried to edit an approved SO up to a quantity we don't have" used to leave the
 * TAHAP 1 revert permanently committed with no matching deduction — phantom stock, every time,
 * no crash required.
 *
 * Worth stressing that this was never hypothetical: it needs no exception, no race, no bad data —
 * just a user editing an approved SO and asking for more than is in stock, which the UI actively
 * invites. Every such rejected edit silently inflated stock by the original order quantity.
 *
 * The fix wraps the whole method in `DB::beginTransaction()`/`commit()` with `rollBack()` on both
 * the validation-failure exit and any throw, mirroring `accProduction()`/`accStockOpname()`.
 *
 * Both the testing guide (`cdocs/testing/guides/DATABASE_TRANSACTION_GUIDE.md:8`) and
 * `tests/Workflow/SalesOrderFlowTest.php`'s docblock previously asserted the opposite — that this
 * flow "wraps its stock mutation in a real DB::transaction() end to end" — which is likely why the
 * gap went unprioritised for so long. Both have been corrected.
 *
 * Fixtures are built fresh via Eloquent rather than picked from seeded data, per
 * `cdocs/testing/` guidance and memory `pegasus-testing-db-multiwarehouse-drift`: the shared
 * `pegasus_testing` DB currently holds real multi-warehouse rows that make hand-picked stock
 * fixtures behave unpredictably here.
 */
class SalesOrderUpdateAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private const STARTING_STOCK = 90;
    private const ORDERED_QTY = 10;

    /** @return array{0: ProductStock, 1: ProductVariant} */
    private function createFixture(): array
    {
        $unit = Unit::where('status', 1)->firstOrFail();

        $category = new Category();
        $category->category_name = 'SO Update Atomicity Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Update Atomicity Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Update Atomicity Variant';
        $variant->product_variant_sku = 'DBTX-SOUPD-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->status = 1;
        $variant->save();

        // Exactly ONE stock row, already reflecting a deducted/approved SO:
        // 100 on hand originally, minus the ORDERED_QTY this SO consumed.
        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = $unit->unit_id;
        $stock->warehouse_id = 1;
        $stock->ps_stock = self::STARTING_STOCK;
        $stock->status = 1;
        $stock->save();

        return [$stock, $variant];
    }

    private function createApprovedSalesOrder(ProductVariant $variant, ProductStock $stock): SalesOrder
    {
        $so = new SalesOrder();
        $so->so_number = 'SO-DBTX-'.uniqid();
        $so->so_customer = (int) DB::table('customers')->where('status', 1)->value('customer_id');
        $so->so_date = now()->toDateString();
        $so->so_total = self::ORDERED_QTY * 1000;
        $so->so_ppn = 0;
        $so->so_discount = 0;
        $so->so_cost = 0;
        $so->so_img = json_encode([]);
        $so->so_invoice_no = 'INV-DBTX-'.uniqid();
        $so->status = 2; // already approved — this is what enables the revert-then-deduct path
        $so->save();

        $sod = new SalesOrderDetail();
        $sod->so_id = $so->so_id;
        $sod->product_variant_id = $variant->product_variant_id;
        $sod->sod_nama = 'SO Update Atomicity Product';
        $sod->sod_variant = $variant->product_variant_name;
        $sod->sod_sku = $variant->product_variant_sku;
        $sod->unit_id = $stock->unit_id;
        $sod->sod_harga = 1000;
        $sod->sod_qty = self::ORDERED_QTY;
        $sod->sod_subtotal = self::ORDERED_QTY * 1000;
        $sod->status = 1;
        $sod->save();

        return $so;
    }

    public function test_a_rejected_edit_of_an_approved_so_no_longer_leaves_phantom_stock_behind(): void
    {
        $this->actingAsSuperAdminStaff();

        [$stock, $variant] = $this->createFixture();
        $so = $this->createApprovedSalesOrder($variant, $stock);
        $logCountBefore = DB::table('log_stocks')->count();

        // Ask for far more than exists. TAHAP 1 reverts the original 10 (stock 90 -> 100),
        // TAHAP 3 then finds 100 < 99999 and bails out via `return implode(", ", $p)`.
        $response = $this->post('/updateSalesOrder', [
            'so_id' => $so->so_id,
            'so_number' => $so->so_number,
            'so_customer' => $so->so_customer,
            'so_date' => now()->toDateString(),
            'so_total' => 99999 * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'sod_id' => SalesOrderDetail::where('so_id', $so->so_id)->value('sod_id'),
                'product_variant_id' => $variant->product_variant_id,
                'product_name' => 'SO Update Atomicity Product',
                'pr_name' => 'SO Update Atomicity Product',
                'product_variant_name' => $variant->product_variant_name,
                'product_variant_sku' => $variant->product_variant_sku,
                'unit_id' => $stock->unit_id,
                'product_variant_price' => 1000,
                'so_qty' => 99999,
                'so_subtotal' => 99999 * 1000,
            ]]),
        ]);

        // The edit is rejected with the "insufficient stock" product-name string (unchanged
        // behavior — this fix is about atomicity, not the response shape).
        $response->assertStatus(200);
        $this->assertStringContainsString(
            'SO Update Atomicity Product',
            $response->getContent(),
            'the rejected edit still reports which product was short'
        );

        // THE FIX: TAHAP 1's revert must not survive the rejected edit.
        $stock->refresh();
        $this->assertSame(
            self::STARTING_STOCK,
            $stock->ps_stock,
            'BUG WOULD BE: stock left at 100 (the TAHAP 1 revert committed with no matching deduction) — '
            .'phantom stock created out of nothing by a merely-rejected edit'
        );

        // The revert also wrote a log_stocks row before bailing; that must roll back too,
        // otherwise the ledger claims a restock that never happened.
        $this->assertSame(
            $logCountBefore,
            DB::table('log_stocks')->count(),
            'no log_stocks row may survive a rejected edit'
        );

        // And the order itself is untouched — still approved, still the original quantity.
        $so->refresh();
        $this->assertSame(2, (int) $so->status, 'the SO stays approved');
        $this->assertDatabaseHas('sales_order_details', [
            'so_id' => $so->so_id,
            'product_variant_id' => $variant->product_variant_id,
            'sod_qty' => self::ORDERED_QTY,
        ]);
    }

    public function test_a_valid_edit_of_an_approved_so_still_commits_normally(): void
    {
        $this->actingAsSuperAdminStaff();

        [$stock, $variant] = $this->createFixture();
        $so = $this->createApprovedSalesOrder($variant, $stock);
        $newQty = 25;

        $response = $this->post('/updateSalesOrder', [
            'so_id' => $so->so_id,
            'so_number' => $so->so_number,
            'so_invoice_no' => $so->so_invoice_no,
            'so_customer' => $so->so_customer,
            'so_date' => now()->toDateString(),
            'so_total' => $newQty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'sod_id' => SalesOrderDetail::where('so_id', $so->so_id)->value('sod_id'),
                'product_variant_id' => $variant->product_variant_id,
                'product_name' => 'SO Update Atomicity Product',
                'pr_name' => 'SO Update Atomicity Product',
                'product_variant_name' => $variant->product_variant_name,
                'product_variant_sku' => $variant->product_variant_sku,
                'unit_id' => $stock->unit_id,
                'product_variant_price' => 1000,
                'so_qty' => $newQty,
                'so_subtotal' => $newQty * 1000,
            ]]),
        ]);
        $response->assertStatus(200);

        // Proves the added transaction COMMITS rather than silently swallowing valid work:
        // 90 + 10 (revert) - 25 (new deduction) = 75.
        $stock->refresh();
        $this->assertSame(
            self::STARTING_STOCK + self::ORDERED_QTY - $newQty,
            $stock->ps_stock,
            'a valid edit must still revert the old qty and deduct the new one, and commit both'
        );
        $this->assertDatabaseHas('sales_order_details', [
            'so_id' => $so->so_id,
            'product_variant_id' => $variant->product_variant_id,
            'sod_qty' => $newQty,
        ]);
    }
}
