<?php

namespace Tests\Regression;

use App\Models\PurchaseOrder;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Found while tracing cdocs/testing/workflows/PURCHASE_ORDER_FLOW.md (Phase 3 pilot).
 * `PurchaseOrder::pelunasanPurchaseOrder()`'s entire body is a commented-out
 * assignment followed by an unconditional no-op save:
 *
 *   function pelunasanPurchaseOrder($data)
 *   {
 *       $t = PurchaseOrder::find($data["po_id"]);
 *   //    / $t->pembayaran = 1; // soft delete
 *       $t->save();
 *   }
 *
 * The `/pelunasanPurchaseOrder` route (permission Pembelian|others) therefore
 * does nothing when called, with no visible error. Confirmed 2026-08-01, not
 * fixed — deciding what "Pelunasan" (settlement/payoff) should actually set
 * is a business decision, not something to guess at. Full writeup:
 * cdocs/testing/KNOWN_ISSUES.md. This test characterizes the CURRENT (inert)
 * behavior on purpose — flip its assertions once the feature is implemented
 * for real, per guides/REGRESSION_GUIDE.md.
 */
class PurchaseOrderPelunasanIsNoOpTest extends TestCase
{
    use ActingAsStaff;

    public function test_pelunasan_currently_does_not_change_the_purchase_order(): void
    {
        $this->actingAsSuperAdminStaff();

        $po = PurchaseOrder::query()->where('status', '>=', -1)->firstOrFail();
        $before = $po->only(['status', 'pembayaran', 'acc_by', 'po_total']);

        $this->post('/pelunasanPurchaseOrder', ['po_id' => $po->po_id])->assertStatus(200);

        $po->refresh();
        $this->assertSame($before, $po->only(['status', 'pembayaran', 'acc_by', 'po_total']));
    }
}
