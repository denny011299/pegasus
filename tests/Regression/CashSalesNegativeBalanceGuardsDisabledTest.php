<?php

namespace Tests\Regression;

use App\Models\CashSales;
use App\Models\Staff;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: CashSales has TWO independent, commented-out balance guards — not just one like CashArmada.
 *
 * 1. `ReportController::insertCashSales()`'s operasional branch (lines ~1561-1569): would have
 *    refused the INSERT itself if the expense total exceeds the staff's current `staff_saldo`.
 * 2. `ReportController::acceptCashSales()` (lines ~1748-1754): would have refused ACCEPTING an
 *    entry that drives `staff_saldo` negative — the identical pattern to
 *    `CashArmadaNegativeBalanceGuardDisabledTest`.
 *
 * Both are fully-written messages, just commented out. Found while tracing
 * cdocs/testing/workflows/CASH_SALES_FLOW.md. Confirmed 2026-08-01, deliberately deferred pending
 * developer confirmation — see cdocs/testing/KNOWN_ISSUES.md. This test characterizes the CURRENT
 * (allowed) behavior on purpose — flip it once either guard is re-enabled.
 */
class CashSalesNegativeBalanceGuardsDisabledTest extends TestCase
{
    use ActingAsStaff;

    private function pickStaff(): Staff
    {
        return Staff::where('status', 1)->firstOrFail();
    }

    public function test_inserting_an_expense_larger_than_staff_saldo_is_currently_allowed(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $staff->staff_saldo = 100;
        $staff->save();

        $this->post('/insertCashSales', [
            'staff_id' => $staff->staff_id,
            'bank_id' => 0,
            'oc_transaksi' => 'operasional',
            'photo' => '',
            'items' => json_encode([[
                'csd_nominal' => 999999,
                'csd_notes' => 'Regression test — over-limit expense',
                'csd_type' => 2,
            ]]),
        ])->assertStatus(200);

        $csId = (int) CashSales::orderByDesc('cs_id')->value('cs_id');
        $this->assertDatabaseHas('cash_sales', ['cs_id' => $csId, 'cs_nominal' => 999999]);
    }

    public function test_accepting_an_expense_that_drives_staff_saldo_negative_is_currently_allowed(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $staff->staff_saldo = 100;
        $staff->save();

        $this->post('/insertCashSales', [
            'staff_id' => $staff->staff_id,
            'bank_id' => 0,
            'oc_transaksi' => 'operasional',
            'photo' => '',
            'items' => json_encode([[
                'csd_nominal' => 5000,
                'csd_notes' => 'Regression test expense line',
                'csd_type' => 2,
            ]]),
        ])->assertStatus(200);

        $csId = (int) CashSales::orderByDesc('cs_id')->value('cs_id');

        $this->post('/acceptCashSales', ['cs_id' => $csId])->assertStatus(200);

        $staff->refresh();
        $this->assertSame(-4900, (int) $staff->staff_saldo);
    }
}
