<?php

namespace Tests\Regression;

use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `PettyCash::insertPettyCash()` writes to 4 columns that do not exist on `petty_cashes`:
 *
 *   $t->pc_description = $data["pc_description"];   // no such column
 *   $t->pc_nominal = $data["pc_nominal"];             // no such column
 *   $t->pc_type = $data["pc_type"];                   // no such column
 *   $t->cc_id = $data["cc_id"];                       // no such column
 *
 * `petty_cashes`'s real columns (confirmed via `Schema::getColumnListing`): `pc_id`, `pc_date`,
 * `staff_id`, `status`, `created_by`, `acc_by`, `created_at`, `updated_at`. A separate
 * `petty_cash_details` table (`pcd_id`, `pcd_notes`, `cc_id`, `pcd_nominal`) and `PettyCashDetail`
 * model exist — the same header+detail split every other Kas variant uses — but `PettyCashDetail`
 * is never referenced anywhere else in the codebase (confirmed by grep), so the split was never
 * actually wired up. Every single call to `/insertPettyCash` crashes with a fatal
 * `QueryException` — this is 100% broken, not an edge case. Confirmed 2026-08-02 while tracing
 * `cdocs/testing/workflows/CASH_BASE_AND_PETTY_CASH_FLOW.md`. Same class of finding as the
 * already-documented migration-drift issues (`pegasus-migrations-drift` memory), just not
 * previously caught since `SchemaConsistencyTest` only checks a model's own table/primary-key
 * existence, not every column an insert/update method writes to. Deliberately deferred, not
 * fixed. This test characterizes the CURRENT (crashing) behavior on purpose.
 */
class InsertPettyCashCrashesOnMissingColumnsTest extends TestCase
{
    use ActingAsStaff;

    public function test_insert_petty_cash_crashes_with_a_column_not_found_error(): void
    {
        $this->actingAsSuperAdminStaff();

        $staffId = (int) DB::table('staffs')->where('status', 1)->value('staff_id');
        $categoryId = (int) DB::table('cash_categories')->where('status', 1)->value('cc_id');
        $countBefore = DB::table('petty_cashes')->count();

        $response = $this->post('/insertPettyCash', [
            'pc_date' => now()->toDateString(),
            'staff_id' => $staffId,
            'pc_description' => 'Regression test petty cash entry',
            'pc_nominal' => 50000,
            'pc_type' => 1,
            'cc_id' => $categoryId,
        ]);

        // The crash is still live — this test does not fix it, only characterizes it.
        $response->assertStatus(500);

        $this->assertSame($countBefore, DB::table('petty_cashes')->count(), 'no row can ever be persisted — the insert crashes before completing');
    }
}
