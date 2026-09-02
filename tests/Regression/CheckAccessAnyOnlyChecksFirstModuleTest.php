<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Fixed 2026-09-02: `check.access.any:A,B,C,ability` now receives every comma-separated
 * segment as a separate middleware argument (variadic `...$segments` in
 * App\Http\Middleware\checkAccessAny). Previously only the first segment was read.
 *
 * Full writeup: cdocs/testing/KNOWN_ISSUES.md (historical — bug fixed).
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

    public function test_area_loads_when_granted_the_second_listed_module(): void
    {
        $this->actingAsStaffWithOnlyPermission('Satuan');

        $this->get('/area')->assertStatus(200);
    }

    // --- /getCashAdmin: check.access.any:Kas Operasional Admin,Kas Admin,Kas Operasional,any ---

    public function test_get_cash_admin_loads_when_granted_view_on_the_first_listed_module(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Operasional Admin', ['view']);

        $this->get('/getCashAdmin')->assertStatus(200);
    }

    public function test_get_cash_admin_loads_when_granted_the_second_listed_module(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Admin', ['view']);

        $this->get('/getCashAdmin')->assertStatus(200);
    }

    public function test_get_cash_admin_honors_the_any_ability_suffix(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Operasional Admin', ['create']);

        $this->get('/getCashAdmin')->assertStatus(200);
    }
}
