<?php

namespace Tests\Workflow;

use App\Models\Cash;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/CASH_BASE_AND_PETTY_CASH_FLOW.md for the fully-traced flow this
 * asserts against. `/insertCash` is a simpler, standalone entry point with no `accept*`/`decline*`
 * route at all — unlike every other Kas Operasional flow traced so far. Petty Cash has no working
 * Workflow path to test at all — `/insertPettyCash` crashes 500 on every call (genuine migration
 * drift, `petty_cashes` is missing 4 columns the model writes to) — see
 * `tests/Regression/InsertPettyCashCrashesOnMissingColumnsTest.php` instead.
 */
class CashBaseAndPettyCashFlowTest extends TestCase
{
    use ActingAsStaff;

    public function test_insert_cash_creates_an_already_approved_standalone_row(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->post('/insertCash', [
            'cash_date' => now()->toDateString(),
            'cash_description' => 'Workflow test base cash entry',
            'cash_type' => 1,
            'cash_nominal' => 250000,
        ]);
        $response->assertStatus(200);

        $cash = Cash::orderByDesc('cash_id')->firstOrFail();
        $this->assertSame(2, (int) $cash->status, 'insertCash never passes status, so Cash::insertCash\'s own ?? 2 default applies — already "approved", no pending state at all');
        $this->assertSame(250000, (int) $cash->cash_nominal);
        $this->assertSame(1, (int) $cash->cash_type);
        $this->assertSame(0, (int) $cash->cash_tujuan, 'the real frontend never sends cash_tujuan (commented out in Cash.js) — defaults to 0, not linked to any CashAdmin/CashGudang row');
        $this->assertSame(0, (int) $cash->person_id, 'person_id also defaults to 0 when not supplied');

        $this->assertDatabaseMissing('cash_admins', ['cash_id' => $cash->cash_id]);
        $this->assertDatabaseMissing('cash_gudangs', ['cash_id' => $cash->cash_id]);
    }
}
