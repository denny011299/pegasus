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
 * Fix, round 2 (2026-08-21, since SUPERSEDED): keyed the module off `Daftar Produk`/`Daftar Bahan
 * Mentah` instead — turned out to be a mistaken assumption, not the real answer (see below).
 *
 * Fix, round 3 — the actual correction (2026-08-29, `c217531`, "mistaken getLogs access fix"): the
 * module this endpoint's own history modal actually lives behind is `Stok Produk`/`Stok Bahan
 * Mentah` (the SAME pages the modal opens from), not `Daftar Produk`/`Daftar Bahan Mentah` — round
 * 2's module names were themselves the mistake. `GeneralController::getLog()` still validates
 * inline via `RoleAccess::can()`, keyed off `log_type` (no static `check.access` string can express
 * "either Stok Produk or Stok Bahan Mentah depending on a request parameter"), just against the
 * corrected module names.
 *
 * See cdocs/testing/KNOWN_ISSUES.md and memory `pegasus-log-access-prefix-correction`.
 */
class GetLogGatedByWrongModuleTest extends TestCase
{
    use ActingAsStaff;

    public function test_staff_with_stok_produk_access_can_view_product_history(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Produk', ['view']);

        $this->get('/getLog?log_type=1&log_item_id=1')->assertOk();
    }

    public function test_staff_with_stok_produk_access_cannot_view_bahan_mentah_history(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Produk', ['view']);

        $this->get('/getLog?log_type=2&log_item_id=1')->assertForbidden();
    }

    public function test_staff_with_stok_bahan_mentah_access_can_view_bahan_mentah_history(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Bahan Mentah', ['view']);

        $this->get('/getLog?log_type=2&log_item_id=1')->assertOk();
    }

    public function test_staff_with_stok_bahan_mentah_access_cannot_view_product_history(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Bahan Mentah', ['view']);

        $this->get('/getLog?log_type=1&log_item_id=1')->assertForbidden();
    }

    public function test_staff_with_only_daftar_produk_access_cannot_view_product_history(): void
    {
        // Confirms the fix really keys off 'Stok Produk', not 'Daftar Produk' (round 2's mistaken
        // module, sharing the same real-sounding "produk master data" area) -- a role granted only
        // that module must still be denied.
        $this->actingAsStaffWithOnlyPermission('Daftar Produk', ['view']);

        $this->get('/getLog?log_type=1&log_item_id=1')->assertForbidden();
    }

    public function test_staff_with_no_access_at_all_is_blocked(): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get('/getLog?log_type=1&log_item_id=1')->assertForbidden();
    }

    public function test_missing_log_type_is_blocked_even_for_a_privileged_staff(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Produk', ['view']);

        $this->get('/getLog?log_item_id=1')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/getLog?log_type=1&log_item_id=1')->assertRedirect('/login');
    }
}
