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
 * Bug: `SupplierController::accPoDelivery()`'s over-delivery guard is mathematically broken:
 *
 *   $total = PurchaseOrderDeliveryDetail::whereIn('pdo_id', $p->pluck('pdo_id'))
 *       ->where('supplies_variant_id','=',$value['supplies_variant_id'])->sum('pdod_qty');
 *   if ($total + $value['pdod_qty'] > $value["pdod_qty"]) { array_push($bermasalah, ...); }
 *
 * The right-hand side reuses the SAME variable already added on the left — it never reads the
 * PO's actually-ordered quantity at all. This simplifies to `$total > 0`: any item that has ever
 * received a single prior approved delivery batch is rejected on EVERY subsequent delivery
 * attempt, no matter how small the new amount or how much remains outstanding on the order.
 *
 * See cdocs/testing/workflows/PURCHASE_ORDER_MANUAL_DELIVERY_FLOW.md and
 * cdocs/testing/KNOWN_ISSUES.md. Confirmed 2026-08-02, deliberately deferred — this test
 * characterizes the CURRENT (broken) behavior on purpose.
 */
class PurchaseOrderDeliveryOverDeliveryGuardIsBrokenTest extends TestCase
{
    use ActingAsStaff;

    public function test_a_second_small_delivery_for_an_already_partially_delivered_item_is_rejected_even_though_the_order_is_far_from_fulfilled(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();
        $staffId = (int) DB::table('staffs')->where('status', 1)->value('staff_id');

        $orderedQty = 1000; // a large order, nowhere near fulfilled by either delivery below

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $orderedQty * 1000,
            'jenis_discount' => 1,
            'po_desc' => 'Broken over-delivery guard regression test',
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
        // ordered — a correct guard would allow this easily. The broken guard instead compares
        // 1 (already delivered) + 1 (new) > 1 (new) — always true — and rejects it.
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

        $response->assertJson(['status' => -1]);
        $response->assertJsonFragment(['message' => 'Jumlah penerimaan melebihi jumlah pemesanan untuk barang : Workflow test supplies']);

        $secondDelivery->refresh();
        $this->assertSame(1, (int) $secondDelivery->status, 'the second delivery batch must be left pending, not approved, given the rejection');

        $stock->refresh();
        $this->assertSame($stockAfterFirstDelivery, $stock->ss_stock, 'the rejected second delivery must not add to stock');

        $po = PurchaseOrder::findOrFail($poId);
        // Note: the PO's own status was ALREADY flipped to 2 by the first accPoDelivery call
        // (see PURCHASE_ORDER_MANUAL_DELIVERY_FLOW.md's separate finding) — this assertion is
        // about the delivery batch's own status, not re-litigating that one.
        $this->assertSame(2, (int) $po->status);
    }
}
