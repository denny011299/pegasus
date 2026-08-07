<?php

namespace Tests\Regression;

use App\Models\Cash;
use App\Models\CashArmada;
use App\Models\Customer;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-04): `ReportController::updateCashArmada()` never passed a `status` field to
 * either `CashArmada::updateCashArmada()` or (for the "saldo" branch) `Cash::updateCash()` — both
 * models unconditionally reset `status` back to `1` (pending) on every update:
 *
 *   // CashArmada::updateCashArmada()
 *   $t->status = $data['status'] ?? 1;   // controller never sends 'status', so ALWAYS 1
 *
 * There was no guard anywhere in `updateCashArmada()` checking the record's CURRENT status before
 * allowing the update — unlike `acceptCashArmada`/`declineCashArmada`, which both already refuse
 * outright if `status != 1`. This meant calling `/updateCashArmada` on an ALREADY-APPROVED
 * (`status = 2`) entry silently reverted it back to pending — with `customer_saldo` already having
 * been mutated once by the original approval. Re-accepting the now-"pending" entry applied the
 * SAME balance mutation a SECOND time, since nothing reversed the first one.
 *
 * Fix: `updateCashArmada()` now refuses outright with a clean `{status: -2, header: 'Gagal
 * Update', ...}` response if the entry's current status isn't `1`, matching
 * `acceptCashArmada`/`declineCashArmada`'s existing convention exactly.
 */
class CashArmadaUpdateSilentlyResetsApprovedEntryToPendingTest extends TestCase
{
    use ActingAsStaff;

    public function test_updating_an_approved_saldo_entry_is_now_cleanly_rejected_instead_of_resetting_to_pending(): void
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

        // Editing the now-APPROVED entry — must now be cleanly rejected, matching
        // acceptCashArmada/declineCashArmada's existing status guard.
        $updateResponse = $this->post('/updateCashArmada', [
            'cr_id' => $cr->cr_id,
            'customer_id' => $customer->customer_id,
            'oc_transaksi' => 'saldo',
            'cash_id' => $cr->cash_id,
            'cr_notes' => 'Regression test saldo entry (edited)',
            'cr_nominal' => $nominal,
        ]);
        $updateResponse->assertOk();
        $updateResponse->assertJson(['status' => -2, 'header' => 'Gagal Update']);

        $cr->refresh();
        $cash->refresh();
        $this->assertSame(2, (int) $cr->status, 'the entry must remain approved — the update must be rejected before any mutation');
        $this->assertSame(2, (int) $cash->status, 'the linked Cash row must remain approved too');
        $this->assertSame('Regression test saldo entry', $cr->cr_notes, 'the rejected update must not change any field either');

        $customer->refresh();
        $this->assertSame(1000000 - $nominal, (int) $customer->customer_saldo, 'still exactly one balance mutation from the original accept');

        // A second accept on the still-approved entry must be blocked outright, same as always.
        $reAcceptResponse = $this->post('/acceptCashArmada', ['cash_id' => $cr->cash_id]);
        $reAcceptResponse->assertJson(['status' => -2]);

        $customer->refresh();
        $this->assertSame(
            1000000 - $nominal,
            (int) $customer->customer_saldo,
            'the balance must never be double-applied now that both update and re-accept are blocked'
        );
    }
}
