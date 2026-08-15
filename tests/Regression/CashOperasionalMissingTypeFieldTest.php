<?php

namespace Tests\Regression;

use App\Models\Cash;
use App\Models\CashAdmin;
use App\Models\CashGudang;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Fixed 2026-08-02: several Kas Operasional insert paths never set the `*_type` column their own
 * model unconditionally reads, crashing with a 500 ("Undefined array key ...") instead of ever
 * creating a row. Found while building the Kas Operasional Workflow pilot
 * (cdocs/testing/workflows/KAS_OPERASIONAL_FLOW.md).
 *
 * - `ReportController::insertCashAdmin()`'s "operasional" (expense) branch never set `ca_type`;
 *   `updateCashAdmin()`'s operasional branch had the identical gap.
 * - `ReportController::insertCashGudang()`/`updateCashGudang()` never set `cg_type` in EITHER
 *   branch (operasional or saldo).
 *
 * Fix: added `$data['ca_type'] = 2;` to both `insertCashAdmin`/`updateCashAdmin`'s operasional
 * branches (matching the "saldo" branch's existing `ca_type = 1`), and
 * `$data['cg_type'] = 2;`/`$data['cg_type'] = 1;` to `insertCashGudang`/`updateCashGudang`'s
 * operasional/saldo branches respectively — matching `CashAdmin::getCashAdmin()`/
 * `CashGudang::getCashGudang()`'s own balance math, which already branched on `type==1`
 * (saldo)/`type==2` (operational expense) on the READ side.
 *
 * These tests previously characterized the crash on purpose; now they verify the fix: the insert
 * succeeds, the row is created with the correct `*_type`, and (for CashGudang's saldo branch) the
 * linked `Cash` row is created too.
 */
class CashOperasionalMissingTypeFieldTest extends TestCase
{
    use ActingAsStaff;

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_cash_admin_operasional_insert_succeeds_with_ca_type_2(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->post('/insertCashAdmin', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'operasional',
            'items' => json_encode([[
                'cad_nominal' => 100000,
                'cad_notes' => 'Regression test expense line',
            ]]),
        ]);

        $response->assertStatus(200);

        $cashAdmin = CashAdmin::orderByDesc('ca_id')->firstOrFail();
        $this->assertSame(2, (int) $cashAdmin->ca_type, 'operasional entries must be tagged ca_type=2, matching getCashAdmin()\'s own balance math');
        $this->assertSame(100000, (int) $cashAdmin->ca_nominal);
        $this->assertSame(0, (int) $cashAdmin->cash_id, 'an operasional entry has no linked Cash row');
    }

    public function test_cash_gudang_operasional_insert_succeeds_with_cg_type_2(): void
    {
        $this->actingAsSuperAdminStaff();

        $customerId = (int) Customer::where('status', 1)->value('customer_id');

        $response = $this->post('/insertCashGudang', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'operasional',
            'items' => json_encode([[
                'customer_id' => $customerId,
                'cgd_nominal' => 100000,
                'cgd_notes' => 'Regression test expense line',
            ]]),
        ]);

        $response->assertStatus(200);

        $cashGudang = CashGudang::orderByDesc('cg_id')->firstOrFail();
        $this->assertSame(2, (int) $cashGudang->cg_type, 'operasional entries must be tagged cg_type=2, matching getCashGudang()\'s own balance math');
        $this->assertSame(100000, (int) $cashGudang->cg_nominal);
        $this->assertSame(0, (int) $cashGudang->cash_id, 'an operasional entry has no linked Cash row');
    }

    public function test_cash_gudang_saldo_insert_succeeds_with_cg_type_1_and_a_linked_cash_row(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->post('/insertCashGudang', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1, // pengajuan dana
            'cg_nominal' => 500000,
            'cg_notes' => 'Regression test fund request',
        ]);

        $response->assertStatus(200);

        $cashGudang = CashGudang::orderByDesc('cg_id')->firstOrFail();
        $this->assertSame(1, (int) $cashGudang->cg_type, 'saldo entries must be tagged cg_type=1, matching getCashGudang()\'s own balance math');
        $this->assertSame(500000, (int) $cashGudang->cg_nominal);
        $this->assertGreaterThan(0, (int) $cashGudang->cash_id, 'a saldo entry must link to a real cashes row');

        $cash = Cash::find($cashGudang->cash_id);
        $this->assertNotNull($cash);
        $this->assertSame(500000, (int) $cash->cash_nominal);
        $this->assertSame(2, (int) $cash->cash_tujuan, 'cash_tujuan=2 is Gudang');
    }
}
