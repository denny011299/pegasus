<?php

namespace Tests\Regression;

use App\Models\CashAdmin;
use App\Models\CashGudang;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #130: acceptCashAdmin()/declineCashAdmin() (and declineCashGudang()) crashed 500 instead
 * of failing cleanly whenever the pengajuan/pengembalian had been deleted (e.g. by its own staff,
 * via /deleteCashAdmin) before an approver ACC'd/menolak it. `deleteCashAdmin()`/`deleteCashGudang()`
 * only soft-flag `status = 0` — they never set `acc_by` — so the old code's
 * `Staff::find($q->acc_by)->staff_name` ran `Staff::find(null)` and crashed on
 * "Attempt to read property 'staff_name' on null" instead of returning the usual
 * {status:-2, header, message} shape the frontend already knows how to render as a notifikasi().
 * acceptCashGudang() already guarded this correctly (see its lockForUpdate transaction) — this
 * mirrors that null-safety into the three siblings that didn't have it.
 */
class CashAdminAccDeclineDeletedRecordCrashesTest extends TestCase
{
    use ActingAsStaff;

    private function staffId(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_accepting_a_deleted_cash_admin_entry_fails_cleanly_instead_of_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1,
            'ca_nominal' => 100000,
            'ca_notes' => 'Regression test - will be deleted before ACC',
        ])->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $caId = $cashAdmin->ca_id;

        $this->post('/deleteCashAdmin', ['ca_id' => $caId])->assertStatus(200);
        $this->assertSame(0, (int) CashAdmin::find($caId)->status, 'precondition: deleteCashAdmin only soft-flags status=0, acc_by stays null');

        $response = $this->post('/acceptCashAdmin', ['ca_id' => $caId]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', -2);
    }

    public function test_declining_a_deleted_cash_admin_entry_fails_cleanly_instead_of_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 2,
            'ca_nominal' => 50000,
            'ca_notes' => 'Regression test - will be deleted before decline',
        ])->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $caId = $cashAdmin->ca_id;

        $this->post('/deleteCashAdmin', ['ca_id' => $caId])->assertStatus(200);
        $this->assertSame(0, (int) CashAdmin::find($caId)->status, 'precondition: deleteCashAdmin only soft-flags status=0, acc_by stays null');

        $response = $this->post('/declineCashAdmin', ['ca_id' => $caId]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', -2);
    }

    public function test_declining_a_deleted_cash_gudang_entry_fails_cleanly_instead_of_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertCashGudang', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1,
            'cg_nominal' => 75000,
            'cg_notes' => 'Regression test - will be deleted before decline',
        ])->assertStatus(200);

        $cashGudang = CashGudang::orderByDesc('cg_id')->firstOrFail();
        $cgId = $cashGudang->cg_id;

        $this->post('/deleteCashGudang', ['cg_id' => $cgId])->assertStatus(200);
        $this->assertSame(0, (int) CashGudang::find($cgId)->status, 'precondition: deleteCashGudang only soft-flags status=0, acc_by stays null');

        $response = $this->post('/declineCashGudang', ['cg_id' => $cgId]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', -2);
    }
}
