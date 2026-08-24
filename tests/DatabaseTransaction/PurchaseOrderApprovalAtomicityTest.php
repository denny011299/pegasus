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
 * cdocs/testing/workflows/PURCHASE_ORDER_FLOW.md).
 *
 * Trigger: PurchaseOrderDeliveryDetail::insertPoDeliveryDetail() saves the
 * delivery-detail row FIRST, then looks up SuppliesStock by
 * supplies_id+unit_id — if that lookup misses (e.g. a bad unit_id), it calls
 * `$s->ss_stock += ...` on null and throws, uncaught, all the way up through
 * accPO.
 *
 * ✅ FIXED 2026-08-24: this test used to DOCUMENT the partial-commit bug — a
 * valid line item processed earlier in the same request kept its stock
 * increment, its log_stocks row, and its delivery-detail row permanently,
 * while the PO itself stayed pending, so re-approving double-credited that
 * item. `accPO()` now wraps its whole write phase (delivery header + per-item
 * loop + invoice + status flip) in DB::beginTransaction()/commit with a
 * rollBack on throw, mirroring accProduction()/accStockOpname(). The
 * assertions below are flipped accordingly: nothing survives the failure.
 *
 * The uncaught 500 itself is unchanged and still asserted — this fix is about
 * atomicity, not about converting the underlying null-lookup crash into a
 * clean business-rule response.
 */
class PurchaseOrderApprovalAtomicityTest extends TestCase
{
    use ActingAsStaff;

    public function test_a_mid_loop_failure_now_rolls_back_every_earlier_item(): void
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

        // Still an uncaught error, not a clean {status:-1, message:...} business-rule
        // response — unchanged by the atomicity fix.
        $response->assertStatus(500);

        // FIXED: item A (processed first) is rolled back completely.
        $stockA->refresh();
        $this->assertSame(
            $startingStockA,
            $stockA->ss_stock,
            'the first, valid line item must NOT keep its stock increment once a later item fails'
        );
        $this->assertSame(
            $logCountBefore,
            DB::table('log_stocks')->count(),
            'no log_stocks entry survives the rollback either'
        );
        $this->assertDatabaseMissing('purchase_delivery_orders_details', [
            'pdod_qty' => $qty,
            'supplies_variant_id' => $variantA->supplies_variant_id,
        ]);

        // Item B's delivery-detail row (save() runs before the stock lookup that
        // throws) is rolled back too, rather than being left orphaned.
        $this->assertDatabaseMissing('purchase_delivery_orders_details', [
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
