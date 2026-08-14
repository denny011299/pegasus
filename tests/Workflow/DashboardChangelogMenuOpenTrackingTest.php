<?php

namespace Tests\Workflow;

use App\Models\DashboardChangeLog;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #53: "jam dia buka modul atau menu apapun" — App\Http\Middleware\LogDashboardActivity
 * now also logs activity_type='open' rows for real page views (GET requests that render a Blade
 * view), separate from the pre-existing activity_type='change' rows for POST/PUT/PATCH/DELETE
 * mutations. Duration is a passive estimate: the gap between one 'open' row and that same staff's
 * next tracked request, filled in retroactively — see LogDashboardActivity::logOpen().
 */
class DashboardChangelogMenuOpenTrackingTest extends TestCase
{
    use ActingAsStaff;

    public function test_opening_a_page_logs_an_open_row_with_staff_and_null_duration(): void
    {
        $staff = $this->actingAsSuperAdminStaff();

        $this->get('/stockOpname')->assertStatus(200);

        $this->assertDatabaseHas('dashboard_change_logs', [
            'module_key' => 'stockopname',
            'activity_type' => 'open',
            'created_by' => $staff->staff_id,
            'duration_seconds' => null,
        ]);
    }

    public function test_reopening_the_same_module_within_the_debounce_window_does_not_duplicate(): void
    {
        $staff = $this->actingAsSuperAdminStaff();

        $this->get('/stockOpname')->assertStatus(200);
        $this->get('/stockOpname')->assertStatus(200);
        $this->get('/stockOpname')->assertStatus(200);

        $count = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'stockopname')
            ->where('activity_type', 'open')
            ->count();
        $this->assertSame(1, $count, 'opening the same module repeatedly within 15 minutes must debounce to a single row');
    }

    public function test_opening_a_different_module_closes_the_previous_open_row_with_a_duration(): void
    {
        $staff = $this->actingAsSuperAdminStaff();

        $this->get('/stockOpname')->assertStatus(200);
        $firstOpen = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'stockopname')
            ->where('activity_type', 'open')
            ->firstOrFail();
        $this->assertNull($firstOpen->duration_seconds);

        // Back-date it so the diff is deterministic and clearly under the 4h cap.
        $firstOpen->created_at = now()->subMinutes(5);
        $firstOpen->save();

        $this->get('/purchaseOrder')->assertStatus(200);

        $firstOpen->refresh();
        $this->assertNotNull($firstOpen->duration_seconds, 'navigating away must retroactively fill the previous open row\'s duration');
        $this->assertGreaterThanOrEqual(290, $firstOpen->duration_seconds); // ~5 minutes, allow test-runtime slack

        $secondOpen = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'purchaseorder')
            ->where('activity_type', 'open')
            ->firstOrFail();
        $this->assertNull($secondOpen->duration_seconds, 'the newly opened module is still an open session');
    }

    public function test_a_gap_longer_than_the_cap_is_left_without_a_duration(): void
    {
        $staff = $this->actingAsSuperAdminStaff();

        $this->get('/stockOpname')->assertStatus(200);
        $firstOpen = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'stockopname')
            ->where('activity_type', 'open')
            ->firstOrFail();

        // Simulate a tab left open overnight: gap far beyond the 4h sanity cap.
        $firstOpen->created_at = now()->subHours(10);
        $firstOpen->save();

        $this->get('/purchaseOrder')->assertStatus(200);

        $firstOpen->refresh();
        $this->assertNull($firstOpen->duration_seconds, 'a gap beyond the cap must not be recorded as a real duration');
    }

    public function test_menu_open_rows_are_excluded_from_the_changelog_pending_kpi(): void
    {
        $staff = $this->actingAsSuperAdminStaff();

        // Baseline via the real endpoint (not a raw table count) so it goes through the same
        // "current period" date filter as the KPI itself — seeded rows outside that window would
        // otherwise make a raw count diverge from what the endpoint actually reports.
        $before = (int) $this->get('/getDashboardOverview')->json('changelog.changelog_pending');

        // A real mutation -> activity_type='change', counted by the KPI.
        DashboardChangeLog::create([
            'module_key' => 'master_bahan',
            'activity_type' => 'change',
            'module_label' => 'Master Bahan',
            'reference' => 'BHN #1',
            'what_changed' => 'Master bahan diperbarui.',
            'summary' => 'Test',
            'created_by' => $staff->staff_id,
        ]);

        // A page view -> activity_type='open', must NOT be counted.
        $this->get('/stockOpname')->assertStatus(200);

        $response = $this->get('/getDashboardOverview');
        $response->assertStatus(200);
        $pending = (int) $response->json('changelog.changelog_pending');

        $this->assertSame($before + 1, $pending, 'the Changelog KPI must count the mutation row but not the menu-open row');
    }
}
