<?php

namespace Tests\Workflow;

use App\Models\ProductStock;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/SALES_ORDER_FLOW.md for the fully-traced flow this asserts against.
 * The pilot deliberately picks a plain, non-retail, non-unit-converted line item to keep scope to
 * Insert -> Approve -> Reject, matching the Purchase Order pilot's shape.
 *
 * Corrected 2026-08-24: this docblock used to claim "unlike Purchase Order, this flow wraps its
 * stock mutation in a real DB::transaction() end to end". That was false — `CustomerController`
 * had no transactions at all, and the same false claim in
 * `cdocs/testing/guides/DATABASE_TRANSACTION_GUIDE.md` is likely why the gap went unprioritised.
 * `accSO()`/`updateSalesOrder()` are now genuinely transactional; atomicity itself is asserted in
 * `tests/DatabaseTransaction/SalesOrderUpdateAtomicityTest.php`, not here.
 *
 * Updated 2026-09 (shipment-approval-flow v2, see
 * cdocs/docs/specs/shipment-external-api-approval-flow.md): accSO()/declineSO() are RETIRED —
 * every Pending Pengiriman now goes through 2-stage approval (App\Support\ShipmentApproval,
 * CustomerController::approveShipment()/rejectShipment()) instead, exercised here via
 * Tests\Support\ActingAsStaff::approveShipmentTwoStage()/rejectShipmentAtQcStage(). Manual insert
 * itself is also disabled by default (config('pegasus.shipment_internal_insert_enabled')) — opened
 * back up for this file's fixtures only, since this flow's insert step is not itself under test.
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
        config(['pegasus.shipment_internal_insert_enabled' => true]);

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

        $this->approveShipmentTwoStage($soId);

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

        $this->rejectShipmentAtQcStage($soId);

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

        // Stock is only actually checked at the Ops stage (App\Support\SalesOrderApproval::
        // confirm(), called from CustomerController::approveShipment() when the ops stage
        // completes) — QC has no stock guard at all.
        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view'], ['role_id' => \App\Support\RoleIds::DIREKSI]);
        $this->withActiveWarehouse(1);
        $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'qc'])->assertStatus(200)->assertJson(['status' => 1]);

        $opsResponse = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops']);
        $opsResponse->assertStatus(200);
        $this->assertSame(-1, (int) $opsResponse->json('status'), 'insufficient stock must not silently succeed');
        $this->assertNotEmpty($opsResponse->json('message'), 'the response must at least name which product fell short');

        $so = SalesOrder::find($soId);
        $this->assertSame(1, (int) $so->status, 'a rejected approval must leave the SO pending, not partially approved');
        $this->assertNull($so->ops_approved_by, 'a failed Ops confirm must roll back the ops_approved_by stamp too, not just stock');

        $stock->refresh();
        $this->assertSame($qty - 1, $stock->ps_stock, 'a rejected approval must not touch stock at all');
    }
}
