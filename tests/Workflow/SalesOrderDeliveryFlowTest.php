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
 * Fixed 2026-08-02: `SalesOrderDeliveryDetail::insertSoDeliveryDetail()` had an unconditional
 * `dd($s);` (app/Models/SalesOrderDeliveryDetail.php:53), hit by `insertSoDelivery`/
 * `updateSoDelivery`/`accSoDelivery` for ANY line item — `dd()` calls `die()` under the hood,
 * which cannot be caught by PHPUnit and would have aborted the entire test process. Removed, with
 * a null-guard added around the `ProductStock` lookup (matching KNOWN_ISSUES.md's documented fix
 * plan). The header/status-only tests below still use an empty `sdo_detail` array (kept as-is,
 * still valid coverage).
 *
 * **Found immediately once unblocked, NOT fixed (explicit scope decision, see KNOWN_ISSUES.md)**:
 * `test_insert_delivery_with_a_real_line_item_deducts_stock_a_second_time` proves delivering a line
 * item deducts `ps_stock` AGAIN, on top of the full deduction `accSO` already performs at SO
 * approval time — the same goods get double-counted. This was unreachable/unobservable before the
 * `dd()` fix.
 *
 * Also note: the stock lookup still matches by `product_id` (the parent product), not
 * `product_variant_id` — a separate, newly-found bug (confirmed 45 products have multiple active
 * variants sharing stock rows at the same product_id) left untouched per explicit scope decision;
 * see KNOWN_ISSUES.md. Not exercised by this file — the fixture below uses a product with only
 * one variant so the ambiguity doesn't affect this test's result.
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

    /**
     * A product with exactly ONE active variant — insertSoDeliveryDetail() matches ProductStock by
     * product_id (not product_variant_id, a separate known issue, see KNOWN_ISSUES.md), so a
     * multi-variant product's stock row would be picked ambiguously via ->first(). Using a
     * single-variant product here keeps this test's result unambiguous regardless of that gap.
     */
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

    /**
     * BUG found while building this test (2026-08-02, deliberately not fixed here — see
     * KNOWN_ISSUES.md): `SalesOrderFlowTest` already establishes that approving an SO performs the
     * real, final `ps_stock` deduction for the full ordered quantity (not a soft reservation). This
     * delivery-detail insert deducts `ps_stock` a SECOND time, by the delivered quantity, from the
     * SAME field — so an approved-then-delivered SO consumes stock twice for the same goods. This
     * was unreachable before the `dd()` fix; characterizing the CURRENT (double-deduction) behavior
     * on purpose, same as every other confirmed-but-deferred bug in this program.
     */
    public function test_insert_delivery_with_a_real_line_item_deducts_stock_a_second_time(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickSingleVariantFixtureStock();
        $soId = $this->insertApprovedSalesOrder($stock, 5);
        $stock->refresh();
        $stockAfterSoApproval = $stock->ps_stock; // accSO already performed the REAL deduction of 5
        $deliveryQty = 3;

        $response = $this->post('/insertSoDelivery', [
            'so_id' => $soId,
            'sdo_receiver' => 'Workflow Test Receiver',
            'sdo_date' => now()->toDateString(),
            'sdo_phone' => '081200000000',
            'sdo_desc' => 'Real line-item delivery',
            'sdo_detail' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'sdod_sku' => 'WF-DELIVERY-SKU',
                'sdod_qty' => $deliveryQty,
                'unit_id' => $stock->unit_id,
            ]]),
        ]);
        $response->assertStatus(200);

        $sdoId = (int) SalesOrderDelivery::orderByDesc('sdo_id')->value('sdo_id');
        $this->assertDatabaseHas('sales_delivery_orders_details', [
            'sdo_id' => $sdoId,
            'product_variant_id' => $stock->product_variant_id,
            'sdod_qty' => $deliveryQty,
        ]);

        $stock->refresh();
        $this->assertSame(
            $stockAfterSoApproval - $deliveryQty,
            $stock->ps_stock,
            'BUG: this deducts ps_stock AGAIN, on top of what accSO already deducted at approval — the same goods get double-counted'
        );
    }
}
