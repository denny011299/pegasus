<?php

namespace Tests\Regression;

use App\Models\CashArmada;
use App\Models\Customer;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `ReportController::acceptCashArmada()`'s negative-balance guard is commented out:
 *
 *   // if ($customer->customer_saldo < 0){
 *   //     return response()->json([... "Saldo armada ... tidak mencukupi"]);
 *   // }
 *   $customer->save();
 *
 * Found while tracing cdocs/testing/workflows/CASH_ARMADA_FLOW.md (Phase 3 pilot).
 * `acceptCashSales()` has the identical disabled pattern for `staffs.staff_saldo` (confirmed by a
 * quick read, not separately characterized here). Confirmed 2026-08-01, deliberately deferred —
 * this looks like an intentionally-disabled validation (the message is fully written, just
 * commented out), not an oversight, so it wasn't re-enabled on a guess. See
 * cdocs/testing/KNOWN_ISSUES.md. This test characterizes the CURRENT (allowed) behavior on
 * purpose — flip the assertion once the guard is re-enabled.
 */
class CashArmadaNegativeBalanceGuardDisabledTest extends TestCase
{
    use ActingAsStaff;

    public function test_accepting_an_expense_that_drives_customer_saldo_negative_is_currently_allowed(): void
    {
        $this->actingAsSuperAdminStaff();

        $customer = Customer::where('status', 1)->firstOrFail();
        $customer->customer_saldo = 100;
        $customer->save();

        $this->post('/insertCashArmada', [
            'customer_id' => $customer->customer_id,
            'oc_transaksi' => 'operasional',
            'photo' => '',
            'items' => json_encode([[
                'crd_nominal' => 5000,
                'crd_notes' => 'Regression test expense line',
                'crd_type' => 2, // != 1, an ordinary expense
            ]]),
        ])->assertStatus(200);

        $crId = (int) CashArmada::orderByDesc('cr_id')->value('cr_id');

        $this->post('/acceptCashArmada', ['cr_id' => $crId])->assertStatus(200);

        $customer->refresh();
        $this->assertSame(-4900, (int) $customer->customer_saldo);
    }
}
