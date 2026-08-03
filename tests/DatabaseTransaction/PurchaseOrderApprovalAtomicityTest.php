<?php

namespace Tests\DatabaseTransaction;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Extends the Phase 3 pilot (tests/Workflow/PurchaseOrderFlowTest.php,
 * cdocs/testing/workflows/PURCHASE_ORDER_FLOW.md). `accPO` has no
 * DB::transaction() (unlike `tolakPO` — see guides/DATABASE_TRANSACTION_GUIDE.md),
 * so this documents exactly how far a mid-loop failure gets before it stops,
 * rather than assuming the whole request either fully applies or fully
 * doesn't.
 *
 * Trigger: PurchaseOrderDeliveryDetail::insertPoDeliveryDetail() saves the
 * delivery-detail row FIRST, then looks up SuppliesStock by
 * supplies_id+unit_id — if that lookup misses (e.g. a bad unit_id), it calls
 * `$s->ss_stock += ...` on null and throws, uncaught, all the way up through
 * accPO. A second, valid line item processed earlier in the same request
 * has already been fully committed by that point.
 */
class PurchaseOrderApprovalAtomicityTest extends TestCase
{
    use ActingAsStaff;

    public function test_a_mid_loop_failure_leaves_earlier_items_partially_committed(): void
    {
        $this->actingAsSuperAdminStaff();

        $variantA = SuppliesVariant::where('supplies_id', 1)->where('status', 1)->firstOrFail();
        $stockA = SuppliesStock::where('supplies_id', $variantA->supplies_id)->where('status', 1)->firstOrFail();

        $variantB = SuppliesVariant::where('supplies_id', 2)->where('status', 1)->firstOrFail();
        $bogusUnitId = 999999; // guaranteed to match no supplies_stocks row for variantB's supplies_id

        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();
        $qty = 3;

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * 1000 * 2,
            'jenis_discount' => 1,
            'po_desc' => 'DB transaction atomicity test PO',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([
                [
                    'supplies_variant_id' => $variantA->supplies_variant_id,
                    'supplies_name' => 'Item A (valid)',
                    'supplies_variant_name' => $variantA->supplies_variant_name,
                    'supplies_variant_sku' => $variantA->supplies_variant_sku,
                    'qty' => $qty,
                    'supplies_variant_price' => 1000,
                    'unit_id_select' => $stockA->unit_id,
                ],
                [
                    'supplies_variant_id' => $variantB->supplies_variant_id,
                    'supplies_name' => 'Item B (forced failure)',
                    'supplies_variant_name' => $variantB->supplies_variant_name,
                    'supplies_variant_sku' => $variantB->supplies_variant_sku,
                    'qty' => $qty,
                    'supplies_variant_price' => 1000,
                    'unit_id_select' => $bogusUnitId,
                ],
            ]),
        ])->json();

        $startingStockA = $stockA->ss_stock;
        $logCountBefore = DB::table('log_stocks')->count();

        $response = $this->post('/accPO', [
            'data' => [
                'po_id' => $poId,
                'po_supplier' => $supplier->supplier_id,
                'items' => [
                    [
                        'supplies_variant_id' => $variantA->supplies_variant_id,
                        'unit_id' => $stockA->unit_id,
                        'pod_sku' => $variantA->supplies_variant_sku,
                        'pod_qty' => $qty,
                    ],
                    [
                        'supplies_variant_id' => $variantB->supplies_variant_id,
                        'unit_id' => $bogusUnitId,
                        'pod_sku' => $variantB->supplies_variant_sku,
                        'pod_qty' => $qty,
                    ],
                ],
            ],
        ]);

        // Documents current behavior: an uncaught error, not a clean
        // {status:-1, message:...} business-rule response.
        $response->assertStatus(500);

        // Item A (processed first) is fully, permanently committed.
        $stockA->refresh();
        $this->assertSame(
            $startingStockA + $qty,
            $stockA->ss_stock,
            'the first, valid line item keeps its stock increment despite the later failure'
        );
        $this->assertSame(
            $logCountBefore + 1,
            DB::table('log_stocks')->count(),
            'the first line item still gets its log_stocks entry'
        );
        $this->assertDatabaseHas('purchase_delivery_orders_details', [
            'pdod_qty' => $qty,
            'supplies_variant_id' => $variantA->supplies_variant_id,
        ]);

        // Item B's delivery-detail row is created (save() runs before the
        // stock lookup) but its stock is never touched and no log is written.
        $this->assertDatabaseHas('purchase_delivery_orders_details', [
            'supplies_variant_id' => $variantB->supplies_variant_id,
            'pdod_qty' => $qty,
        ]);

        // The rest of accPO never ran: no invoice, PO still pending approval.
        $this->assertNull(PurchaseOrderDetailInvoice::where('po_id', $poId)->first());
        $po = PurchaseOrder::find($poId);
        $this->assertSame(1, (int) $po->status, 'the PO is left pending approval, neither approved nor rejected');
        $this->assertNull($po->acc_by);
    }
}
