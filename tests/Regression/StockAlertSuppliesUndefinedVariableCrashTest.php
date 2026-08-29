<?php

namespace Tests\Regression;

use App\Models\Supplies;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED UPSTREAM (confirmed 2026-08-29) — GitHub issue #35 can be closed.
 *
 * History: confirmed 2026-08-09 while adding test coverage for rubenyw's Stock Alert rewrite,
 * which shipped with no tests. `App\Models\StockAlertSupplies::getStockAlertSupplies()` referenced
 * an undefined `$leadTimeDays` variable (only `$leadTime`, one line above, was ever assigned) when
 * building the response row. Under Laravel's default error handling that undefined-variable warning
 * becomes an ErrorException, so `GET /getStockAlertSupplies` — the whole "Peringatan Stok Bahan
 * Mentah" page's data endpoint — 500'd unconditionally for ANY warehouse with at least one active
 * `supplies` row, i.e. every real warehouse. It was left unfixed on purpose then, per this repo's
 * "queue bugs, don't fix someone else's code" policy.
 *
 * rubenyw has since fixed it upstream (the assignment and the read both use `$leadTime` now — see
 * StockAlertSupplies.php's `$leadTime = max(0, (int) ($value->lead_time_days ?? 0));` and
 * `$value->lead_time_days = $leadTime;`). This test therefore now asserts the CORRECT behavior,
 * exactly as its previous docblock instructed ("flip the assertion to assertStatus(200) once the
 * one-line fix lands upstream"), and stands as a plain regression guard against it coming back.
 *
 * The full behavioral coverage in tests/Workflow/StockAlertSuppliesFlowTest.php was skipped
 * pending this fix and has been un-skipped in the same change.
 */
class StockAlertSuppliesUndefinedVariableCrashTest extends TestCase
{
    use ActingAsStaff;

    public function test_getStockAlertSupplies_no_longer_crashes_on_an_active_supply_row(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(1);

        $supply = new Supplies();
        $supply->supplies_name = 'Regression Crash Test Supply '.uniqid();
        $supply->supplies_unit = json_encode([9]);
        $supply->supplies_default_unit = 9;
        $supply->status = 1;
        $supply->save();

        $ss = new SuppliesStock();
        $ss->supplies_id = $supply->supplies_id;
        $ss->unit_id = 9;
        $ss->warehouse_id = 1;
        $ss->ss_stock = 5;
        $ss->status = 1;
        $ss->save();

        $response = $this->get('/getStockAlertSupplies?warehouse_id=1');

        $response->assertStatus(200);
    }
}
