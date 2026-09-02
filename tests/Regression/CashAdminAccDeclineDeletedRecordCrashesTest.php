<?php

namespace Tests\Regression;

use App\Models\Cash;
use App\Models\CashAdmin;
use App\Models\CashGudang;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #130 (items 33/34): acceptCashAdmin()/declineCashAdmin()/acceptCashGudang()/
 * declineCashGudang()/acceptCashBesar()/declineCashBesar() (and the Armada/Sales siblings) had two
 * separate problems:
 *
 * 1. Several of them had NO null-guard on the lookup — if the pengajuan/pengembalian had been
 *    deleted (e.g. by its own staff, via /deleteCashAdmin) before an approver ACC'd/menolak it,
 *    `$q->status` crashed 500 ("Attempt to read property 'status' on null") instead of returning
 *    the usual {status:-2, header, message} shape the frontend already renders via notifikasi().
 * 2. Even the ones that DID guard against this returned the exact same message
 *    ("Pengajuan sudah diterima/ditolak oleh staff lain") for a genuinely deleted record as for one
 *    someone else had already accepted/declined — because deleteCashAdmin()/deleteCashGudang()/
 *    deleteCash() only soft-flag status=0 and never set acc_by, `Staff::find(null)` always resolved
 *    to "staff lain" regardless of the real reason. PM flagged this directly on the issue: the
 *    message must say "sudah dihapus" when that's what actually happened, not "diterima/ditolak
 *    oleh staff lain".
 *
 * Both are now handled by the shared `ReportController::pengajuanFailureMessage()` helper, which
 * branches on the record's actual status (0=dihapus, 2=diterima, 3=ditolak, not found=mungkin
 * sudah dihapus) instead of collapsing everything into one generic sentence.
 */
class CashAdminAccDeclineDeletedRecordCrashesTest extends TestCase
{
    use ActingAsStaff;

    private function staffId(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_accepting_a_deleted_cash_admin_entry_reports_it_was_deleted(): void
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
        $response->assertJsonPath('message', 'Pengajuan ini sudah dihapus oleh pengaju');
    }

    public function test_declining_a_deleted_cash_admin_entry_reports_it_was_deleted(): void
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
        $response->assertJsonPath('message', 'Pengajuan ini sudah dihapus oleh pengaju');
    }

    public function test_declining_a_deleted_cash_gudang_entry_reports_it_was_deleted(): void
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
        $response->assertJsonPath('message', 'Pengajuan ini sudah dihapus oleh pengaju');
    }

    public function test_accepting_a_deleted_cash_besar_entry_reports_it_was_deleted(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1,
            'ca_nominal' => 60000,
            'ca_notes' => 'Regression test - Kas Besar linked row will be deleted before ACC',
        ])->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $cashId = $cashAdmin->cash_id;
        $this->assertGreaterThan(0, $cashId, 'precondition: a saldo entry must link to a real cashes row');

        // Deleting the CashAdmin entry also soft-deletes its linked Cash row (ca_type == 1 branch
        // of deleteCashAdmin()) — this is the exact path issue #130 item 33 describes.
        $this->post('/deleteCashAdmin', ['ca_id' => $cashAdmin->ca_id])->assertStatus(200);
        $this->assertSame(0, (int) Cash::find($cashId)->status, 'precondition: deleteCash only soft-flags status=0, acc_by stays null');

        $response = $this->post('/acceptCashBesar', ['cash_id' => $cashId]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', -2);
        $response->assertJsonPath('message', 'Pengajuan ini sudah dihapus oleh pengaju');
    }

    public function test_declining_a_deleted_cash_besar_entry_reports_it_was_deleted(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertCashGudang', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1,
            'cg_nominal' => 45000,
            'cg_notes' => 'Regression test - Kas Besar linked row will be deleted before decline',
        ])->assertStatus(200);

        $cashGudang = CashGudang::orderByDesc('cg_id')->firstOrFail();
        $cashId = $cashGudang->cash_id;
        $this->assertGreaterThan(0, $cashId, 'precondition: a saldo entry must link to a real cashes row');

        $this->post('/deleteCashGudang', ['cg_id' => $cashGudang->cg_id])->assertStatus(200);
        $this->assertSame(0, (int) Cash::find($cashId)->status, 'precondition: deleteCash only soft-flags status=0, acc_by stays null');

        $response = $this->post('/declineCashBesar', ['cash_id' => $cashId]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', -2);
        $response->assertJsonPath('message', 'Pengajuan ini sudah dihapus oleh pengaju');
    }

    public function test_accepting_an_already_accepted_cash_besar_entry_names_the_real_reason_not_deleted(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1,
            'ca_nominal' => 70000,
            'ca_notes' => 'Regression test - already accepted, not deleted',
        ])->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $cashId = $cashAdmin->cash_id;

        $this->post('/acceptCashBesar', ['cash_id' => $cashId])->assertStatus(200);
        $this->assertSame(2, (int) Cash::find($cashId)->status, 'precondition: the row is accepted, not deleted');

        // Accepting it again must say it was already ACCEPTED, never "already deleted" — those are
        // two different, non-interchangeable reasons per PM's item 34.
        $response = $this->post('/acceptCashBesar', ['cash_id' => $cashId]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', -2);
        $message = $response->json('message');
        $this->assertStringContainsString('diterima', $message);
        $this->assertStringNotContainsString('dihapus', $message);
    }
}
