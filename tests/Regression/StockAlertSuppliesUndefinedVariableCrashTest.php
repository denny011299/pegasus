<?php

namespace Tests\Regression;

use App\Models\Supplies;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Confirmed 2026-08-09, NOT fixed here — deliberately left for the team to fix (GitHub issue #35,
 * KNOWN_ISSUES.md). Found while adding test coverage for rubenyw's Stock Alert rewrite, which
 * shipped with no tests: App\Models\StockAlertSupplies::getStockAlertSupplies() references an
 * undefined `$leadTimeDays` variable (only `$leadTime`, one line above, is ever assigned) when
 * building the response row. Under Laravel's default error handling this undefined-variable
 * warning is converted to an ErrorException, so the request 500s — meaning
 * `GET /getStockAlertSupplies` (the whole "Peringatan Stok Bahan Mentah" page's data endpoint)
 * crashes unconditionally for ANY warehouse with at least one active `supplies` row. Since every
 * real warehouse has active supplies, the page is 100% broken as shipped.
 *
 * This asserts the CURRENT (buggy) behavior on purpose, per this repo's regression-test
 * convention for a confirmed-but-deferred bug — flip the assertion to `assertStatus(200)` once
 * the one-line fix (`$leadTimeDays` -> `$leadTime` on the assignment line) lands upstream.
 *
 * See KNOWN_ISSUES.md. The full intended behavioral coverage already exists but is skipped
 * pending this fix — tests/Workflow/StockAlertSuppliesFlowTest.php.
 */
class StockAlertSuppliesUndefinedVariableCrashTest extends TestCase
{
    use ActingAsStaff;

    public function test_getStockAlertSupplies_currently_crashes_on_an_active_supply_row(): void
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

        $response->assertStatus(500);
    }
}
