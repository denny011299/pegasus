<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-21): `GET /getLog` backs the "Riwayat Stok Produk"/"Riwayat Stok Bahan Mentah"
 * history modal on the Stock Produk (`/stockProduct`) and Stock Bahan Mentah (`/stockSupplies`)
 * pages (Stock_Product.js / Stock_Supplies.js). It was registered inside the
 * `check.access:Peran & Perizinan|view` route group — an unrelated admin-only module ("Peran &
 * Perizinan" = Roles & Permissions) — instead of being reachable by staff who actually have view
 * access to Stok Produk/Stok Bahan Mentah. QC & Gudang staff who can open those pages got a 403 the
 * moment they clicked into a variant's stock history, with no explanatory message on the frontend
 * (GitHub #68 — "Staf QC & Gudang tidak bisa akses history stok").
 *
 * Fix: moved the `/getLog` route out of the `Peran & Perizinan|view` group into the login-only
 * utility area at the top of routes/web.php (same treatment as the `/autocomplete*` endpoints
 * right above it) — it's cross-module data already gated by the Stok Produk/Stok Bahan Mentah
 * pages that are the only callers, same precedent used for shared autocomplete lookups.
 *
 * See cdocs/testing/KNOWN_ISSUES.md.
 */
class GetLogGatedByWrongModuleTest extends TestCase
{
    use ActingAsStaff;

    public function test_staff_with_only_stok_produk_access_can_reach_get_log(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Produk', ['view']);

        $this->get('/getLog')->assertOk();
    }

    public function test_staff_with_only_stok_bahan_mentah_access_can_reach_get_log(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Bahan Mentah', ['view']);

        $this->get('/getLog')->assertOk();
    }

    public function test_staff_with_no_access_at_all_can_still_reach_get_log_once_logged_in(): void
    {
        // getLog is intentionally login-gated only (like the shared /autocomplete* endpoints),
        // not module-gated -- it has no module of its own since it's shared cross-module data.
        $this->actingAsStaffWithNoAccess();

        $this->get('/getLog')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/getLog')->assertRedirect('/login');
    }
}
