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
 * Bug: `SupplierController::generateTandaTerima($id, $kode)` (the older, non-invoice-driven Tt
 * grouping path — GET /generateTandaTerima/{supplier_id}/{bank_kode}) filters
 * `purchase_orders.pembayaran = 0`. Grepping every read/write of `pembayaran` across the codebase
 * confirms it is NEVER set to `0` anywhere — the column defaults to `1` and every write path sets
 * `1`, `2`, or `3` (see cdocs/testing/workflows/PURCHASE_ORDER_TANDA_TERIMA_FLOW.md). This means
 * the method's own `count($param["data"]) <= 0` guard is unconditionally true for any real PO in
 * the system — it always returns a bare `-1`, regardless of the supplier/bank chosen or how many
 * genuinely fully-invoiced (status=4) POs exist that a user would expect to be grouped.
 *
 * Unlike the other confirmed-dead-code findings in this program (`pelunasanPurchaseOrder`,
 * `ProductionController::updateProduction`), this one IS reachable from the UI — two real pages
 * wire up a click handler that navigates the browser directly to this route. A user clicking
 * "Generate Tanda Terima" (the older button) today silently gets a blank page showing "-1" with no
 * error message, no matter what they select.
 *
 * Confirmed 2026-08-02, deliberately deferred per this project's "queue bugs, don't fix" policy —
 * see KNOWN_ISSUES.md. This test characterizes the CURRENT (always-fails) behavior on purpose,
 * using exactly the kind of PO (status=4, real pembayaran=1) a user would expect this feature to
 * find and group.
 */
class GenerateTandaTerimaAlwaysReturnsMinusOneTest extends TestCase
{
    use ActingAsStaff;

    public function test_a_fully_invoiced_po_is_never_found_because_pembayaran_is_never_zero(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => 5000,
            'jenis_discount' => 1,
            'po_desc' => 'generateTandaTerima dead-code regression test',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'Workflow test supplies',
                'supplies_variant_name' => $variant->supplies_variant_name,
                'supplies_variant_sku' => $variant->supplies_variant_sku,
                'qty' => 5,
                'supplies_variant_price' => 1000,
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
                    'pod_qty' => 5,
                ]],
            ],
        ])->assertStatus(200);

        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();
        $this->post('/acceptInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 2])->assertOk();

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(4, (int) $po->status, 'fully accepted, exactly the state generateTandaTerima looks for');
        $this->assertSame(1, (int) $po->pembayaran, 'the real, only-ever-produced default — never 0');
        $this->assertNull($po->tt_id);

        $bankCode = 'BCA';
        $response = $this->get("/generateTandaTerima/{$supplier->supplier_id}/{$bankCode}");

        $response->assertStatus(200);
        $this->assertSame('-1', $response->getContent(), 'BUG: always -1, since pembayaran=0 never matches any real PO');

        $po->refresh();
        $this->assertNull($po->tt_id, 'nothing is grouped — the method never reaches its own grouping logic');
    }
}
