<?php

namespace Tests\Workflow;

use App\Models\Cash;
use App\Models\CashAdmin;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/KAS_OPERASIONAL_FLOW.md for the fully-traced flow this asserts
 * against. Unlike every other flow traced so far, approval here is a PURE status flip — the
 * nominal amount and any linked Cash ledger row are fully computed at insert time, not approval
 * time. CashGudang/CashArmada/CashSales mirror CashAdmin exactly, not separately tested.
 *
 * Only the "saldo" sub-flow is covered here. The "operasional" (expense) sub-flow currently
 * crashes with a 500 (insertCashAdmin never sets ca_type for that branch) — confirmed, deliberately
 * deferred by user decision, characterized in
 * tests/Regression/CashAdminOperasionalMissingCaTypeTest.php instead of here. See
 * cdocs/testing/KNOWN_ISSUES.md.
 *
 * The negative-`ca_nominal` sign-flip branches (added 2026-08-02) were the one piece of this
 * flow's own math never exercised: unlike `CashArmada`/`CashSales`, `cash_admins.ca_nominal`
 * itself is stored RAW (whatever sign the request sends, untouched) — only the LINKED `Cash` row's
 * `cash_nominal` gets normalized to a positive value, with `cash_type` flipped based on the sign.
 * `acceptCashAdmin` never reads `ca_nominal`'s sign at all (pure status flip, no balance
 * calculation here unlike CashArmada/CashSales), so there's no double-negation trick to verify —
 * just the insert-time Cash-row normalization.
 */
class KasOperasionalFlowTest extends TestCase
{
    use ActingAsStaff;

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_insert_saldo_then_approve_flips_status_on_both_cash_admin_and_cash(): void
    {
        $this->actingAsSuperAdminStaff();

        $nominal = 500000;

        $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1, // pengajuan dana (fund request)
            'ca_nominal' => $nominal,
            'ca_notes' => 'Workflow test fund request',
        ])->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $this->assertSame(1, (int) $cashAdmin->status, 'a freshly inserted saldo entry should be pending approval');
        $this->assertSame(1, (int) $cashAdmin->ca_type);
        $this->assertSame($nominal, (int) $cashAdmin->ca_nominal);
        $this->assertGreaterThan(0, (int) $cashAdmin->cash_id, 'a saldo entry must link to a real cashes row');

        $cash = Cash::find($cashAdmin->cash_id);
        $this->assertNotNull($cash, 'insertCashAdmin must create the linked Cash row for a saldo entry');
        $this->assertSame(1, (int) $cash->status);
        $this->assertSame($nominal, (int) $cash->cash_nominal);
        $this->assertSame(3, (int) $cash->cash_type, 'a positive pengajuan dana amount maps to cash_type 3');

        // Approve by cash_id (not ca_id) — the dual-update branch.
        $this->post('/acceptCashAdmin', ['cash_id' => $cashAdmin->cash_id])->assertStatus(200);

        $cashAdmin->refresh();
        $cash->refresh();
        $this->assertSame(2, (int) $cashAdmin->status, 'approving a saldo entry sets CashAdmin.status to 2');
        $this->assertSame(2, (int) $cash->status, 'approving a saldo entry must ALSO set the linked Cash row to 2');
        $this->assertNotNull($cashAdmin->acc_by);
        $this->assertNotNull($cash->acc_by);
    }

    public function test_decline_a_pending_saldo_entry_flips_both_to_declined(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 2, // pengembalian dana (fund return)
            'ca_nominal' => 250000,
            'ca_notes' => 'Workflow test fund return',
        ])->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $cash = Cash::find($cashAdmin->cash_id);

        $this->post('/declineCashAdmin', ['cash_id' => $cashAdmin->cash_id])->assertStatus(200);

        $cashAdmin->refresh();
        $cash->refresh();
        $this->assertSame(3, (int) $cashAdmin->status);
        $this->assertSame(3, (int) $cash->status, 'declining a saldo entry must ALSO decline the linked Cash row');
    }

    public function test_negative_nominal_pengajuan_dana_flips_the_linked_cash_row_type_but_keeps_ca_nominal_raw(): void
    {
        $this->actingAsSuperAdminStaff();

        $nominal = -500000;

        $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1, // pengajuan dana
            'ca_nominal' => $nominal,
            'ca_notes' => 'Workflow test negative pengajuan dana',
        ])->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $this->assertSame($nominal, (int) $cashAdmin->ca_nominal, 'ca_nominal is stored RAW, unlike cash_admins\' linked Cash row');

        $cash = Cash::find($cashAdmin->cash_id);
        $this->assertNotNull($cash);
        $this->assertSame(1, (int) $cash->cash_type, 'a negative ca_nominal flips oc_transaksi==1 from cash_type 3 to 1');
        $this->assertSame(-$nominal, (int) $cash->cash_nominal, 'the linked Cash row normalizes cash_nominal to a positive value');
    }

    public function test_negative_nominal_pengembalian_dana_flips_the_linked_cash_row_type_but_keeps_ca_nominal_raw(): void
    {
        $this->actingAsSuperAdminStaff();

        $nominal = -250000;

        $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 2, // pengembalian dana
            'ca_nominal' => $nominal,
            'ca_notes' => 'Workflow test negative pengembalian dana',
        ])->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $this->assertSame($nominal, (int) $cashAdmin->ca_nominal, 'ca_nominal is stored RAW, unlike cash_admins\' linked Cash row');

        $cash = Cash::find($cashAdmin->cash_id);
        $this->assertNotNull($cash);
        $this->assertSame(2, (int) $cash->cash_type, 'a negative ca_nominal flips oc_transaksi==2 from cash_type 1 to 2');
        $this->assertSame(-$nominal, (int) $cash->cash_nominal, 'the linked Cash row normalizes cash_nominal to a positive value');
    }
}
