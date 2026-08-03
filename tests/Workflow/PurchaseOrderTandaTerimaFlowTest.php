<?php

namespace Tests\Workflow;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use App\Models\purchase_order_tt;
use Illuminate\Http\UploadedFile;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PURCHASE_ORDER_TANDA_TERIMA_FLOW.md for the fully-traced flow this
 * asserts against — the payment-batching sub-flow both PurchaseOrderFlowTest and
 * PurchaseOrderInvoiceFlowTest deferred. Note: the model class is `App\purchase_order_tt`
 * (lowercase snake_case), not PascalCase like every other model in this codebase.
 */
class PurchaseOrderTandaTerimaFlowTest extends TestCase
{
    use ActingAsStaff;

    /**
     * Inserts and approves a PO, then accepts its automatic invoice in full — identical to
     * PurchaseOrderInvoiceFlowTest's fixture. Ends with the PO at status=4 (fully covered) and
     * pembayaran still at its default of 1 (untouched by invoice acceptance), ready to be grouped
     * into a Tt.
     */
    private function insertPoWithAcceptedInvoice(int $qty, int $price, ?Supplier $supplier = null): array
    {
        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier ??= Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * $price,
            'jenis_discount' => 1,
            'po_desc' => 'Tt flow test PO',
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

        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->firstOrFail();
        $this->post('/acceptInvoicePO', ['poi_id' => $invoice->poi_id, 'status' => 2])->assertOk();

        return ['po_id' => $poId, 'poi_id' => (int) $invoice->poi_id, 'supplier' => $supplier];
    }

    private function generateTt(int $poiId): array
    {
        return $this->generateTtForMany([$poiId]);
    }

    /** @param int[] $poiIds */
    private function generateTtForMany(array $poiIds): array
    {
        $response = $this->get('/generateTandaTerimaInvoice?'.http_build_query(['poi_id' => $poiIds]));
        $response->assertStatus(200);

        return $response->json();
    }

    /**
     * Forces a supplier's bank_id to a specific real bank — used to deterministically construct
     * same-bank/different-bank scenarios rather than relying on whatever the seed snapshot happens
     * to already contain.
     */
    private function forceSupplierBank(Supplier $supplier, int $bankId): Supplier
    {
        $supplier->bank_id = $bankId;
        $supplier->save();

        return $supplier;
    }

    /**
     * accTt's controller unconditionally reads `$data["tt_image"]`, which is only ever populated
     * when the request carries an `image` upload (`SupplierController.php:533-535`) — omitting it
     * crashes with "Undefined array key 'tt_image'". Confirmed NOT a real bug: the real frontend
     * (tt.js's `.btn-save` handler) client-side-blocks the accept button entirely until a bukti
     * transfer image is chosen, so `image` is always present in real usage — same shape as the
     * CashArmada `photo` fixture-gap precedent, not a new finding. Every real call must send one.
     */
    private function acceptTt(int $ttId, string $desc = 'Workflow test acceptance'): void
    {
        $this->post('/accTt', [
            'tt_id' => $ttId,
            'tt_desc' => $desc,
            'image' => UploadedFile::fake()->image('bukti-transfer.jpg'),
        ])->assertStatus(200);
    }

    public function test_generating_a_tt_groups_the_po_and_sets_pembayaran_to_pending(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->insertPoWithAcceptedInvoice(qty: 5, price: 1000);
        $po = PurchaseOrder::findOrFail($fx['po_id']);
        $this->assertSame(1, (int) $po->pembayaran, 'pembayaran must still be at its default before any Tt exists');
        $this->assertNull($po->tt_id);

        $result = $this->generateTt($fx['poi_id']);
        $this->assertSame(1, $result['status']);
        $this->assertNotEmpty($result['tt_id']);

        $po->refresh();
        $this->assertSame((int) $result['tt_id'], (int) $po->tt_id, 'the PO must be linked to the new Tt batch');
        $this->assertSame(3, (int) $po->pembayaran, 'grouping into a pending Tt sets pembayaran=3');

        $tt = purchase_order_tt::findOrFail($result['tt_id']);
        $this->assertSame(1, (int) $tt->status, 'a freshly generated Tt is pending approval');
        $this->assertEquals($po->po_total, $tt->tt_total, 'tt_total must equal the sum of the grouped POs\' po_total');
    }

    public function test_accepting_the_tt_marks_the_po_as_paid(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->insertPoWithAcceptedInvoice(qty: 3, price: 2000);
        $result = $this->generateTt($fx['poi_id']);
        $ttId = (int) $result['tt_id'];

        $this->acceptTt($ttId);

        $tt = purchase_order_tt::findOrFail($ttId);
        $this->assertSame(2, (int) $tt->status);

        $po = PurchaseOrder::findOrFail($fx['po_id']);
        $this->assertSame(2, (int) $po->pembayaran, 'accepting the Tt marks every grouped PO as paid (pembayaran=2)');
        $this->assertSame($ttId, (int) $po->tt_id, 'accepting does not clear the tt_id link');
    }

    public function test_declining_the_tt_releases_the_po_back_to_ungrouped(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->insertPoWithAcceptedInvoice(qty: 2, price: 5000);
        $result = $this->generateTt($fx['poi_id']);
        $ttId = (int) $result['tt_id'];

        $this->post('/declineTt', ['tt_id' => $ttId])->assertStatus(200);

        $tt = purchase_order_tt::findOrFail($ttId);
        $this->assertSame(0, (int) $tt->status);

        $po = PurchaseOrder::findOrFail($fx['po_id']);
        $this->assertNull($po->tt_id, 'declining releases the PO from the batch entirely');
        $this->assertSame(1, (int) $po->pembayaran, 'declining reverts pembayaran back to its unpaid/ungrouped default');
    }

    public function test_a_po_already_grouped_cannot_be_grouped_into_a_second_tt(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->insertPoWithAcceptedInvoice(qty: 4, price: 1500);
        $firstResult = $this->generateTt($fx['poi_id']);
        $this->assertSame(1, $firstResult['status']);

        $secondResult = $this->generateTt($fx['poi_id']);
        $this->assertSame(-1, $secondResult['status'], 'a PO already grouped (pembayaran=3, tt_id set) must be rejected from a second Tt');

        $po = PurchaseOrder::findOrFail($fx['po_id']);
        $this->assertSame((int) $firstResult['tt_id'], (int) $po->tt_id, 'the rejected second attempt must not change which Tt the PO belongs to');
    }

    public function test_accepting_an_already_accepted_tt_is_blocked_and_does_not_double_apply(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->insertPoWithAcceptedInvoice(qty: 6, price: 1000);
        $result = $this->generateTt($fx['poi_id']);
        $ttId = (int) $result['tt_id'];

        $this->acceptTt($ttId);

        // The repeat call hits the status!=1 guard before ever touching tt_image, so it's safe
        // to omit the image here.
        $response = $this->post('/accTt', ['tt_id' => $ttId]);
        $response->assertJson(['status' => -2]);

        $po = PurchaseOrder::findOrFail($fx['po_id']);
        $this->assertSame(2, (int) $po->pembayaran, 'a blocked repeat accept must leave pembayaran exactly as the first accept left it');
    }

    public function test_grouping_invoices_from_suppliers_with_different_banks_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        [$supplierA, $supplierB] = Supplier::where('status', 1)->limit(2)->get();
        $this->forceSupplierBank($supplierA, 1); // BSJ
        $this->forceSupplierBank($supplierB, 2); // F — deliberately different

        $fxA = $this->insertPoWithAcceptedInvoice(qty: 3, price: 1000, supplier: $supplierA);
        $fxB = $this->insertPoWithAcceptedInvoice(qty: 2, price: 1000, supplier: $supplierB);

        $result = $this->generateTtForMany([$fxA['poi_id'], $fxB['poi_id']]);

        $this->assertSame(-1, $result['status']);
        $this->assertStringContainsString('memiliki bank yang berbeda', $result['message']);

        $poA = PurchaseOrder::findOrFail($fxA['po_id']);
        $poB = PurchaseOrder::findOrFail($fxB['po_id']);
        $this->assertNull($poA->tt_id, 'a rejected cross-bank grouping must not group anything, including the first (valid-looking) item');
        $this->assertNull($poB->tt_id);
        $this->assertSame(1, (int) $poA->pembayaran);
        $this->assertSame(1, (int) $poB->pembayaran);
    }

    /**
     * Found while testing the cross-bank guard: the guard only ever compares `bank_id` — it never
     * checks that all grouped invoices belong to the SAME supplier, despite
     * cdocs/docs/flows/purchase-order-tanda-terima/FLOW.md describing a Tt as grouping POs "by the
     * same supplier and same bank." Two different suppliers who happen to share a bank_id can be
     * grouped into one Tt today, and the resulting `purchase_order_tts.supplier_id` ends up set to
     * whichever supplier's invoice happened to be LAST in the request array — `$param["supplier"]`
     * is overwritten every loop iteration and only its final value is used after the loop
     * (`SupplierController.php:396,426,434`) — not necessarily representative of every PO actually
     * grouped into the batch. Not fixed — see KNOWN_ISSUES.md.
     */
    public function test_grouping_invoices_from_different_suppliers_sharing_one_bank_is_silently_allowed(): void
    {
        $this->actingAsSuperAdminStaff();

        [$supplierA, $supplierB] = Supplier::where('status', 1)->limit(2)->get();
        $sharedBankId = 3; // CH
        $this->forceSupplierBank($supplierA, $sharedBankId);
        $this->forceSupplierBank($supplierB, $sharedBankId);

        $fxA = $this->insertPoWithAcceptedInvoice(qty: 3, price: 1000, supplier: $supplierA);
        $fxB = $this->insertPoWithAcceptedInvoice(qty: 2, price: 1000, supplier: $supplierB);

        // supplierB's invoice is listed LAST — per the bug, the resulting Tt's supplier_id will
        // reflect supplierB only, even though supplierA's PO is grouped into it too.
        $result = $this->generateTtForMany([$fxA['poi_id'], $fxB['poi_id']]);

        $this->assertSame(1, $result['status'], 'BUG: grouping across two different suppliers succeeds as long as their bank_id matches');

        $poA = PurchaseOrder::findOrFail($fxA['po_id']);
        $poB = PurchaseOrder::findOrFail($fxB['po_id']);
        $this->assertSame((int) $result['tt_id'], (int) $poA->tt_id, "supplier A's PO is grouped into the batch");
        $this->assertSame((int) $result['tt_id'], (int) $poB->tt_id, "supplier B's PO is ALSO grouped into the same batch");

        $tt = purchase_order_tt::findOrFail($result['tt_id']);
        $this->assertSame(
            $supplierB->supplier_id,
            (int) $tt->supplier_id,
            'BUG: the Tt\'s supplier_id reflects only the LAST invoice processed (supplier B), despite supplier A\'s PO being grouped in too'
        );
    }

    /**
     * `insertTt`/`updateTt` (`SupplierController.php:512-520`) are thin, fully-functional
     * pass-throughs to the model — but confirmed 2026-08-02: no frontend JS anywhere calls
     * `/insertTt` or `/updateTt` (grepped `public/Custom_js/`). Every real Tt in this app is
     * created via `generateTandaTerimaInvoice()` instead. Not a bug (the code works correctly,
     * unlike the genuinely-broken `pelunasanPurchaseOrder`/`updateProduction` dead-code findings)
     * — just a orphaned-from-the-UI manual CRUD path, plausibly meant for a direct-entry use case
     * that was never wired up. Covered here for completeness since it's still a real, reachable
     * endpoint.
     */
    public function test_insert_tt_creates_a_row_directly_without_going_through_generate(): void
    {
        $this->actingAsSuperAdminStaff();

        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $response = $this->post('/insertTt', [
            'tt_date' => now()->toDateString(),
            'tt_kode' => 'MANUAL-TT-0001',
            'tt_total' => 50000,
            'supplier_id' => $supplier->supplier_id,
            'staff_name' => 'Workflow Test Staff',
        ]);
        $response->assertStatus(200);
        $ttId = (int) $response->getContent();

        $tt = purchase_order_tt::findOrFail($ttId);
        $this->assertSame('MANUAL-TT-0001', $tt->tt_kode);
        $this->assertEquals(50000, $tt->tt_total);
        $this->assertSame($supplier->supplier_id, (int) $tt->supplier_id);
        $this->assertSame(1, (int) $tt->status, 'a manually-inserted Tt defaults to pending, same as a generated one');
    }

    public function test_update_tt_edits_an_existing_row_and_returns_null_for_an_invalid_id(): void
    {
        $this->actingAsSuperAdminStaff();

        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $ttId = (int) $this->post('/insertTt', [
            'tt_date' => now()->toDateString(),
            'tt_kode' => 'MANUAL-TT-0002',
            'tt_total' => 10000,
            'supplier_id' => $supplier->supplier_id,
            'staff_name' => 'Workflow Test Staff',
        ])->getContent();

        $this->post('/updateTt', [
            'tt_id' => $ttId,
            'tt_date' => now()->toDateString(),
            'tt_kode' => 'MANUAL-TT-0002-EDITED',
            'tt_total' => 20000,
            'staff_name' => 'Workflow Test Staff (edited)',
        ])->assertStatus(200);

        $tt = purchase_order_tt::findOrFail($ttId);
        $this->assertSame('MANUAL-TT-0002-EDITED', $tt->tt_kode);
        $this->assertEquals(20000, $tt->tt_total);

        // updateTt has its own null-guard, unlike deleteInvoicePO — an invalid id returns null
        // cleanly instead of crashing.
        $response = $this->post('/updateTt', [
            'tt_id' => 999999,
            'tt_date' => now()->toDateString(),
            'tt_kode' => 'DOES-NOT-EXIST',
            'tt_total' => 1,
            'staff_name' => 'Nobody',
        ]);
        $response->assertStatus(200);
        $this->assertSame('', $response->getContent(), 'updateTt returns null for a missing row, rendered as an empty body — not a crash');
    }
}
