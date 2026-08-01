<?php

namespace Tests\Workflow;

use App\Models\ProductStock;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/SALES_ORDER_FLOW.md for the fully-traced flow this asserts against.
 * Unlike Purchase Order, this flow wraps its stock mutation in a real DB::transaction() end to
 * end — the pilot deliberately picks a plain, non-retail, non-unit-converted line item to keep
 * scope to Insert -> Approve -> Reject, matching the Purchase Order pilot's shape.
 */
class SalesOrderFlowTest extends TestCase
{
    use ActingAsStaff;

    private function pickFixtureStock(): ProductStock
    {
        // ProductStock has no Eloquent relationships (this codebase's models generally don't
        // define any — see the pegasus-conventions skill), so join via a plain subquery instead.
        $variantIdsWithoutRetailUnit = DB::table('product_variants')
            ->whereNull('retail_unit')
            ->pluck('product_variant_id');

        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1) // Gudang Pusat (main), seeded 2026-08-01
            ->where('ps_stock', '>', 20)
            ->whereIn('product_variant_id', $variantIdsWithoutRetailUnit)
            ->firstOrFail();
    }

    private function customerId(): int
    {
        // so_customer is a customers.customer_id reference (loosely-typed varchar column, no
        // real FK) — confirmed 2026-08-01 while tracing the Sales Order Invoice flow. An earlier
        // version of this fixture wrongly used a free-text string here.
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    private function insertSalesOrder(ProductStock $stock, int $qty): int
    {
        $response = $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => $qty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'pr_name' => 'Workflow test product',
                'product_variant_name' => 'Test Variant',
                'product_variant_sku' => 'WF-TEST-SKU',
                'unit_id' => $stock->unit_id,
                'product_variant_price' => 1000,
                'so_qty' => $qty,
                'so_subtotal' => $qty * 1000,
            ]]),
        ]);
        $response->assertStatus(200);
        $this->assertSame('1', $response->getContent(), 'insertSalesOrder should return a bare 1 on success');

        return (int) SalesOrder::orderByDesc('so_id')->value('so_id');
    }

    public function test_insert_then_approve_deducts_stock_and_writes_log(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $qty = 5;
        $startingStock = $stock->ps_stock;
        $logCountBefore = DB::table('log_stocks')->count();

        $soId = $this->insertSalesOrder($stock, $qty);

        $so = SalesOrder::find($soId);
        $this->assertSame(1, (int) $so->status, 'a freshly inserted SO should be pending approval');
        $this->assertDatabaseHas('sales_order_details', [
            'so_id' => $soId,
            'product_variant_id' => $stock->product_variant_id,
            'sod_qty' => $qty,
        ]);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'inserting an SO must not touch stock before approval');
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'inserting an SO must not write a log_stocks row');

        $accResponse = $this->post('/accSO', ['so_id' => $soId]);
        $accResponse->assertStatus(200);

        $so->refresh();
        $this->assertSame(2, (int) $so->status, 'approving an SO sets status to 2');
        $this->assertNotNull($so->acc_by);

        $stock->refresh();
        $this->assertSame($startingStock - $qty, $stock->ps_stock, 'approval must deduct the ordered qty from the main warehouse stock');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $stock->product_variant_id,
            'log_jumlah' => $qty,
        ]);
    }

    public function test_decline_a_pending_so_leaves_stock_untouched(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $qty = 3;
        $startingStock = $stock->ps_stock;

        $soId = $this->insertSalesOrder($stock, $qty);

        $this->post('/declineSO', ['so_id' => $soId])->assertStatus(200);

        $so = SalesOrder::find($soId);
        $this->assertSame(3, (int) $so->status, 'declining a pending SO sets status to 3');

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'declining a pending SO must not touch stock (nothing was deducted yet)');
    }

    public function test_approve_is_rejected_cleanly_when_stock_becomes_insufficient(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $qty = 5;

        $soId = $this->insertSalesOrder($stock, $qty);

        // Simulate the stock being consumed elsewhere (e.g. another sale)
        // between insert and approval.
        $stock->refresh();
        $stock->ps_stock = $qty - 1;
        $stock->save();

        $accResponse = $this->post('/accSO', ['so_id' => $soId]);
        $accResponse->assertJson(fn ($json) => $json->where('header', 'Stok tidak cukup')->etc());

        $so = SalesOrder::find($soId);
        $this->assertSame(1, (int) $so->status, 'a rejected approval must leave the SO pending, not partially approved');

        $stock->refresh();
        $this->assertSame($qty - 1, $stock->ps_stock, 'a rejected approval must not touch stock at all');
    }
}
