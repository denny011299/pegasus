<?php

namespace Tests\Regression;

use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `ReportController::insertCash()` checks `$data['cash_tujuan']` against the literal strings
 * `"admin"`/`"gudang"` to decide whether to auto-link a `CashAdmin`/`CashGudang` row — but
 * `Cash::insertCash()`'s own string→int mapping for this exact scenario is commented out:
 *
 *   // if ($data['cash_tujuan'] == "admin") $data['cash_tujuan'] = 1;
 *   // else if ($data['cash_tujuan'] == "gudang") $data['cash_tujuan'] = 2;
 *
 * `cashes.cash_tujuan` is an `integer` column. The real frontend (`Cash.js:592`) never sends this
 * field at all (commented out there too), so this is dead code from the real UI's perspective —
 * but if anything ever DID send the literal string the controller's own `if` checks expect, the
 * very first `Cash::insertCash($data)` call (which runs BEFORE the cash_tujuan branch) crashes
 * with a fatal DB error, not a clean validation response. Confirmed 2026-08-02 while tracing
 * `cdocs/testing/workflows/CASH_BASE_AND_PETTY_CASH_FLOW.md` — two commented-out mapping lines and
 * a live string-comparison `if` were clearly meant to work together and no longer do. Deliberately
 * deferred, not fixed. This test characterizes the CURRENT (crashing) behavior.
 */
class InsertCashTujuanStringCrashesTest extends TestCase
{
    use ActingAsStaff;

    public function test_a_literal_admin_string_cash_tujuan_crashes_with_a_db_error(): void
    {
        $this->actingAsSuperAdminStaff();

        $countBefore = DB::table('cashes')->count();

        $response = $this->post('/insertCash', [
            'cash_date' => now()->toDateString(),
            'cash_description' => 'Regression test cash_tujuan crash',
            'cash_type' => 1,
            'cash_nominal' => 100000,
            'cash_tujuan' => 'admin',
        ]);

        // The crash is still live — this test does not fix it, only characterizes it.
        $response->assertStatus(500);

        $this->assertSame($countBefore, DB::table('cashes')->count(), 'the insert crashes before the row can be persisted');
    }
}
