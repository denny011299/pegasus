<?php

namespace Tests\Regression;

use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: several Kas Operasional insert paths never set the `*_type` column
 * their own model unconditionally reads, crashing with a 500
 * ("Undefined array key ...") instead of ever creating a row. Found while
 * building the Kas Operasional Workflow pilot
 * (cdocs/testing/workflows/KAS_OPERASIONAL_FLOW.md).
 *
 * - `ReportController::insertCashAdmin()`'s "operasional" (expense) branch
 *   never sets `ca_type`; `CashAdmin::insertCashAdmin()` does
 *   `$t->ca_type = $data["ca_type"];` with no fallback.
 * - `ReportController::insertCashGudang()` never sets `cg_type` in EITHER
 *   branch (operasional or saldo); same unconditional read in
 *   `CashGudang::insertCashGudang()`.
 *
 * The correct values are inferable with high confidence from
 * `CashAdmin::getCashAdmin()`/`CashGudang::getCashGudang()`'s own balance
 * math, which already branches on `type==1` (saldo, add/subtract by `aksi`)
 * vs `type==2` (operational expense, always subtracts) — the read side
 * already expects this; the write side just never sets it for these paths.
 * `CashAdmin`'s own "saldo" branch already sets `ca_type = 1` correctly,
 * which is what makes this bug specifically about the "operasional" branch
 * there, but a whole-function gap for `CashGudang`.
 *
 * Confirmed 2026-08-01, deliberately deferred by user decision — NOT fixed.
 * `CashArmada`/`CashSales` were not confirmed broken (`CashArmada`'s model
 * has a `?? 3` fallback the other two lack) and are out of scope here.
 *
 * These tests characterize the CURRENT (crashing) behavior on purpose, so a
 * real fix shows up here as an intentional, reviewed test change — flip
 * each `assertStatus(500)` to a real assertion once the missing `*_type`
 * assignment is added.
 */
class CashOperasionalMissingTypeFieldTest extends TestCase
{
    use ActingAsStaff;

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_cash_admin_operasional_insert_currently_crashes(): void
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

        $response->assertStatus(500);
    }

    public function test_cash_gudang_operasional_insert_currently_crashes(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->post('/insertCashGudang', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'operasional',
            'items' => json_encode([[
                'cgd_nominal' => 100000,
                'cgd_notes' => 'Regression test expense line',
            ]]),
        ]);

        $response->assertStatus(500);
    }

    public function test_cash_gudang_saldo_insert_currently_crashes(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->post('/insertCashGudang', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'saldo',
            'oc_transaksi' => 1,
            'cg_nominal' => 500000,
            'cg_notes' => 'Regression test fund request',
        ]);

        $response->assertStatus(500);
    }
}
