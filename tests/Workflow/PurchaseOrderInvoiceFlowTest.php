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
 * asserts against — builds on the base PurchaseOrderFlowTest pilot. Covers the invoice-acceptance
 * over-payment guard and the purchase_orders.status 3/4 recalculation it drives, which is the
 * actual money-calculation logic in this flow. Tanda Terima Invoice grouping (a separate tracking
 * dimension, po.pembayaran) is a different, not-yet-built pilot.
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

    public function test_accepting_the_automatic_invoice_flips_po_status_to_fully_covered(): void
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
        $this->assertSame(4, (int) $po->status, 'an accepted total equal to po_total flips PO status to 4 (fully covered)');
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
        $this->assertSame(4, (int) $po->status);

        // A second invoice for any positive amount must now be rejected on accept.
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
        $this->assertSame(4, (int) $po->status, 'a rejected acceptance must not change PO status');
    }

    public function test_declining_an_invoice_leaves_po_status_not_fully_covered(): void
    {
        $this->actingAsSuperAdminStaff();

        $poId = $this->insertAndApprovePo(qty: 3, price: 2000);
        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();

        $this->post('/declineInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 0])->assertOk();

        $invoice->refresh();
        $this->assertSame(0, (int) $invoice->status, 'declining sets the invoice status to 0');

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(3, (int) $po->status, 'a declined (uncounted) invoice leaves the accepted total below po_total, so status becomes 3');
    }

    /**
     * BUG (see KNOWN_ISSUES.md): `updateInvoicePO()` unconditionally calls `cekInvoice()` after
     * saving, regardless of the invoice's own status. Editing a still-PENDING invoice (never
     * accepted) re-triggers the same status recalculation acceptInvoicePO drives — moving
     * purchase_orders.status away from 2 with no accept/decline action at all.
     */
    public function test_editing_a_still_pending_invoice_flips_po_status_with_no_accept_action(): void
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
            'poi_total' => $invoice->poi_total, // even an unchanged total re-triggers cekInvoice()
        ])->assertOk();

        $invoice->refresh();
        $this->assertSame(1, (int) $invoice->status, 'the invoice itself is still pending — updateInvoicePO never touches status');

        $po->refresh();
        $this->assertSame(
            3,
            (int) $po->status,
            'BUG: merely editing a pending invoice flips purchase_orders.status away from 2, with no accept/decline ever called'
        );
    }

    /**
     * BUG (see KNOWN_ISSUES.md): editing an ALREADY-ACCEPTED invoice's total downward
     * retroactively re-runs cekInvoice(), which can flip purchase_orders.status back down from
     * "fully covered" — a PO can silently regress from status 4 to status 3 purely by editing an
     * invoice, without any new decline/reject action.
     */
    public function test_editing_an_accepted_invoices_total_downward_regresses_po_status(): void
    {
        $this->actingAsSuperAdminStaff();

        $poId = $this->insertAndApprovePo(qty: 5, price: 1000);
        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();

        $this->post('/acceptInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 2])->assertOk();
        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(4, (int) $po->status, 'fully accepted, PO is fully covered');

        $this->post('/updateInvoicePO', [
            'poi_id' => $invoice->poi_id,
            'po_id' => $poId,
            'poi_date' => now()->toDateString(),
            'poi_due' => now()->addDays(30)->toDateString(),
            'poi_total' => $invoice->poi_total - 1000, // now below po_total
        ])->assertOk();

        $po->refresh();
        $this->assertSame(
            3,
            (int) $po->status,
            'BUG: reducing an already-accepted invoice\'s total retroactively regresses the PO from fully-covered (4) back to partially-covered (3)'
        );
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
     * Confirms the same cekInvoice side-effect also fires on delete: soft-deleting a still-pending
     * invoice (never accepted) flips purchase_orders.status away from 2, same as editing one.
     */
    public function test_deleting_a_still_pending_invoice_also_flips_po_status(): void
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
        $this->assertSame(3, (int) $po->status, 'BUG: deleting a pending invoice also flips PO status via the same cekInvoice() side effect');
    }
}
