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
 *
 * Follow-up: the generic "Kas"/"Kas Operasional" module (and alias names like "Kas Admin", "Kas
 * Gudang", ...) were later removed from the permission-checking code entirely — they don't exist
 * in public/assets/json/permission.json, so no role can ever be granted or denied them through the
 * Izin Akses UI; keeping them as a fallback let stale `role_access` rows silently grant access an
 * admin had no way to see or revoke. Only the 4 real submodule names remain.
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
        $this->actingAsStaffWithOnlyPermission('Kas Operasional Armada', ['view', 'create']);

        $this->get('/operationalCash')
            ->assertStatus(200)
            ->assertSee('btnAddCash', false);
    }

    public function test_add_button_hidden_for_legacy_alias_module_names(): void
    {
        // "Kas Armada"/"Kas Operasional" are pre-split legacy names, not real modules — a role
        // granted `create` only under one of these must NOT see the button anymore.
        $this->actingAsStaffWithOnlyPermission('Kas Armada', ['view', 'create']);
        $this->get('/operationalCash')->assertStatus(200)->assertDontSee('btnAddCash', false);

        $this->actingAsStaffWithOnlyPermission('Kas Operasional', ['view', 'create']);
        $this->get('/operationalCash')->assertStatus(200)->assertDontSee('btnAddCash', false);
    }
}
