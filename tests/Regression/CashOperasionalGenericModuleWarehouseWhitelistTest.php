<?php

namespace Tests\Regression;

use App\Models\Warehouse;
use App\Support\WarehouseMenuAccess;
use Tests\TestCase;

/**
 * GitHub #196 item 28 (follow-up): a role with `create` granted only via the GENERIC "Kas
 * Operasional" module (legacy data from before the Kas menu was split into per-type Admin/Gudang/
 * Armada/Sales submodules) was rejected by `check.access.any:...,create` on every insertCash*
 * route, EVEN with the specific sidebar item (e.g. "Kas Operasional Admin") whitelisted for the
 * active warehouse. Root cause: `WarehouseMenuAccess::allows()` gates every fallback module name
 * — including the generic "Kas Operasional"/"Kas Admin"/"Kas Gudang"/"Kas Armada"/"Kas Sales"
 * aliases — against the warehouse's `sidebar_menus` whitelist. Those aliases are NOT real sidebar
 * items (absent from public/assets/json/permission.json, so no warehouse form can ever whitelist
 * them), so `allows()` always returned false for them regardless of the warehouse — the loop in
 * checkAccessAny would skip them before RoleAccess::can() was ever consulted, aborting 403 even
 * though the role legitimately had `create`.
 *
 * Fix: added the 5 generic aliases to WarehouseMenuAccess::NON_SIDEBAR_MODULES, same treatment as
 * 'Safety Stock'/'Pengaturan'/'Profil' — access to a non-sidebar-item module name is decided by
 * RoleAccess alone, never gated by a warehouse's menu whitelist.
 */
class CashOperasionalGenericModuleWarehouseWhitelistTest extends TestCase
{
    public static function genericModuleProvider(): array
    {
        return [
            'Kas Operasional' => ['Kas Operasional'],
            'Kas Admin' => ['Kas Admin'],
            'Kas Gudang' => ['Kas Gudang'],
            'Kas Armada' => ['Kas Armada'],
            'Kas Sales' => ['Kas Sales'],
        ];
    }

    /**
     * @dataProvider genericModuleProvider
     */
    public function test_generic_kas_alias_bypasses_warehouse_whitelist(string $module): void
    {
        // A warehouse whose whitelist deliberately does NOT include the generic alias (only real
        // sidebar items would ever appear here in practice, e.g. "Kas Operasional Admin").
        $warehouse = new Warehouse();
        $warehouse->sidebar_menus = ['Kas Operasional Admin', 'Kas Operasional Gudang'];

        $this->assertTrue(
            WarehouseMenuAccess::allows($module, $warehouse),
            "\"$module\" is a permission-only fallback alias (not a real sidebar item), so it must never be blocked by a warehouse's menu whitelist."
        );
    }

    public function test_real_sidebar_item_still_gated_by_warehouse_whitelist(): void
    {
        $warehouse = new Warehouse();
        $warehouse->sidebar_menus = ['Kas Operasional Admin'];

        $this->assertTrue(WarehouseMenuAccess::allows('Kas Operasional Admin', $warehouse));
        $this->assertFalse(
            WarehouseMenuAccess::allows('Kas Operasional Gudang', $warehouse),
            'A real sidebar item not in the whitelist must stay blocked — the fix only exempts the generic aliases, not the actual per-type menus.'
        );
    }
}
