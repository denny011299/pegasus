<?php

namespace Tests\Workflow;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PURCHASE_ORDER_MANUAL_DELIVERY_FLOW.md for the fully-traced flow
 * this asserts against — the sub-flow PurchaseOrderFlowTest.php deferred. Unlike accPO's own
 * auto-generated delivery, nothing here checks purchase_orders.status at all, so a manual delivery
 * can be created/approved against a PO that never went through accPO — which is itself the most
 * important finding: approving a delivery batch flips the PO's own status to Approved as a side
 * effect, skipping accPO's invoice creation entirely. The mathematically-broken over-delivery
 * guard has its own dedicated regression test.
 */
class PurchaseOrderManualDeliveryFlowTest extends TestCase
{
    use ActingAsStaff;

    private function insertPendingPo(SuppliesVariant $variant, SuppliesStock $stock, int $qty, int $price): int
    {
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $response = $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * $price,
            'jenis_discount' => 1,
            'po_desc' => 'Manual delivery test PO',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'Workflow test supplies',
                'supplies_variant_name' => $variant->supplies_variant_name,
                'supplies_variant_sku' => $variant->supplies_variant_sku,
                'qty' => $qty,
                'supplies_variant_price' => $price,
                'unit_id_select' => $stock->unit_id,
            ]]),
        ]);
        $response->assertStatus(200);

        return (int) PurchaseOrder::orderByDesc('po_id')->value('po_id');
    }

    private function deliveryItemPayload(SuppliesVariant $variant, SuppliesStock $stock, int $qty): array
    {
        return [
            'supplies_variant_id' => $variant->supplies_variant_id,
            'name' => 'Workflow test supplies', // only read if the (broken) over-delivery guard rejects this item
            'pdod_sku' => $variant->supplies_variant_sku,
            'pdod_qty' => $qty,
            'unit_id' => $stock->unit_id,
        ];
    }

    public function test_inserting_a_manual_delivery_against_a_still_pending_po_does_not_touch_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $startingStock = $stock->ss_stock;

        $poId = $this->insertPendingPo($variant, $stock, qty: 10, price: 1000);
        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(1, (int) $po->status, 'the PO was never approved via accPO');

        $this->post('/insertPoDelivery', [
            'po_id' => $poId,
            'pdo_receiver' => 'Workflow Test Receiver',
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'First partial batch',
            'pdo_detail' => json_encode([$this->deliveryItemPayload($variant, $stock, 4)]),
        ])->assertStatus(200);

        $delivery = PurchaseOrderDelivery::where('po_id', $poId)->orderByDesc('pdo_id')->firstOrFail();
        $this->assertSame(1, (int) $delivery->status, 'a freshly inserted delivery batch should default to pending');

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'inserting a delivery must not touch stock before approval');

        $po->refresh();
        $this->assertSame(1, (int) $po->status, 'inserting (not approving) a delivery must not touch the PO status either');
    }

    public function test_approving_a_manual_delivery_increments_stock_and_flips_the_po_to_approved_with_no_invoice(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $startingStock = $stock->ss_stock;
        $qty = 6;

        $poId = $this->insertPendingPo($variant, $stock, qty: 10, price: 1000);

        $this->post('/insertPoDelivery', [
            'po_id' => $poId,
            'pdo_receiver' => 'Workflow Test Receiver',
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'First partial batch',
            'pdo_detail' => json_encode([$this->deliveryItemPayload($variant, $stock, $qty)]),
        ])->assertStatus(200);
        $delivery = PurchaseOrderDelivery::where('po_id', $poId)->orderByDesc('pdo_id')->firstOrFail();

        $accResponse = $this->post('/accPoDelivery', [
            'po_id' => $poId,
            'pdo_id' => $delivery->pdo_id,
            'pdo_receiver' => 'Workflow Test Receiver',
            'staff_id' => (int) DB::table('staffs')->where('status', 1)->value('staff_id'),
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'First partial batch',
            'pdo_detail' => json_encode([$this->deliveryItemPayload($variant, $stock, $qty)]),
            'status' => 2,
        ]);
        $accResponse->assertStatus(200);
        $this->assertSame('1', $accResponse->getContent());

        $delivery->refresh();
        $this->assertSame(2, (int) $delivery->status);

        $stock->refresh();
        $this->assertSame($startingStock + $qty, $stock->ss_stock, 'approving a manual delivery must increment stock, same mechanism as accPO\'s own auto-generated delivery');

        // The critical finding: the PO itself is now marked Approved, despite accPO never running.
        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(2, (int) $po->status, 'BUG: approving a delivery batch flips the parent PO to Approved as a side effect of updatePoDelivery()');

        // And because accPO never ran, no automatic invoice was ever created for this PO.
        $this->assertNull(
            PurchaseOrderDetailInvoice::where('po_id', $poId)->first(),
            'BUG: the PO is now "Approved" with zero invoices, since only accPO auto-generates one'
        );
    }

    public function test_declining_a_pending_delivery_leaves_stock_and_po_status_untouched(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $startingStock = $stock->ss_stock;

        $poId = $this->insertPendingPo($variant, $stock, qty: 10, price: 1000);

        $this->post('/insertPoDelivery', [
            'po_id' => $poId,
            'pdo_receiver' => 'Workflow Test Receiver',
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'Batch to be declined',
            'pdo_detail' => json_encode([$this->deliveryItemPayload($variant, $stock, 3)]),
        ])->assertStatus(200);
        $delivery = PurchaseOrderDelivery::where('po_id', $poId)->orderByDesc('pdo_id')->firstOrFail();

        $this->post('/declinePoDelivery', [
            'po_id' => $poId,
            'pdo_id' => $delivery->pdo_id,
            'pdo_receiver' => 'Workflow Test Receiver',
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'Batch to be declined',
            'pdo_detail' => json_encode([$this->deliveryItemPayload($variant, $stock, 3)]),
            'status' => 0,
        ])->assertStatus(200);

        $delivery->refresh();
        $this->assertSame(0, (int) $delivery->status);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'declining must not touch stock — insert never moved anything');

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(1, (int) $po->status, 'declining a delivery must not touch the PO status either — only approving does');
    }
}
