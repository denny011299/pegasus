<?php

namespace Tests\Workflow;

use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderDelivery;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/SALES_ORDER_DELIVERY_FLOW.md for the fully-traced flow this asserts
 * against.
 *
 * IMPORTANT: `SalesOrderDeliveryDetail::insertSoDeliveryDetail()` contains an unconditional
 * `dd($s);` (app/Models/SalesOrderDeliveryDetail.php:53), hit by `insertSoDelivery`/
 * `updateSoDelivery`/`accSoDelivery` for ANY line item. `dd()` calls `die()` under the hood, which
 * cannot be caught by PHPUnit and would abort this entire test *process* (not just fail one test),
 * silently discarding every other test's result in the same run. Every request in this file
 * therefore deliberately sends an EMPTY `sdo_detail` array (`"[]"`) so the crashing `foreach` body
 * never executes — this file only proves the header/status mechanics, not the (currently broken)
 * stock-deducting detail path. See KNOWN_ISSUES.md — do not "fix" this file by adding real items.
 */
class SalesOrderDeliveryFlowTest extends TestCase
{
    use ActingAsStaff;

    private function pickFixtureStock(): ProductStock
    {
        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->where('ps_stock', '>', 20)
            ->firstOrFail();
    }

    private function customerId(): int
    {
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    private function insertApprovedSalesOrder(ProductStock $stock, int $qty): int
    {
        $response = $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => $qty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'pr_name' => 'Delivery test product',
                'product_variant_name' => 'Test Variant',
                'product_variant_sku' => 'WF-DELIVERY-SKU',
                'unit_id' => $stock->unit_id,
                'product_variant_price' => 1000,
                'so_qty' => $qty,
                'so_subtotal' => $qty * 1000,
            ]]),
        ]);
        $response->assertStatus(200);
        $soId = (int) SalesOrder::orderByDesc('so_id')->value('so_id');

        $this->post('/accSO', ['so_id' => $soId])->assertStatus(200);

        return $soId;
    }

    private function insertDeliveryWithNoItems(int $soId): int
    {
        $response = $this->post('/insertSoDelivery', [
            'so_id' => $soId,
            'sdo_receiver' => 'Workflow Test Receiver',
            'sdo_date' => now()->toDateString(),
            'sdo_phone' => '081200000000',
            'sdo_desc' => 'Empty-item delivery (avoids the insertSoDeliveryDetail dd() crash)',
            'sdo_detail' => json_encode([]),
        ]);
        $response->assertStatus(200);

        return (int) SalesOrderDelivery::orderByDesc('sdo_id')->value('sdo_id');
    }

    public function test_insert_delivery_header_with_no_items_creates_a_pending_row(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $soId = $this->insertApprovedSalesOrder($stock, 5);

        $sdoId = $this->insertDeliveryWithNoItems($soId);

        $sdo = SalesOrderDelivery::findOrFail($sdoId);
        $this->assertSame($soId, (int) $sdo->so_id);
        $this->assertSame(1, (int) $sdo->status, 'a freshly inserted delivery should default to pending');
        $this->assertNotEmpty($sdo->sdo_number, 'insertSoDelivery auto-generates sdo_number');
        $this->assertStringStartsWith('SDO', $sdo->sdo_number);
    }

    public function test_decline_a_pending_delivery_flips_status_without_touching_items(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $soId = $this->insertApprovedSalesOrder($stock, 5);
        $sdoId = $this->insertDeliveryWithNoItems($soId);

        $this->post('/declineSoDelivery', [
            'sdo_id' => $sdoId,
            'status' => 0,
        ])->assertStatus(200);

        $sdo = SalesOrderDelivery::findOrFail($sdoId);
        $this->assertSame(0, (int) $sdo->status, 'declineSoDelivery just writes whatever status the request sends');
    }

    public function test_accept_a_delivery_with_no_items_flips_status_to_approved(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $soId = $this->insertApprovedSalesOrder($stock, 5);
        $sdoId = $this->insertDeliveryWithNoItems($soId);

        $this->post('/accSoDelivery', [
            'sdo_id' => $sdoId,
            'sdo_receiver' => 'Workflow Test Receiver (accepted)',
            'sdo_date' => now()->toDateString(),
            'sdo_phone' => '081200000000',
            'sdo_desc' => 'Accepted, still no items',
            'sdo_detail' => json_encode([]),
            'status' => 2,
        ])->assertStatus(200);

        $sdo = SalesOrderDelivery::findOrFail($sdoId);
        $this->assertSame(2, (int) $sdo->status);
    }
}
