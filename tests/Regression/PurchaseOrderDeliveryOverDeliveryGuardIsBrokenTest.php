<?php

namespace Tests\Regression;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-04): `SupplierController::accPoDelivery()`'s over-delivery guard used to be
 * mathematically broken:
 *
 *   $total = PurchaseOrderDeliveryDetail::whereIn('pdo_id', $p->pluck('pdo_id'))
 *       ->where('supplies_variant_id','=',$value['supplies_variant_id'])->sum('pdod_qty');
 *   if ($total + $value['pdod_qty'] > $value["pdod_qty"]) { array_push($bermasalah, ...); }
 *
 * The right-hand side reused the SAME variable already added on the left — it never read the
 * PO's actually-ordered quantity at all. This simplified to `$total > 0`: any item that had ever
 * received a single prior approved delivery batch was rejected on EVERY subsequent delivery
 * attempt, no matter how small the new amount or how much remained outstanding on the order —
 * while a genuinely oversized single delivery was never caught either.
 *
 * Fix: the right-hand side now reads the PO's actual ordered quantity from
 * `purchase_orders_details.pod_qty` for that item, so the guard compares cumulative delivered
 * (prior approved batches + this one) against what was really ordered.
 *
 * See cdocs/testing/workflows/PURCHASE_ORDER_MANUAL_DELIVERY_FLOW.md and
 * cdocs/testing/KNOWN_ISSUES.md. Class name kept for history — this test now verifies the fix
 * instead of characterizing the bug.
 */
class PurchaseOrderDeliveryOverDeliveryGuardIsBrokenTest extends TestCase
{
    use ActingAsStaff;

    private function createOrder(Supplier $supplier, SuppliesVariant $variant, SuppliesStock $stock, int $orderedQty): int
    {
        return (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $orderedQty * 1000,
            'jenis_discount' => 1,
            'po_desc' => 'Over-delivery guard regression test',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'Workflow test supplies',
                'supplies_variant_name' => $variant->supplies_variant_name,
                'supplies_variant_sku' => $variant->supplies_variant_sku,
                'qty' => $orderedQty,
                'supplies_variant_price' => 1000,
                'unit_id_select' => $stock->unit_id,
            ]]),
        ])->json();
    }

    public function test_a_second_small_delivery_for_an_already_partially_delivered_item_is_now_correctly_accepted(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();
        $staffId = (int) DB::table('staffs')->where('status', 1)->value('staff_id');

        $orderedQty = 1000; // a large order, nowhere near fulfilled by either delivery below
        $poId = $this->createOrder($supplier, $variant, $stock, $orderedQty);

        $itemPayload = fn (int $qty) => [
            'supplies_variant_id' => $variant->supplies_variant_id,
            'name' => 'Workflow test supplies',
            'pdod_sku' => $variant->supplies_variant_sku,
            'pdod_qty' => $qty,
            'unit_id' => $stock->unit_id,
        ];

        // First delivery: a small batch (1 unit) out of an order for 1000 — obviously legitimate.
        $this->post('/insertPoDelivery', [
            'po_id' => $poId,
            'pdo_receiver' => 'Regression Test Receiver',
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'First tiny batch',
            'pdo_detail' => json_encode([$itemPayload(1)]),
        ])->assertStatus(200);
        $firstDelivery = PurchaseOrderDelivery::where('po_id', $poId)->orderByDesc('pdo_id')->firstOrFail();

        $this->post('/accPoDelivery', [
            'po_id' => $poId,
            'pdo_id' => $firstDelivery->pdo_id,
            'pdo_receiver' => 'Regression Test Receiver',
            'staff_id' => $staffId,
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'First tiny batch',
            'pdo_detail' => json_encode([$itemPayload(1)]),
            'status' => 2,
        ])->assertStatus(200);

        $stock->refresh();
        $stockAfterFirstDelivery = $stock->ss_stock;

        // Second delivery: another tiny batch (1 unit). 1 + 1 = 2, nowhere close to the 1000
        // ordered — the fixed guard now allows this, instead of comparing 1 (already delivered) +
        // 1 (new) > 1 (new), which was always true.
        $this->post('/insertPoDelivery', [
            'po_id' => $poId,
            'pdo_receiver' => 'Regression Test Receiver',
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'Second tiny batch',
            'pdo_detail' => json_encode([$itemPayload(1)]),
        ])->assertStatus(200);
        $secondDelivery = PurchaseOrderDelivery::where('po_id', $poId)->orderByDesc('pdo_id')->firstOrFail();

        $response = $this->post('/accPoDelivery', [
            'po_id' => $poId,
            'pdo_id' => $secondDelivery->pdo_id,
            'pdo_receiver' => 'Regression Test Receiver',
            'staff_id' => $staffId,
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'Second tiny batch',
            'pdo_detail' => json_encode([$itemPayload(1)]),
            'status' => 2,
        ]);

        $response->assertOk();
        $this->assertSame('1', $response->getContent(), 'a legitimate, far-from-fulfilled second delivery must now be accepted');

        $secondDelivery->refresh();
        $this->assertSame(2, (int) $secondDelivery->status, 'the second delivery batch must now be approved');

        $stock->refresh();
        $this->assertSame(
            $stockAfterFirstDelivery + 1,
            $stock->ss_stock,
            'the accepted second delivery must add its own quantity to stock'
        );

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(2, (int) $po->status);
    }

    public function test_a_delivery_batch_exceeding_the_remaining_ordered_quantity_is_correctly_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();
        $staffId = (int) DB::table('staffs')->where('status', 1)->value('staff_id');

        $orderedQty = 10;
        $poId = $this->createOrder($supplier, $variant, $stock, $orderedQty);

        $itemPayload = fn (int $qty) => [
            'supplies_variant_id' => $variant->supplies_variant_id,
            'name' => 'Workflow test supplies',
            'pdod_sku' => $variant->supplies_variant_sku,
            'pdod_qty' => $qty,
            'unit_id' => $stock->unit_id,
        ];

        // A single delivery batch of 11 units against an order for only 10 — genuinely over the
        // ordered amount. The pre-fix guard never caught this at all (it only ever compared
        // $total > 0, never the actual ordered qty).
        $this->post('/insertPoDelivery', [
            'po_id' => $poId,
            'pdo_receiver' => 'Regression Test Receiver',
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'Oversized single batch',
            'pdo_detail' => json_encode([$itemPayload($orderedQty + 1)]),
        ])->assertStatus(200);
        $delivery = PurchaseOrderDelivery::where('po_id', $poId)->orderByDesc('pdo_id')->firstOrFail();

        $stock->refresh();
        $stockBeforeApproval = $stock->ss_stock;

        $response = $this->post('/accPoDelivery', [
            'po_id' => $poId,
            'pdo_id' => $delivery->pdo_id,
            'pdo_receiver' => 'Regression Test Receiver',
            'staff_id' => $staffId,
            'pdo_date' => now()->toDateString(),
            'pdo_phone' => '081200000000',
            'pdo_desc' => 'Oversized single batch',
            'pdo_detail' => json_encode([$itemPayload($orderedQty + 1)]),
            'status' => 2,
        ]);

        $response->assertJson(['status' => -1]);
        $response->assertJsonFragment(['message' => 'Jumlah penerimaan melebihi jumlah pemesanan untuk barang : Workflow test supplies']);

        $delivery->refresh();
        $this->assertSame(1, (int) $delivery->status, 'an over-ordered delivery batch must be left pending, not approved');

        $stock->refresh();
        $this->assertSame($stockBeforeApproval, $stock->ss_stock, 'a rejected delivery must not add to stock');
    }
}
