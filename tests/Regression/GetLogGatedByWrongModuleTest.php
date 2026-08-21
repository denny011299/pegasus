<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-21): `GET /getLog` backs the "Riwayat Stok Produk"/"Riwayat Stok Bahan Mentah"
 * history modal on the Stock Produk (`/stockProduct`) and Stock Bahan Mentah (`/stockSupplies`)
 * pages (Stock_Product.js / Stock_Supplies.js). It was registered inside the
 * `check.access:Peran & Perizinan|view` route group — an unrelated admin-only module ("Peran &
 * Perizinan" = Roles & Permissions) — instead of being reachable by staff who actually have access
 * to view stock data. QC & Gudang staff who could open those pages got a 403 the moment they
 * clicked into a variant's stock history, with no explanatory message on the frontend
 * (GitHub #68 — "Staf QC & Gudang tidak bisa akses history stok").
 *
 * Fix, round 1: moved `/getLog` out of the `Peran & Perizinan|view` group into the login-only
 * utility area (same treatment as `/autocomplete*`), since no static `check.access:Module|ability`
 * middleware string could express "either Stok Produk or Stok Bahan Mentah" without hitting the
 * check.access.any first-module-only bug (see memory `pegasus-check-access-any-bug`).
 *
 * Fix, round 2 (per explicit product decision): the module actually required is `Daftar Produk`
 * for product history (`log_type=1`) and `Daftar Bahan Mentah` for bahan mentah history
 * (`log_type=2`) — not the Stok Produk/Stok Bahan Mentah page-view modules. Since the module needed
 * depends on a *request parameter* (`log_type`), not a fixed route, no `check.access` middleware
 * string can express it either — `GeneralController::getLog()` now validates inline via
 * `RoleAccess::can()`, keyed off `log_type`, and aborts 403 for a missing/unrecognized `log_type`
 * or a staff member lacking the corresponding module's `view` ability.
 *
 * See cdocs/testing/KNOWN_ISSUES.md.
 */
class GetLogGatedByWrongModuleTest extends TestCase
{
    use ActingAsStaff;

    public function test_staff_with_daftar_produk_access_can_view_product_history(): void
    {
        $this->actingAsStaffWithOnlyPermission('Daftar Produk', ['view']);

        $this->get('/getLog?log_type=1&log_item_id=1')->assertOk();
    }

    public function test_staff_with_daftar_produk_access_cannot_view_bahan_mentah_history(): void
    {
        $this->actingAsStaffWithOnlyPermission('Daftar Produk', ['view']);

        $this->get('/getLog?log_type=2&log_item_id=1')->assertForbidden();
    }

    public function test_staff_with_daftar_bahan_mentah_access_can_view_bahan_mentah_history(): void
    {
        $this->actingAsStaffWithOnlyPermission('Daftar Bahan Mentah', ['view']);

        $this->get('/getLog?log_type=2&log_item_id=1')->assertOk();
    }

    public function test_staff_with_daftar_bahan_mentah_access_cannot_view_product_history(): void
    {
        $this->actingAsStaffWithOnlyPermission('Daftar Bahan Mentah', ['view']);

        $this->get('/getLog?log_type=1&log_item_id=1')->assertForbidden();
    }

    public function test_staff_with_only_stok_produk_page_access_cannot_view_product_history(): void
    {
        // Confirms the fix really keys off 'Daftar Produk', not 'Stok Produk' (the page itself) --
        // a role granted only the stock-page module must still be denied.
        $this->actingAsStaffWithOnlyPermission('Stok Produk', ['view']);

        $this->get('/getLog?log_type=1&log_item_id=1')->assertForbidden();
    }

    public function test_staff_with_no_access_at_all_is_blocked(): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get('/getLog?log_type=1&log_item_id=1')->assertForbidden();
    }

    public function test_missing_log_type_is_blocked_even_for_a_privileged_staff(): void
    {
        $this->actingAsStaffWithOnlyPermission('Daftar Produk', ['view']);

        $this->get('/getLog?log_item_id=1')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/getLog?log_type=1&log_item_id=1')->assertRedirect('/login');
    }
}
