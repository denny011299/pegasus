<?php

namespace Tests\Regression;

use App\Models\Cash;
use App\Models\CashSales;
use App\Models\Staff;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Mirrors tests/Regression/CashArmadaUpdateSilentlyResetsApprovedEntryToPendingTest.php — the same
 * bug shape, confirmed independently on `CashSales`.
 *
 * `CashSales::updateCashSales()` has EXPLICIT status-revival logic, clearly written to handle one
 * specific case (a DECLINED, `status=3` entry being re-submitted should go back to pending):
 *
 *   $incomingStatus = isset($data['status']) ? (int) $data['status'] : null;
 *   if ($incomingStatus !== null && $incomingStatus !== 3) {
 *       $t->status = $incomingStatus;
 *   } elseif ((int) ($t->status ?? 0) === 3) {
 *       $t->status = 1; // revived from declined
 *       ...
 *   } else {
 *       $t->status = $incomingStatus ?? 1;   // <-- catches status=2 (APPROVED) too, not just "else"
 *   }
 *
 * `ReportController::updateCashSales()` never sends a `status` field at all, so `$incomingStatus`
 * is always `null`. For an ALREADY-APPROVED (`status=2`) entry, the first branch is skipped
 * (incomingStatus is null) and the second branch's `=== 3` check also fails (current status is 2,
 * not 3) — falling through to the final `else`, which resets status to `1` anyway. The
 * status-revival logic that was clearly written to handle ONE case (reviving a decline) ends up
 * silently catching approved entries too. Confirmed 2026-08-02 alongside the CashArmada mirror —
 * deliberately deferred, not fixed.
 *
 * **Worse than the CashArmada mirror**: `updateCashSales()`'s controller ALSO unconditionally
 * forces `$data['cs_aksi'] = 1` for the entire "saldo" branch
 * (`ReportController.php:1683`), regardless of the entry's original `cs_aksi` (2 or 3). Combined
 * with the status reset, re-accepting an edited entry doesn't just double-apply the SAME balance
 * change — `cs_aksi` silently becoming `1` flips `acceptCashSales()` into its
 * `cs_type==1 && cs_aksi==1` branch (`+=` instead of the original `-=`), so the SECOND accept
 * moves the balance in the OPPOSITE direction from the first. This test characterizes the CURRENT
 * (direction-flipping) behavior, which is more severe than a simple double-apply.
 */
class CashSalesUpdateSilentlyResetsApprovedEntryToPendingTest extends TestCase
{
    use ActingAsStaff;

    public function test_updating_an_approved_saldo_entry_resets_it_to_pending_and_a_re_accept_double_applies_the_balance(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = Staff::where('status', 1)->firstOrFail();
        $staff->staff_saldo = 1000000;
        $staff->save();
        $nominal = 300000;

        $this->post('/insertCashSales', [
            'staff_id' => $staff->staff_id,
            'bank_id' => 0,
            'oc_transaksi' => 'saldo',
            'photo' => '',
            'cs_aksi' => '2',
            'cs_notes' => 'Regression test saldo entry',
            'cs_nominal' => $nominal,
        ])->assertStatus(200);

        $cs = CashSales::orderByDesc('cs_id')->firstOrFail();
        $cash = Cash::findOrFail($cs->cash_id);

        // Accept via cash_id (not cs_id) — acceptCashSales's dual-update branch, the only one that
        // also flips the linked Cash row.
        $this->post('/acceptCashSales', ['cash_id' => $cs->cash_id])->assertStatus(200);

        $cs->refresh();
        $cash->refresh();
        $this->assertSame(2, (int) $cs->status);
        $this->assertSame(2, (int) $cash->status);
        $staff->refresh();
        $this->assertSame(1000000 - $nominal, (int) $staff->staff_saldo, 'first accept applies the balance mutation once');

        // Editing the now-APPROVED entry — updateCashSales's revival logic was written for
        // status=3 (declined), but its fallback else-branch also catches status=2 (approved).
        $this->post('/updateCashSales', [
            'cs_id' => $cs->cs_id,
            'staff_id' => $staff->staff_id,
            'bank_id' => 0,
            'oc_transaksi' => 'saldo',
            'cash_id' => $cs->cash_id,
            'cs_type' => 1,
            'cs_notes' => 'Regression test saldo entry (edited)',
            'cs_nominal' => $nominal,
        ])->assertStatus(200);

        $cs->refresh();
        $this->assertSame(1, (int) $cs->status, 'BUG: updating an already-approved entry silently resets its status to pending — the revival logic meant for declined entries also catches approved ones');
        $this->assertSame(1, (int) $cs->cs_aksi, 'BUG: updateCashSales unconditionally forces cs_aksi=1 for the saldo branch, silently overwriting the original cs_aksi=2');

        // staff_saldo itself is untouched by the update — nothing reverses the original accept.
        $staff->refresh();
        $this->assertSame(1000000 - $nominal, (int) $staff->staff_saldo, 'the update itself does not touch staff_saldo at all');

        // The entry now LOOKS pending, so a second accept is accepted, not blocked.
        $reAcceptResponse = $this->post('/acceptCashSales', ['cash_id' => $cs->cash_id]);
        $reAcceptResponse->assertStatus(200);

        $cs->refresh();
        $this->assertSame(2, (int) $cs->status);

        $staff->refresh();
        $this->assertSame(
            1000000,
            (int) $staff->staff_saldo,
            'BUG: cs_aksi silently became 1, so acceptCashSales hits its cs_type==1 && cs_aksi==1 branch (+=) instead of the original -= branch — the second accept moves the balance the OPPOSITE direction, fully cancelling out the first accept rather than double-applying it'
        );
    }
}
