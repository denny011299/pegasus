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

    /**
     * User-reported confusion: "Insertstockopname" (an AJAX mutation row) reads like it's about
     * opening a page, while the actual "Input Stok Opname" form page is auto-labeled
     * "Detailstockopname" — nothing ties the two together visually. formatModuleLabel() now
     * translates action-prefix + base module into a human label instead of a bare title-cased
     * URL segment.
     */
    public function test_module_labels_are_human_readable_not_raw_url_segments(): void
    {
        $staff = $this->actingAsSuperAdminStaff();

        // The actual create/edit form page -> "Input Stok Opname", not "Detailstockopname".
        $this->get('/detailStockOpname/-1')->assertStatus(200);
        $this->assertDatabaseHas('dashboard_change_logs', [
            'module_key' => 'detailstockopname',
            'activity_type' => 'open',
            'module_label' => 'Input Stok Opname',
        ]);

        // The AJAX insert mutation -> "Tambah Stok Opname", not "Insertstockopname".
        $stock = \App\Models\ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)->where('warehouse_id', 1)->firstOrFail();
        $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $staff->staff_id,
            'category_id' => (int) \Illuminate\Support\Facades\DB::table('categories')->where('status', 1)->value('category_id'),
            'sto_notes' => 'label test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $stock->product_id,
                'product_variant_id' => $stock->product_variant_id,
                'stod_system' => $stock->ps_stock.' pcs',
                'stod_real' => $stock->ps_stock.' pcs',
                'stod_selisih' => '0 pcs',
                'stod_notes' => null,
                'stod_touched' => 1,
            ]]),
        ])->assertStatus(200);
        $this->assertDatabaseHas('dashboard_change_logs', [
            'module_key' => 'insertstockopname',
            'activity_type' => 'change',
            'module_label' => 'Tambah Stok Opname',
        ]);
    }

    /**
     * There used to be a 15-minute debounce here (skip logging a repeat visit to the same
     * module). Removed 2026-08-15: it was coupled to the same early `return` as the
     * close-previous-open-row logic below, so re-opening a module you'd visited recently
     * silently failed to close whatever ELSE was open in between — e.g. Dashboard -> Customer
     * -> Dashboard again within 15 minutes left Customer's row stuck on "Sedang dibuka" forever.
     * User's call: every navigation gets recorded now, no debounce, duplicates included.
     */
    public function test_every_visit_is_recorded_even_repeat_visits_to_the_same_module(): void
    {
        $staff = $this->actingAsSuperAdminStaff();

        $this->get('/stockOpname')->assertStatus(200);
        $this->get('/stockOpname')->assertStatus(200);
        $this->get('/stockOpname')->assertStatus(200);

        $count = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'stockopname')
            ->where('activity_type', 'open')
            ->count();
        $this->assertSame(3, $count, 'every visit must be recorded, including repeats of the same module');

        // Only the LAST one is still "open" -- each earlier visit gets closed by the next one.
        $openCount = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'stockopname')
            ->where('activity_type', 'open')
            ->whereNull('duration_seconds')
            ->count();
        $this->assertSame(1, $openCount, 'each repeat visit must close the previous one, not just accumulate open sessions');
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

    public function test_navigating_back_to_the_dashboard_root_closes_the_previous_open_row(): void
    {
        // GitHub #53 follow-up (user-reported): routes/web.php renders the dashboard directly at
        // GET '/' (not just '/admin') -- '/' used to be excluded from shouldLogOpen() (copied
        // from shouldLogChange()'s exclude list), so the most common "back to dashboard" path
        // never closed out whatever menu was left open, leaving it stuck on "Sedang dibuka"
        // forever even while the user was actively looking at the Changelog panel itself.
        $staff = $this->actingAsSuperAdminStaff();

        $this->get('/stockOpname')->assertStatus(200);
        $firstOpen = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'stockopname')
            ->where('activity_type', 'open')
            ->firstOrFail();
        $this->assertNull($firstOpen->duration_seconds);

        $firstOpen->created_at = now()->subMinutes(3);
        $firstOpen->save();

        $this->get('/')->assertStatus(200);

        $firstOpen->refresh();
        $this->assertNotNull($firstOpen->duration_seconds, "navigating to '/' must retroactively close the previous open row too");

        $this->assertDatabaseHas('dashboard_change_logs', [
            'module_key' => 'dashboard',
            'activity_type' => 'open',
            'created_by' => $staff->staff_id,
            'duration_seconds' => null,
        ]);
    }

    public function test_revisiting_a_recently_opened_module_still_closes_whatever_is_currently_open(): void
    {
        // The exact user-reported scenario: Dashboard -> Customer -> Dashboard again (within
        // what used to be the 15-minute debounce window) must still close Customer's session.
        $staff = $this->actingAsSuperAdminStaff();

        $this->get('/')->assertStatus(200);
        $dashboardOpen = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'dashboard')->where('activity_type', 'open')
            ->firstOrFail();

        $this->get('/customer')->assertStatus(200);
        $dashboardOpen->refresh();
        $this->assertNotNull($dashboardOpen->duration_seconds, 'opening Customer must close the Dashboard session');

        $customerOpen = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'customer')->where('activity_type', 'open')
            ->firstOrFail();
        $this->assertNull($customerOpen->duration_seconds);

        // Back to Dashboard again, well within the old 15-minute debounce window.
        $this->get('/')->assertStatus(200);

        $customerOpen->refresh();
        $this->assertNotNull($customerOpen->duration_seconds, "revisiting Dashboard must close Customer's session even though Dashboard was opened moments ago");
    }

    /**
     * User-reported: "when 2 users logged in the same time... they share a time trigger" --
     * two concurrent login sessions of the SAME staff account (e.g. logged in on two
     * devices/browsers at once) used to close each other's 'open' row, because the only
     * scoping was staff_id. Each login session now gets its own marker (stored in the
     * session's own data, not the volatile Laravel session ID) so opening a page on device B
     * must NOT touch device A's still-genuinely-open row.
     */
    public function test_two_concurrent_sessions_of_the_same_staff_do_not_close_each_others_open_row(): void
    {
        $staff = $this->actingAsSuperAdminStaff();

        // "Device A" opens Stock Opname.
        $this->get('/stockOpname')->assertStatus(200);
        $deviceA = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'stockopname')->where('activity_type', 'open')
            ->firstOrFail();
        $this->assertNull($deviceA->duration_seconds);
        $markerA = $deviceA->meta['session_id'] ?? null;
        $this->assertNotNull($markerA, 'the open row must carry a session marker');

        // Simulate a second, independent login session for the SAME staff account (a
        // different browser/device) by dropping the session marker before the next request --
        // LogDashboardActivity::sessionMarker() then mints a fresh one, same as a brand new
        // browser session would get.
        session()->forget('_dashboard_activity_sid');
        $this->actingAsSuperAdminStaff();
        $this->get('/purchaseOrder')->assertStatus(200);

        $deviceA->refresh();
        $this->assertNull($deviceA->duration_seconds, "device B's navigation must not close device A's still-open session");

        $deviceB = DashboardChangeLog::where('created_by', $staff->staff_id)
            ->where('module_key', 'purchaseorder')->where('activity_type', 'open')
            ->firstOrFail();
        $markerB = $deviceB->meta['session_id'] ?? null;
        $this->assertNotNull($markerB);
        $this->assertNotSame($markerA, $markerB, 'each login session must get its own marker');

        // Back on "device A" (its own marker restored) -- opening another page must still
        // correctly close device A's own previous row.
        session(['_dashboard_activity_sid' => $markerA]);
        $this->get('/customer')->assertStatus(200);

        $deviceA->refresh();
        $this->assertNotNull($deviceA->duration_seconds, "device A's own navigation must still close its own previous row");

        $deviceB->refresh();
        $this->assertNull($deviceB->duration_seconds, "device A's navigation must not close device B's still-open session");
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
