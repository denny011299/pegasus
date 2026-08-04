<?php

namespace Tests\Regression;

use App\Models\Cash;
use App\Models\CashSales;
use App\Models\Staff;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-04): Mirrors
 * tests/Regression/CashArmadaUpdateSilentlyResetsApprovedEntryToPendingTest.php — the same bug
 * shape, confirmed independently on `CashSales`.
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
 *       $t->status = $incomingStatus ?? 1;   // <-- caught status=2 (APPROVED) too, not just "else"
 *   }
 *
 * `ReportController::updateCashSales()` never sent a `status` field at all, so `$incomingStatus`
 * was always `null`. For an ALREADY-APPROVED (`status=2`) entry, the first branch was skipped
 * (incomingStatus is null) and the second branch's `=== 3` check also failed (current status is 2,
 * not 3) — falling through to the final `else`, which reset status to `1` anyway. Worse than the
 * CashArmada mirror: the controller ALSO unconditionally forced `$data['cs_aksi'] = 1` for the
 * entire "saldo" branch regardless of the entry's original `cs_aksi`, so a re-accept after editing
 * an approved entry didn't just double-apply the balance change — it could flip `acceptCashSales()`
 * into the opposite branch and cancel the first accept out entirely.
 *
 * Fix: `updateCashSales()` now refuses outright with a clean `{status: -2, header: 'Gagal
 * Update', ...}` response if the entry's current status isn't `1`, matching
 * `acceptCashSales`/`declineCashSales`'s existing convention — the model's status-revival logic and
 * the cs_aksi assignment are both unreachable for an approved entry now, since the controller never
 * gets that far.
 */
class CashSalesUpdateSilentlyResetsApprovedEntryToPendingTest extends TestCase
{
    use ActingAsStaff;

    public function test_updating_an_approved_saldo_entry_is_now_cleanly_rejected_instead_of_resetting_to_pending(): void
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

        // Editing the now-APPROVED entry — must now be cleanly rejected before the model's
        // status-revival logic or the controller's cs_aksi override are ever reached.
        $updateResponse = $this->post('/updateCashSales', [
            'cs_id' => $cs->cs_id,
            'staff_id' => $staff->staff_id,
            'bank_id' => 0,
            'oc_transaksi' => 'saldo',
            'cash_id' => $cs->cash_id,
            'cs_type' => 1,
            'cs_notes' => 'Regression test saldo entry (edited)',
            'cs_nominal' => $nominal,
        ]);
        $updateResponse->assertOk();
        $updateResponse->assertJson(['status' => -2, 'header' => 'Gagal Update']);

        $cs->refresh();
        $this->assertSame(2, (int) $cs->status, 'the entry must remain approved — the update must be rejected before any mutation');
        $this->assertSame(2, (int) $cs->cs_aksi, 'cs_aksi must be untouched — the original value from insert must survive');

        $staff->refresh();
        $this->assertSame(1000000 - $nominal, (int) $staff->staff_saldo, 'still exactly one balance mutation from the original accept');

        // A second accept on the still-approved entry must be blocked outright, same as always.
        $reAcceptResponse = $this->post('/acceptCashSales', ['cash_id' => $cs->cash_id]);
        $reAcceptResponse->assertJson(['status' => -2]);

        $staff->refresh();
        $this->assertSame(
            1000000 - $nominal,
            (int) $staff->staff_saldo,
            'the balance must never double-apply or flip direction now that both update and re-accept are blocked'
        );
    }
}
