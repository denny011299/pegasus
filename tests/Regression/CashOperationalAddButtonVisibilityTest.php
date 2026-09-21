<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #196 item 28: the "Tambah Aktivitas" button on /operationalCash (page-header.blade.php)
 * used to be gated by `@roleCanAny(['Kas', 'Kas Operasional'], 'create')` — the same generic-module
 * blind spot as CashOperasionalPresenter's row-level ACC/Tolak buttons (see
 * CashOperasionalPresenterPerTypeAccessTest). A role scoped only to a per-type submodule (e.g.
 * "Kas Operasional Gudang") could open the page but never saw the add button, even with `create`
 * granted on that exact submodule.
 */
class CashOperationalAddButtonVisibilityTest extends TestCase
{
    use ActingAsStaff;

    public function test_add_button_hidden_for_role_with_no_create_access_anywhere(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Operasional Gudang', ['view', 'others']);

        $this->get('/operationalCash')
            ->assertStatus(200)
            ->assertDontSee('btnAddCash', false);
    }

    public function test_add_button_shown_for_role_scoped_to_gudang_submodule_only(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Operasional Gudang', ['view', 'create']);

        $this->get('/operationalCash')
            ->assertStatus(200)
            ->assertSee('btnAddCash', false);
    }

    public function test_add_button_shown_for_role_scoped_to_armada_submodule_only(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Armada', ['view', 'create']);

        $this->get('/operationalCash')
            ->assertStatus(200)
            ->assertSee('btnAddCash', false);
    }

    public function test_add_button_shown_for_generic_kas_operasional_module(): void
    {
        $this->actingAsStaffWithOnlyPermission('Kas Operasional', ['view', 'create']);

        $this->get('/operationalCash')
            ->assertStatus(200)
            ->assertSee('btnAddCash', false);
    }
}
