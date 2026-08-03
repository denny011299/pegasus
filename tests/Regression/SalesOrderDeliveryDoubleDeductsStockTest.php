<?php

namespace Tests\Regression;

use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderDelivery;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `SalesOrderDeliveryDetail::insertSoDeliveryDetail()` deducts `ProductStock.ps_stock` by the
 * delivered quantity — but `CustomerController::accSO()` already performs the REAL, final
 * deduction of the FULL ordered quantity at SO-approval time (confirmed by
 * `tests/Workflow/SalesOrderFlowTest.php`'s own assertion: "approval must deduct the ordered qty
 * from the main warehouse stock" — not a soft reservation). Delivering a line item against an
 * already-approved SO therefore deducts the SAME goods from `ps_stock` a SECOND time.
 *
 * Found 2026-08-02 immediately after fixing the unrelated `dd()` crash that previously made this
 * code path completely unreachable (`app/Models/SalesOrderDeliveryDetail.php:53`, see
 * KNOWN_ISSUES.md's "Sales Order Delivery" entry) — this double-deduction was unobservable before
 * that fix landed. Deliberately NOT fixed here (explicit scope decision — this touches core SO
 * approval/delivery stock-accounting semantics and needs its own dedicated task). This test
 * characterizes the CURRENT (double-deduction) behavior on purpose.
 */
class SalesOrderDeliveryDoubleDeductsStockTest extends TestCase
{
    use ActingAsStaff;

    private function pickSingleVariantFixtureStock(): ProductStock
    {
        $singleVariantProductIds = DB::table('product_variants')
            ->select('product_id')
            ->where('status', 1)
            ->groupBy('product_id')
            ->havingRaw('count(*) = 1')
            ->pluck('product_id');

        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->where('ps_stock', '>', 20)
            ->whereIn('product_id', $singleVariantProductIds)
            ->firstOrFail();
    }

    private function customerId(): int
    {
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    public function test_delivering_a_line_item_deducts_stock_twice_for_the_same_goods(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickSingleVariantFixtureStock();
        $startingStock = $stock->ps_stock;
        $orderedQty = 5;

        $insertResponse = $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => $orderedQty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'pr_name' => 'Regression test product',
                'product_variant_name' => 'Test Variant',
                'product_variant_sku' => 'REG-DBLDEDUCT-SKU',
                'unit_id' => $stock->unit_id,
                'product_variant_price' => 1000,
                'so_qty' => $orderedQty,
                'so_subtotal' => $orderedQty * 1000,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $soId = (int) SalesOrder::orderByDesc('so_id')->value('so_id');

        $this->post('/accSO', ['so_id' => $soId])->assertStatus(200);

        $stock->refresh();
        $this->assertSame($startingStock - $orderedQty, $stock->ps_stock, 'accSO performs the real, full deduction at approval time');
        $stockAfterApproval = $stock->ps_stock;

        $deliveryQty = 3; // a partial delivery against the already-approved, already-deducted order

        $this->post('/insertSoDelivery', [
            'so_id' => $soId,
            'sdo_receiver' => 'Regression Test Receiver',
            'sdo_date' => now()->toDateString(),
            'sdo_phone' => '081200000000',
            'sdo_desc' => 'Double-deduction regression test',
            'sdo_detail' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'sdod_sku' => 'REG-DBLDEDUCT-SKU',
                'sdod_qty' => $deliveryQty,
                'unit_id' => $stock->unit_id,
            ]]),
        ])->assertStatus(200);

        $stock->refresh();
        $this->assertSame(
            $stockAfterApproval - $deliveryQty,
            $stock->ps_stock,
            'BUG: the delivery deducts ps_stock a SECOND time — combined with accSO\'s own deduction, '
            .'this SO has now consumed '.($orderedQty + $deliveryQty).' units of stock for only '.$orderedQty.' units actually ordered'
        );

        $sdoId = (int) SalesOrderDelivery::orderByDesc('sdo_id')->value('sdo_id');
        $this->assertDatabaseHas('sales_delivery_orders_details', [
            'sdo_id' => $sdoId,
            'product_variant_id' => $stock->product_variant_id,
            'sdod_qty' => $deliveryQty,
        ]);
    }
}
