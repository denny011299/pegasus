<?php

namespace Tests\Regression;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub issue #14 (closes #14). See cdocs/testing/KNOWN_ISSUES.md and
 * cdocs/docs/flows/purchase-order-invoice/FLOW.md for the full writeup.
 *
 * `SupplierController::tolakPO()`'s stock-reversal branch is gated on
 * `if ($p->status == 2)`. Before this fix, `PurchaseOrderDetailInvoice::cekInvoice()` moved
 * `purchase_orders.status` to 3 or 4 on every accept/decline/edit/delete of an invoice — so once
 * a PO's automatic invoice had been accepted (a completely routine, expected action), calling
 * `tolakPO` afterward would set `status = -1` and cascade-cancel every related record WITHOUT
 * reversing the `supplies_stocks` that `accPO` had added, because `status` was no longer exactly
 * `2`. The cancellation itself reported success; only the stock silently failed to roll back.
 *
 * Per the PM's confirmed status flow (2026-08-06), `purchase_orders.status` only ever holds
 * {1, 2, -1} — "belum terbayar"/"menunggu tanda terima"/"terbayar" are `pembayaran`-driven
 * sub-states of `status == 2`, and "ditolak" must be reachable from any of them. `cekInvoice()`
 * is now a no-op (see its docblock), so `status` stays at 2 through invoice acceptance, and
 * `tolakPO`'s reversal branch fires correctly.
 */
class PurchaseOrderRejectAfterInvoiceAcceptanceSkippedStockReversalTest extends TestCase
{
    use ActingAsStaff;

    public function test_rejecting_a_po_after_its_invoice_was_accepted_still_reverses_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $qty = 5;
        $price = 1000;
        $startingStock = $stock->ss_stock;

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * $price,
            'jenis_discount' => 1,
            'po_desc' => 'Reject-after-invoice-acceptance regression PO',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'Regression test supplies',
                'supplies_variant_name' => $variant->supplies_variant_name,
                'supplies_variant_sku' => $variant->supplies_variant_sku,
                'qty' => $qty,
                'supplies_variant_price' => $price,
                'unit_id_select' => $stock->unit_id,
            ]]),
        ])->json();

        $this->post('/accPO', [
            'data' => [
                'po_id' => $poId,
                'po_supplier' => $supplier->supplier_id,
                'items' => [[
                    'supplies_variant_id' => $variant->supplies_variant_id,
                    'unit_id' => $stock->unit_id,
                    'pod_sku' => $variant->supplies_variant_sku,
                    'pod_qty' => $qty,
                ]],
            ],
        ])->assertStatus(200);

        $stock->refresh();
        $this->assertSame($startingStock + $qty, $stock->ss_stock, 'approval must add the ordered qty to stock');

        // Routine action: accept the PO's automatic invoice. Before the fix, this moved
        // purchase_orders.status to 4 — which is exactly what broke tolakPO's reversal below.
        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();
        $this->post('/acceptInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 2])->assertOk();

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(2, (int) $po->status, 'accepting the invoice must leave purchase_orders.status at 2 (closes #14)');

        // Now reject the already-approved PO — tolakPO must still reverse the stock it added.
        $this->post('/tolakPO', ['po_id' => $poId])->assertOk();

        $po->refresh();
        $this->assertSame(-1, (int) $po->status, 'rejecting an approved PO must set status to -1');

        $stock->refresh();
        $this->assertSame(
            $startingStock,
            $stock->ss_stock,
            'rejecting the PO must reverse the stock accPO added, even though its invoice was already accepted'
        );
    }
}
