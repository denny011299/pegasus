<?php

namespace Tests\Support;

use App\Models\Warehouse;

/**
 * Several tests were written against the committed default seed's warehouse layout (id 1 = Gudang
 * Pusat/main, id 2 = Gudang Eceran Toko/retail — see e.g. StockTransferWorkflowTest's docblock).
 * That numbering isn't guaranteed by anything: swap in a different dataset (a real production
 * snapshot like `okeh8644`, see memory pegasus-testing-db-multiwarehouse-drift) and id 2 can be
 * whatever that data happens to have at that id — inactive, main-type, anything.
 *
 * `MAIN_WAREHOUSE_ID = 1` in those tests is comparatively safe: id 1 being the/a main warehouse
 * is a much shakier but so-far-holding coincidence across datasets. The retail id is not — it
 * genuinely needs to resolve to whatever active, non-main warehouse the LOADED data actually has.
 * Use this instead of a hardcoded retail id anywhere a test needs one that real validation (e.g.
 * `Rule::exists('warehouses', 'id')->where('status', 1)` on `gudang_id`) will actually accept.
 */
trait ResolvesTestWarehouses
{
    /**
     * $requiredSidebarMenu: a warehouse's `sidebar_menus` is a real, business-meaningful
     * per-warehouse module whitelist (App\Models\Warehouse::allowsSidebarMenu(), enforced by
     * checkAccess middleware via WarehouseMenuAccess::allows() — NOT bypassed for super admin,
     * unlike role permissions). Real retail warehouses legitimately differ on which modules they
     * expose (confirmed: one okeh8644 retail warehouse excludes "Stock Transfer", another includes
     * it) — the FIRST active retail warehouse is not guaranteed to allow whatever module the test
     * actually needs to drive. Pass the module name a route's `check.access:Module|ability`
     * middleware guards (e.g. 'Stock Transfer') to only consider warehouses that allow it; omit it
     * when the test doesn't call through checkAccess at all (e.g. hitting a model/support class
     * directly, or an External API route, which doesn't gate on this).
     */
    protected function resolveActiveRetailWarehouseId(?string $requiredSidebarMenu = null): int
    {
        $warehouses = Warehouse::query()
            ->where('status', 1)
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 0))
            ->orderBy('id')
            ->get();

        if ($requiredSidebarMenu !== null) {
            $warehouses = $warehouses->filter(fn ($w) => $w->allowsSidebarMenu($requiredSidebarMenu));
        }

        $id = $warehouses->first()?->id;

        if (! $id) {
            $this->fail(
                'No active retail (non-main) warehouse'
                .($requiredSidebarMenu !== null ? " allowing \"{$requiredSidebarMenu}\"" : '')
                .' exists in the loaded test data.'
            );
        }

        return (int) $id;
    }
}
