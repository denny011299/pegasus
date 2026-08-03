<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `check.access.any:ModuleA,ModuleB,ModuleC,ability` only ever enforces
 * ModuleA, always with a hardcoded `view` ability — Laravel splits the
 * comma-separated middleware string into separate positional arguments
 * before calling handle(), but App\Http\Middleware\checkAccessAny::handle()
 * only declares one `string $spec` parameter, so everything after the first
 * comma segment is silently dropped. Confirmed via func_num_args() returning
 * 5 for a route whose middleware declared 3 comma-separated segments.
 *
 * Full writeup: cdocs/testing/KNOWN_ISSUES.md. Confirmed 2026-08-01,
 * deliberately deferred by user decision — NOT fixed. These tests
 * characterize the CURRENT (buggy) behavior on purpose across the different
 * "shapes" the bug takes, so a real fix shows up here as an intentional,
 * reviewed test change rather than a silent green->red flip. See also
 * tests/Smoke/AkuntansiLaporanSmokeTest.php's reportSelisihOpname tests,
 * which demonstrate the same defect inline where it was first found — this
 * file is the permanent, "must never be deleted" record per
 * guides/REGRESSION_GUIDE.md.
 */
class CheckAccessAnyOnlyChecksFirstModuleTest extends TestCase
{
    use ActingAsStaff;

    // --- /area: check.access.any:Kategori,Satuan,Variasi,view ---

    public function test_area_loads_when_granted_the_first_listed_module(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kategori');

        $this->get('/area')->assertStatus(200);
    }

    /**
     * Per the route's own declaration, granting 'Satuan' (the second listed
     * module) should also unlock this page. It currently does not.
     */
    public function test_area_currently_ignores_the_second_listed_module(): void
    {
        $this->actingAsStaffWithOnlyPermission('Satuan');

        $this->get('/area')->assertStatus(403);
    }

    // --- /getCashAdmin: check.access.any:Kas Operasional Admin,Kas Admin,Kas Operasional,any ---

    public function test_get_cash_admin_loads_when_granted_view_on_the_first_listed_module(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Operasional Admin', ['view']);

        $this->get('/getCashAdmin')->assertStatus(200);
    }

    /**
     * Per the route's own declaration, granting 'Kas Admin' (the second
     * listed module) should also unlock this endpoint. It currently does not.
     */
    public function test_get_cash_admin_currently_ignores_the_second_listed_module(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Admin', ['view']);

        $this->get('/getCashAdmin')->assertStatus(403);
    }

    /**
     * The route's trailing `,any` means "any ability on any listed module
     * should pass" — so a staff granted only 'create' (not 'view') on the
     * first module should still get in. It currently does not, because the
     * ability defaults to a hardcoded 'view' once the `,any` suffix itself
     * is silently dropped along with the extra modules.
     */
    public function test_get_cash_admin_currently_ignores_the_any_ability_suffix(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Operasional Admin', ['create']);

        $this->get('/getCashAdmin')->assertStatus(403);
    }
}
