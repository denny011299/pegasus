<?php

namespace Tests\Workflow;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PURCHASE_ORDER_INVOICE_FLOW.md for the fully-traced flow this
 * asserts against — builds on the base PurchaseOrderFlowTest pilot.
 *
 * Updated 2026-08-06 (GitHub issue #14, closes #14): `cekInvoice()` used to move
 * `purchase_orders.status` to 3/4 ("belum lunas"/"lunas penuh") on every accept/decline/edit/delete
 * of an invoice, with no guard on the invoice's own status. Per the PM's confirmed flow,
 * `purchase_orders.status` only ever holds {1 menunggu konfirmasi, 2 disetujui, -1 ditolak} —
 * "belum terbayar / menunggu tanda terima / terbayar" is driven entirely by `pembayaran`
 * (1/3/2), a separate tracking dimension covered by `PURCHASE_ORDER_TANDA_TERIMA_FLOW.md`, not by
 * this invoice-acceptance flow. `cekInvoice()` is now a documented no-op (see its docblock in
 * `PurchaseOrderDetailInvoice.php`), so this suite asserts `purchase_orders.status` stays at 2
 * (its post-`accPO` value) through every invoice accept/decline/edit/delete scenario below — the
 * real money-calculation logic that remains is `purchase_order_detail_invoices.status` itself
 * (2=accepted/0=declined) and `acceptInvoicePO`'s over-payment guard, both untouched by this fix.
 */
class PurchaseOrderInvoiceFlowTest extends TestCase
{
    use ActingAsStaff;

    /**
     * Inserts and approves a PO exactly like PurchaseOrderFlowTest's pilot — accPO creates the
     * automatic invoice covering the full po_total (status=1) as a side effect.
     */
    private function insertAndApprovePo(int $qty, int $price): int
    {
        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * $price,
            'jenis_discount' => 1,
            'po_desc' => 'Invoice flow test PO',
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

        return $poId;
    }

    public function test_accepting_the_automatic_invoice_sets_invoice_status_without_touching_po_status(): void
    {
        $this->actingAsSuperAdminStaff();

        $poId = $this->insertAndApprovePo(qty: 5, price: 1000);
        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(2, (int) $po->status, 'approval alone leaves the PO at status 2, before any invoice is accepted');

        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();
        $this->assertSame(1, (int) $invoice->status, 'the automatic invoice starts pending acceptance');
        $this->assertEquals($po->po_total, $invoice->poi_total, 'the automatic invoice covers the full po_total');

        $response = $this->post('/acceptInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 2]);
        $this->assertNotSame('-1', $response->getContent(), 'accepting the only invoice, for the full amount, must not hit the over-payment guard');

        $invoice->refresh();
        $po->refresh();
        $this->assertSame(2, (int) $invoice->status, 'accepting sets the invoice status to 2');
        $this->assertSame(2, (int) $po->status, 'accepting an invoice no longer moves purchase_orders.status (closes #14) — it stays at 2, driven only by accPO/tolakPO');
    }

    public function test_accepting_a_second_invoice_that_would_exceed_po_total_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $poId = $this->insertAndApprovePo(qty: 5, price: 1000);
        $po = PurchaseOrder::findOrFail($poId);
        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();

        // Accept the automatic invoice first — it already covers po_total in full.
        $this->post('/acceptInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 2])->assertOk();
        $po->refresh();
        $this->assertSame(2, (int) $po->status, 'po.status stays at 2 regardless of invoice acceptance (closes #14)');

        // A second invoice for any positive amount must now be rejected on accept — this guard is
        // driven by summing purchase_order_detail_invoices.status=2 rows, independent of po.status.
        // insertInvoicePO's response is the PO's status, not the new invoice's id (see the flow
        // doc) — fetch the created row directly instead.
        $this->post('/insertInvoicePO', [
            'po_id' => $poId,
            'poi_due' => now()->addDays(30)->toDateString(),
            'poi_total' => 10000,
        ])->assertOk();
        $secondPoiId = (int) PurchaseOrderDetailInvoice::where('po_id', $poId)->orderByDesc('poi_id')->value('poi_id');

        $response = $this->post('/acceptInvoicePO', ['poi_id' => $secondPoiId, 'status' => 2]);
        $this->assertSame('-1', $response->getContent(), 'accepting an invoice that would exceed po_total must be rejected');

        $secondInvoice = PurchaseOrderDetailInvoice::findOrFail($secondPoiId);
        $this->assertSame(1, (int) $secondInvoice->status, 'a rejected acceptance must leave the second invoice untouched');

        $po->refresh();
        $this->assertSame(2, (int) $po->status, 'a rejected acceptance must not change PO status');
    }

    public function test_declining_an_invoice_does_not_change_po_status(): void
    {
        $this->actingAsSuperAdminStaff();

        $poId = $this->insertAndApprovePo(qty: 3, price: 2000);
        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();

        $this->post('/declineInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 0])->assertOk();

        $invoice->refresh();
        $this->assertSame(0, (int) $invoice->status, 'declining sets the invoice status to 0');

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(2, (int) $po->status, 'declining an invoice no longer changes purchase_orders.status (closes #14) — pembayaran, not status, tracks payment progress');
    }

    /**
     * ✅ FIXED (2026-08-06, closes #14): `updateInvoicePO()` still calls `cekInvoice()`
     * unconditionally after saving, but `cekInvoice()` itself is now a no-op (see its docblock) —
     * so editing a still-pending invoice (never accepted) can no longer move purchase_orders.status
     * with no accept/decline action ever happening.
     */
    public function test_editing_a_still_pending_invoice_no_longer_touches_po_status(): void
    {
        $this->actingAsSuperAdminStaff();

        $poId = $this->insertAndApprovePo(qty: 5, price: 1000);
        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(2, (int) $po->status, 'fresh approval, before any invoice action, sits at status 2');

        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();
        $this->assertSame(1, (int) $invoice->status, 'the automatic invoice is still pending — never accepted or declined');

        $this->post('/updateInvoicePO', [
            'poi_id' => $invoice->poi_id,
            'po_id' => $poId,
            'poi_date' => now()->toDateString(),
            'poi_due' => now()->addDays(30)->toDateString(),
            'poi_total' => $invoice->poi_total, // even an unchanged total re-triggers the (now no-op) cekInvoice()
        ])->assertOk();

        $invoice->refresh();
        $this->assertSame(1, (int) $invoice->status, 'the invoice itself is still pending — updateInvoicePO never touches status');

        $po->refresh();
        $this->assertSame(2, (int) $po->status, 'editing a pending invoice must not move purchase_orders.status');
    }

    /**
     * ✅ FIXED (2026-08-06, closes #14): editing an already-accepted invoice's total downward used
     * to retroactively regress purchase_orders.status from 4 back to 3 via cekInvoice(). Now that
     * cekInvoice() is a no-op, po.status simply stays at 2 throughout.
     */
    public function test_editing_an_accepted_invoices_total_downward_no_longer_regresses_po_status(): void
    {
        $this->actingAsSuperAdminStaff();

        $poId = $this->insertAndApprovePo(qty: 5, price: 1000);
        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();

        $this->post('/acceptInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 2])->assertOk();
        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(2, (int) $po->status, 'accepting the invoice does not move po.status');

        $this->post('/updateInvoicePO', [
            'poi_id' => $invoice->poi_id,
            'po_id' => $poId,
            'poi_date' => now()->toDateString(),
            'poi_due' => now()->addDays(30)->toDateString(),
            'poi_total' => $invoice->poi_total - 1000, // now below po_total
        ])->assertOk();

        $po->refresh();
        $this->assertSame(2, (int) $po->status, 'reducing an already-accepted invoice\'s total must not regress purchase_orders.status');
    }

    /**
     * ✅ FIXED (2026-08-04): `deleteInvoicePO()` used to call `cekInvoice($t->po_id)` OUTSIDE its
     * own `if ($t) { ... }` null-check — an invalid poi_id crashed instead of failing cleanly. The
     * `cekInvoice()` call is now inside the null-check, same as everywhere else this pattern
     * appears in this codebase.
     */
    public function test_deleting_an_invoice_with_an_invalid_id_fails_cleanly_instead_of_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $bogusPoiId = 999999;
        $response = $this->post('/deleteInvoicePO', ['poi_id' => $bogusPoiId, 'status' => -1]);

        $response->assertOk();
    }

    /**
     * ✅ FIXED (2026-08-06, closes #14): deleting a still-pending invoice used to also flip
     * purchase_orders.status via the same cekInvoice() side effect as editing. Now it doesn't.
     */
    public function test_deleting_a_still_pending_invoice_no_longer_flips_po_status(): void
    {
        $this->actingAsSuperAdminStaff();

        $poId = $this->insertAndApprovePo(qty: 4, price: 1000);
        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(2, (int) $po->status);

        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();

        $this->post('/deleteInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => -1])->assertOk();

        $invoice->refresh();
        $this->assertSame(-1, (int) $invoice->status, 'deleteInvoicePO soft-deletes the invoice');

        $po->refresh();
        $this->assertSame(2, (int) $po->status, 'deleting a pending invoice must not move purchase_orders.status');
    }
}
