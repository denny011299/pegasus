<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: components/modal-popup.blade.php did
 * `in_array('others', $akses->firstWhere('name', 'X')->akses)` for the Produk
 * Bermasalah, Produksi, and Pengiriman approve/decline modals. role_id = -1
 * ("super admin", per App\Support\RoleAccess::isSuperAdmin) has no matching
 * row in the `roles` table, so such a staff's role_access is never
 * populated, $akses is an empty collection, firstWhere() returns null, and
 * ->akses on null threw a 500. No staff row in current data actually uses
 * role_id = -1, so this was dormant rather than an active incident — but the
 * app explicitly treats -1 as a real, supported super-admin bypass elsewhere.
 *
 * Fixed 2026-08-01 by switching those 3 call sites to the existing
 * `@roleCan` Blade directive (App\Support\RoleAccess::can()), which already
 * handles the super-admin bypass safely everywhere else in the app.
 */
class SuperAdminModalPopupNullAksesTest extends TestCase
{
    use ActingAsStaff;

    public function test_product_issue_page_loads_for_super_admin(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get('/productIssue')->assertStatus(200);
    }

    public function test_production_page_loads_for_super_admin(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get('/production')->assertStatus(200);
    }

    public function test_sales_order_page_loads_for_super_admin(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get('/salesOrder')->assertStatus(200);
    }
}
