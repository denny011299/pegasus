<?php

namespace Tests\Workflow;

use App\Models\Warehouse;
use App\Models\WarehouseType;
use App\Support\WarehouseMenuAccess;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * The fase-2 per-warehouse module whitelist (`warehouses.sidebar_menus`,
 * enforced by App\Support\WarehouseMenuAccess through the checkAccess /
 * checkAccessAny middleware).
 *
 * This is a SECOND, independent gate stacked on top of role permissions, and
 * the ordering in checkAccess::handle() is what makes it worth its own test:
 *
 *     RoleAccess::can(...)        -> super admin (role_id -1) bypasses this
 *     WarehouseMenuAccess::allows(...) -> super admin does NOT bypass this
 *
 * So a super admin working out of a retail warehouse really is blocked from
 * modules that warehouse does not expose. Every other permission test in this
 * suite uses super admin precisely because it bypasses gating — none of them
 * can see this gate, which is why it needs covering here explicitly.
 *
 * The gate is also conditional on there BEING an active warehouse: with no
 * `active_warehouse_id` in session, allows() short-circuits to true. That is
 * what keeps the Smoke suite green without any warehouse setup, and it is
 * pinned below so the short-circuit does not silently widen.
 */
class WarehouseSidebarMenuAccessTest extends TestCase
{
    use ActingAsStaff;

    private function makeWarehouse(bool $isMain, ?array $sidebarMenus): Warehouse
    {
        $type = WarehouseType::create([
            'warehouse_type_name' => ($isMain ? 'Utama' : 'Eceran').' Menu '.uniqid(),
            'is_main_warehouse' => $isMain ? 1 : 0,
            'status' => 1,
        ]);

        $w = new Warehouse();
        $w->warehouse_name = 'Gudang Menu '.uniqid();
        $w->warehouse_type_id = $type->id;
        $w->sidebar_menus = $sidebarMenus;
        $w->status = 1;
        $w->save();

        return $w->fresh();
    }

    // ------------------------------------------------- the whitelist itself

    /**
     * A null/empty whitelist means "no restriction", not "nothing allowed" —
     * the inverse would lock every existing warehouse out of every module the
     * moment the column was added.
     */
    public function test_a_warehouse_with_no_whitelist_allows_every_module(): void
    {
        $w = $this->makeWarehouse(isMain: false, sidebarMenus: null);

        $this->assertTrue($w->allowsSidebarMenu('Stock Transfer'));
        $this->assertTrue($w->allowsSidebarMenu('Pengiriman'));
        $this->assertTrue($w->allowsSidebarMenu('Modul Yang Belum Ada'));
    }

    public function test_a_whitelist_allows_only_what_it_lists(): void
    {
        $w = $this->makeWarehouse(isMain: false, sidebarMenus: ['Stock Transfer', 'Pengiriman']);

        $this->assertTrue($w->allowsSidebarMenu('Stock Transfer'));
        $this->assertTrue($w->allowsSidebarMenu('Pengiriman'));
        $this->assertFalse($w->allowsSidebarMenu('Pembelian'));
    }

    public function test_whitelist_matching_ignores_case_and_surrounding_whitespace(): void
    {
        $w = $this->makeWarehouse(isMain: false, sidebarMenus: ['  Stock Transfer  ']);

        $this->assertTrue($w->allowsSidebarMenu('stock transfer'));
        $this->assertTrue($w->allowsSidebarMenu('STOCK TRANSFER'));
        $this->assertTrue($w->allowsSidebarMenu(' Stock Transfer'));
    }

    /**
     * An all-blank whitelist normalizes back to null ("unrestricted") rather
     * than to an empty list ("deny everything") — a warehouse saved with junk
     * entries stays usable instead of bricking itself.
     */
    public function test_a_whitelist_of_only_blank_entries_normalizes_to_unrestricted(): void
    {
        $w = $this->makeWarehouse(isMain: false, sidebarMenus: ['', '   ']);

        $this->assertTrue($w->allowsSidebarMenu('Pembelian'));
    }

    // ------------------------------------------------------ main-only menus

    /**
     * Produksi is main-warehouse-only regardless of the whitelist: listing it
     * on a retail warehouse must not grant it.
     */
    public function test_produksi_stays_blocked_on_a_retail_warehouse_even_if_whitelisted(): void
    {
        $retail = $this->makeWarehouse(isMain: false, sidebarMenus: ['Produksi', 'Stock Transfer']);

        $this->assertFalse(WarehouseMenuAccess::allows('Produksi', $retail));
        $this->assertTrue(WarehouseMenuAccess::allows('Stock Transfer', $retail));
    }

    public function test_produksi_is_allowed_on_a_main_warehouse_that_whitelists_it(): void
    {
        $main = $this->makeWarehouse(isMain: true, sidebarMenus: ['Produksi']);

        $this->assertTrue(WarehouseMenuAccess::allows('Produksi', $main));
    }

    /**
     * Modules that are not sidebar items at all can never be whitelisted from
     * the Gudang form, so the warehouse gate must not be what decides them —
     * otherwise they would be permanently unreachable once any whitelist is set.
     */
    public function test_a_non_sidebar_module_is_never_blocked_by_a_whitelist(): void
    {
        $retail = $this->makeWarehouse(isMain: false, sidebarMenus: ['Stock Transfer']);

        $this->assertTrue(WarehouseMenuAccess::allows('Safety Stock', $retail));
    }

    // --------------------------------------------- enforcement over real HTTP

    public function test_with_no_active_warehouse_the_gate_short_circuits_open(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->assertTrue(WarehouseMenuAccess::allows('Pembelian'));
        $this->get('/purchaseOrder')->assertStatus(200);
    }

    /**
     * The headline case: role permissions say yes (super admin), the warehouse
     * whitelist says no, and the request is refused. If this ever returns 200,
     * the per-warehouse gate has stopped being enforced for admins.
     */
    public function test_an_active_warehouse_whitelist_blocks_even_a_super_admin(): void
    {
        $retail = $this->makeWarehouse(isMain: false, sidebarMenus: ['Stock Transfer']);

        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse((int) $retail->id);

        $this->get('/purchaseOrder')->assertStatus(403);
    }

    public function test_a_whitelisted_module_still_loads_under_the_same_active_warehouse(): void
    {
        $retail = $this->makeWarehouse(isMain: false, sidebarMenus: ['Stock Transfer']);

        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse((int) $retail->id);

        $this->get('/stockTransfer')->assertStatus(200);
    }

    /**
     * Both gates must pass, in the order checkAccess applies them: having the
     * role permission is not enough when the warehouse excludes the module.
     */
    public function test_role_permission_alone_is_not_enough_against_the_warehouse_gate(): void
    {
        $retail = $this->makeWarehouse(isMain: false, sidebarMenus: ['Stock Transfer']);

        $this->actingAsStaffWithOnlyPermission('Pembelian');
        $this->withActiveWarehouse((int) $retail->id);

        $this->get('/purchaseOrder')->assertStatus(403);
    }

    /**
     * ...and the warehouse gate is not enough on its own either — it never
     * grants access the role does not already have.
     */
    public function test_the_warehouse_gate_does_not_grant_access_the_role_lacks(): void
    {
        $retail = $this->makeWarehouse(isMain: false, sidebarMenus: ['Pembelian']);

        $this->actingAsStaffWithNoAccess();
        $this->withActiveWarehouse((int) $retail->id);

        $this->get('/purchaseOrder')->assertStatus(403);
    }

    public function test_an_unrestricted_active_warehouse_leaves_role_permissions_in_charge(): void
    {
        $main = $this->makeWarehouse(isMain: true, sidebarMenus: null);

        $this->actingAsStaffWithOnlyPermission('Pembelian');
        $this->withActiveWarehouse((int) $main->id);

        $this->get('/purchaseOrder')->assertStatus(200);
    }
}
