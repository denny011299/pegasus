<?php

namespace Tests\Regression;

use App\Models\ProductIssues;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\ReturnSupplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Resolves the open question flagged in `cdocs/testing/workflows/RETURN_SUPPLIES_FLOW.md`/
 * `PRODUCT_ISSUES_FLOW.md`: which flow is `ProductIssues::deleteProductIssues()`'s
 * `ref_num != 0` guard actually for?
 *
 *   function deleteProductIssues($data)
 *   {
 *       $t = self::find($data["pi_id"]);
 *       if (isset($t->ref_num) && $t->ref_num != 0){
 *           $inv = PurchaseOrderDetailInvoice::find($t->ref_num);
 *           $po = PurchaseOrder::find($inv->po_id);
 *           if ($po->tt_id != null) { return -1; }
 *       }
 *       ...
 *   }
 *
 * Answer: it's for a "Retur ke Supplier" Produk Bermasalah entry (`tipe_return=1`) that's linked
 * to a SPECIFIC invoiced PO line (`ref_num` = a `purchase_order_detail_invoices.poi_id`) — blocking
 * its deletion once that PO has progressed to having a `tt_id` (a Tanda Terima payment batch),
 * i.e. "you can't undo this return, the related invoice has already been paid" (confirmed by the
 * guard's own caller, `StockController::deleteProductIssue()`, returning exactly that message:
 * "Invoice tersebut sudah terbayar").
 *
 * But this is the SAME abandoned invoice-linking feature already noted as dead code on the INSERT
 * side (`StockController::insertProductIssue()`'s commented-out PO-invoice cross-check,
 * `:661-679`) — confirmed here to be dead on BOTH ends:
 *   - Backend: `insertProductIssue`'s validation block that would have computed `ref_num` is
 *     entirely commented out.
 *   - Frontend: `public/Custom_js/Backoffice/Inventory/Product_Issues.js:445` has
 *     `// ref_num: $("#ref_num").val(),` commented out of the actual insert payload.
 *   - The OTHER path that creates `tipe_return=1` `product_issues` rows,
 *     `SupplierController::insertReturnSupplies()`, always hardcodes `'ref_num' => 0` explicitly
 *     (`SupplierController.php:754`).
 *
 * So `product_issues.ref_num` can never be nonzero via ANY live insert path today — this guard is
 * real, coherent, correctly-written code for a real business rule, but permanently unreachable.
 *
 * **Found something even more dead than expected while proving this**: `deleteProductIssues()`
 * is called from TWO different controller actions, and only ONE of them actually respects its
 * `-1` return value:
 *   - `StockController::deleteProductIssue()` (route `/deleteProductIssues`, the method the
 *     developer's own docblock in `PRODUCT_ISSUES_FLOW.md` already noted is UI-unreachable —
 *     `Product_Issues.js`'s delete trigger icon is commented out of the table template) DOES
 *     check `if ($del == -1) { return ..."Invoice tersebut sudah terbayar"... }`.
 *   - `SupplierController::deleteReturnSupplies()` (route `/deleteReturnSupplies`, the REAL,
 *     reachable delete path for Return Supplies, already covered by
 *     `tests/Workflow/ReturnSuppliesFlowTest.php`) calls the exact same model method
 *     (`(new ProductIssues())->deleteProductIssues($rs)`) but NEVER checks its return value at
 *     all — proceeds to delete regardless.
 *
 * So even in the hypothetical world where `ref_num` somehow became nonzero, the guard could still
 * only ever matter through the UI-unreachable endpoint — and even then, going through the REAL,
 * reachable `/deleteReturnSupplies` produces something WORSE than "ignored": the guard's early
 * `return -1` inside `deleteProductIssues()` skips that same method's own `$t->status = 3` line
 * (it comes after the early return), but the caller never notices the `-1` and reverses
 * `po_total`/stock anyway — a split-brained result where the money/stock are reversed as if the
 * return were undone, while the `ProductIssues` row is left stuck at `status = 2` (still
 * "approved"), never soft-deleted to `3` like a normal delete would leave it.
 *
 * This test proves all three facts: (1) the real insert path always produces `ref_num = 0`;
 * (2) the ACTUALLY-reachable `/deleteReturnSupplies` produces the split-brained result above even
 * when `ref_num` is forced nonzero; (3) the guard mechanism itself is correctly written and DOES
 * cleanly block deletion via the unreachable `/deleteProductIssues` endpoint. Nothing here is
 * "fixed" (nothing actionable without reviving an abandoned feature) — recorded so this doesn't
 * get re-investigated as an open question again. See `KNOWN_ISSUES.md`.
 */
class ProductIssuesDeleteRefNumGuardIsDeadCodeTest extends TestCase
{
    use ActingAsStaff;

    private function insertAndApprovePo(int $qty, int $price): array
    {
        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * $price,
            'jenis_discount' => 1,
            'po_desc' => 'ref_num dead-code regression PO',
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

        return [$poId, $variant, $stock];
    }

    public function test_the_real_insert_path_always_produces_ref_num_zero(): void
    {
        $this->actingAsSuperAdminStaff();

        [$poId, $variant, $stock] = $this->insertAndApprovePo(qty: 10, price: 1000);

        $this->post('/insertReturnSupplies', [
            'po_id' => $poId,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'ref_num dead-code regression return',
            'rs_total' => 3000,
            'returs' => json_encode([[
                'supplies_id' => $variant->supplies_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'unit_id' => $stock->unit_id,
                'rsd_qty' => 3,
                'rsd_price' => 1000,
            ]]),
        ])->assertStatus(200);

        $pi = ProductIssues::orderByDesc('pi_id')->firstOrFail();
        $this->assertSame(0, (int) $pi->ref_num, 'insertReturnSupplies always hardcodes ref_num to 0');
    }

    /** @return array{0: int, 1: SuppliesVariant, 2: SuppliesStock, 3: int} */
    private function createReturnWithForcedRefNum(): array
    {
        [$poId, $variant, $stock] = $this->insertAndApprovePo(qty: 10, price: 1000);

        $this->post('/insertReturnSupplies', [
            'po_id' => $poId,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'ref_num dead-code regression return',
            'rs_total' => 3000,
            'returs' => json_encode([[
                'supplies_id' => $variant->supplies_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'unit_id' => $stock->unit_id,
                'rsd_qty' => 3,
                'rsd_price' => 1000,
            ]]),
        ])->assertStatus(200);

        $po = PurchaseOrder::findOrFail($poId);
        $pi = ProductIssues::orderByDesc('pi_id')->firstOrFail();

        // Nothing in the live app can ever do this — seeded directly to exercise the guard.
        $invoice = new PurchaseOrderDetailInvoice();
        $invoice->po_id = $poId;
        $invoice->poi_date = now()->toDateString();
        $invoice->poi_due = now()->toDateString();
        $invoice->poi_code = 'REGRESSION-INV-'.uniqid();
        $invoice->poi_total = 3000;
        $invoice->bank_id = 0;
        $invoice->status = 1;
        $invoice->save();

        $pi->ref_num = $invoice->poi_id;
        $pi->save();

        $po->tt_id = 999999; // "already paid via a Tanda Terima batch"
        $po->save();

        return [$poId, $variant, $stock, $pi->pi_id];
    }

    public function test_the_actually_reachable_delete_return_supplies_endpoint_produces_a_split_brained_result(): void
    {
        $this->actingAsSuperAdminStaff();

        [$poId, , $stock, $piId] = $this->createReturnWithForcedRefNum();
        $po = PurchaseOrder::findOrFail($poId);
        $poTotalBeforeDelete = (int) $po->po_total;
        $stock->refresh();
        $stockBeforeDelete = $stock->ss_stock;
        $rs = ReturnSupplies::where('po_id', $poId)->firstOrFail();

        // Even with ref_num forced nonzero and the PO's tt_id set, this REAL, reachable delete
        // path proceeds anyway — it never checks deleteProductIssues()'s return value.
        $response = $this->post('/deleteReturnSupplies', ['rs_id' => $rs->rs_id, 'po_id' => $poId]);
        $this->assertSame('1', $response->getContent(), 'deleteReturnSupplies completes normally — the guard\'s -1 return value is silently discarded');

        $po->refresh();
        $this->assertSame($poTotalBeforeDelete + 3000, (int) $po->po_total, 'po_total IS reversed — the caller proceeds regardless of the guard');

        $stock->refresh();
        $this->assertSame($stockBeforeDelete + 3, $stock->ss_stock, 'stock IS reversed too — the caller proceeds regardless of the guard');

        // Split-brained result: the guard's early `return -1` inside deleteProductIssues() DOES
        // still skip that same method's own `$t->status = 3` line (it's after the early return) —
        // but the CALLER never notices the -1 and reverses po_total/stock anyway. The net effect
        // is worse than either "fully blocked" or "fully deleted": money and stock are reversed as
        // if the return were undone, while the ProductIssues row itself is left stuck at status=2
        // (still "approved"), not soft-deleted to status=3 like a normal, unblocked delete would.
        $pi = ProductIssues::find($piId);
        $this->assertSame(2, (int) $pi->status, 'BUG: left stuck at status=2 — the guard\'s early return skips the status flip, but the caller\'s own reversal logic runs anyway, an inconsistent split-brained result');
    }

    public function test_the_guard_mechanism_itself_correctly_blocks_deletion_via_the_ui_unreachable_endpoint(): void
    {
        $this->actingAsSuperAdminStaff();

        [, , $stock, $piId] = $this->createReturnWithForcedRefNum();
        $stock->refresh();
        $stockBeforeDelete = $stock->ss_stock;

        // StockController::deleteProductIssue() — the one caller that DOES check the guard's
        // return value. Confirmed UI-unreachable already (Product_Issues.js's delete trigger icon
        // is commented out), but the code itself, if ever reached, works correctly.
        $response = $this->post('/deleteProductIssues', ['pi_id' => $piId]);
        $response->assertJson([
            'status' => 0,
            'header' => 'Gagal Delete',
            'message' => 'Invoice tersebut sudah terbayar',
        ]);

        $pi = ProductIssues::find($piId);
        $this->assertSame(2, (int) $pi->status, 'blocked — the ProductIssues row is left exactly as it was, not soft-deleted');

        $stock->refresh();
        $this->assertSame($stockBeforeDelete, $stock->ss_stock, 'blocked — no stock reversal attempted');
    }
}
