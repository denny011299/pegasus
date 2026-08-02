<?php

namespace Tests\Regression;

use App\Models\Cash;
use App\Models\CashArmada;
use App\Models\Customer;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `ReportController::updateCashArmada()` never passes a `status` field to either
 * `CashArmada::updateCashArmada()` or (for the "saldo" branch) `Cash::updateCash()` — both models
 * unconditionally reset `status` back to `1` (pending) on every update:
 *
 *   // CashArmada::updateCashArmada()
 *   $t->status = $data['status'] ?? 1;   // controller never sends 'status', so ALWAYS 1
 *
 * There is no guard anywhere in `updateCashArmada()` checking the record's CURRENT status before
 * allowing the update — unlike `acceptCashArmada`/`declineCashArmada`, which both refuse outright
 * if `status != 1`. This means calling `/updateCashArmada` on an ALREADY-APPROVED (`status = 2`)
 * entry silently reverts it (and its linked `Cash` row, for the "saldo" sub-flow) back to pending
 * — with `customer_saldo` already having been mutated once by the original approval. Re-accepting
 * the now-"pending" entry applies the SAME balance mutation a SECOND time, since nothing reverses
 * the first one. Confirmed 2026-08-02 while building `tests/Workflow/CashArmadaFlowTest.php`'s
 * saldo coverage — deliberately deferred, not fixed, per this program's bug-handling policy. This
 * test characterizes the CURRENT (double-apply) behavior on purpose.
 */
class CashArmadaUpdateSilentlyResetsApprovedEntryToPendingTest extends TestCase
{
    use ActingAsStaff;

    public function test_updating_an_approved_saldo_entry_resets_it_to_pending_and_a_re_accept_double_applies_the_balance(): void
    {
        $this->actingAsSuperAdminStaff();

        $customer = Customer::where('status', 1)->firstOrFail();
        $customer->customer_saldo = 1000000;
        $customer->save();
        $nominal = 300000;

        $this->post('/insertCashArmada', [
            'customer_id' => $customer->customer_id,
            'oc_transaksi' => 'saldo',
            'photo' => '',
            'cr_notes' => 'Regression test saldo entry',
            'cr_nominal' => $nominal,
        ])->assertStatus(200);

        $cr = CashArmada::orderByDesc('cr_id')->firstOrFail();
        $cash = Cash::findOrFail($cr->cash_id);

        // Accept via cash_id (not cr_id) — acceptCashArmada's dual-update branch, the only one
        // that also flips the linked Cash row (same precedent as CashAdmin's KasOperasionalFlowTest).
        $this->post('/acceptCashArmada', ['cash_id' => $cr->cash_id])->assertStatus(200);

        $cr->refresh();
        $cash->refresh();
        $this->assertSame(2, (int) $cr->status);
        $this->assertSame(2, (int) $cash->status);
        $customer->refresh();
        $this->assertSame(1000000 - $nominal, (int) $customer->customer_saldo, 'first accept applies the balance mutation once');

        // Editing the now-APPROVED entry — no status guard exists on updateCashArmada at all.
        $this->post('/updateCashArmada', [
            'cr_id' => $cr->cr_id,
            'customer_id' => $customer->customer_id,
            'oc_transaksi' => 'saldo',
            'cash_id' => $cr->cash_id,
            'cr_notes' => 'Regression test saldo entry (edited)',
            'cr_nominal' => $nominal,
        ])->assertStatus(200);

        $cr->refresh();
        $cash->refresh();
        $this->assertSame(1, (int) $cr->status, 'BUG: updating an already-approved entry silently resets its status to pending — no guard exists');
        $this->assertSame(1, (int) $cash->status, 'BUG: the linked Cash row is ALSO silently reset to pending');

        // customer_saldo itself is untouched by the update — nothing reverses the original accept.
        $customer->refresh();
        $this->assertSame(1000000 - $nominal, (int) $customer->customer_saldo, 'the update itself does not touch customer_saldo at all');

        // The entry now LOOKS pending, so a second accept is accepted, not blocked.
        $reAcceptResponse = $this->post('/acceptCashArmada', ['cash_id' => $cr->cash_id]);
        $reAcceptResponse->assertStatus(200);

        $cr->refresh();
        $this->assertSame(2, (int) $cr->status);

        $customer->refresh();
        $this->assertSame(
            1000000 - (2 * $nominal),
            (int) $customer->customer_saldo,
            'BUG: the SAME balance mutation is applied a SECOND time — customer_saldo is now off by one full nominal'
        );
    }
}
