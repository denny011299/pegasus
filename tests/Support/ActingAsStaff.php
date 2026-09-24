<?php

namespace Tests\Support;

use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffWarehouse;

/**
 * This app authenticates via Session::get('user') (a Staff row with
 * role_name/role_access attached by Staff::getStaff()), not Laravel's
 * Auth guard — so $this->actingAs() doesn't apply here. Use these helpers
 * instead.
 */
trait ActingAsStaff
{
    protected function actingAsSuperAdminStaff(array $overrides = []): object
    {
        $staff = Staff::query()->where('status', 1)->orderBy('staff_id')->first();

        if (!$staff) {
            $staff = new Staff();
            $staff->staff_id = 999999;
            $staff->staff_name = 'Test Super Admin';
            $staff->status = 1;
        }

        $staff->role_id = -1;
        $staff->role_name = 'Super Admin';
        $staff->role_access = '[]';

        foreach ($overrides as $key => $value) {
            $staff->{$key} = $value;
        }

        session(['user' => $staff]);

        return $staff;
    }

    protected function actingAsStaffWithRole(int $roleId, array $overrides = []): object
    {
        $rows = (new Staff())->getStaff(['role_id' => $roleId]);
        $staff = $rows[0] ?? null;

        if (!$staff) {
            $staff = Staff::query()->where('status', 1)->orderBy('staff_id')->first() ?? new Staff();
            $staff->role_id = $roleId;
            $role = Role::find($roleId);
            $staff->role_name = $role->role_name ?? null;
            $staff->role_access = $role->role_access ?? '[]';
        }

        foreach ($overrides as $key => $value) {
            $staff->{$key} = $value;
        }

        session(['user' => $staff]);

        return $staff;
    }

    /**
     * A logged-in staff with role_id != -1 and an empty role_access — fails
     * every RoleAccess::can() check. Use to prove a route's check.access
     * middleware actually blocks (not just that a guest gets redirected).
     */
    protected function actingAsStaffWithNoAccess(array $overrides = []): object
    {
        $staff = Staff::query()->where('status', 1)->orderBy('staff_id')->first() ?? new Staff();
        $staff->role_id = 0;
        $staff->role_name = 'No Access (test)';
        $staff->role_access = '[]';

        foreach ($overrides as $key => $value) {
            $staff->{$key} = $value;
        }

        session(['user' => $staff]);

        return $staff;
    }

    /**
     * A logged-in staff granted exactly one module/ability pair — proves a
     * route's check.access module string matches what the test expects
     * (catches a typo/mismatch that a super-admin check can't, since
     * role_id -1 bypasses the module-name check entirely).
     */
    protected function actingAsStaffWithOnlyPermission(string $module, array $abilities = ['view'], array $overrides = []): object
    {
        $staff = Staff::query()->where('status', 1)->orderBy('staff_id')->first() ?? new Staff();
        $staff->role_id = 0;
        $staff->role_name = 'Scoped (test)';
        $staff->role_access = json_encode([
            ['name' => $module, 'akses' => $abilities],
        ]);

        foreach ($overrides as $key => $value) {
            $staff->{$key} = $value;
        }

        session(['user' => $staff]);

        return $staff;
    }

    /**
     * ProductStock's "active_warehouse" global scope resolves explicit >
     * session('active_warehouse_id') > main warehouse > first active, so
     * this is only needed when a test must pin a specific non-default
     * warehouse.
     */
    protected function withActiveWarehouse(int $warehouseId): static
    {
        session(['active_warehouse_id' => $warehouseId]);

        return $this;
    }

    /**
     * Assign the acting staff to one or more warehouses (`staff_warehouses`), which is what
     * Staff::assignedWarehouseIds() reads.
     *
     * Needed against real, non-empty data: several access checks
     * (StockTransferController::assertCanAcc()/assertCanEditSource(),
     * SalesOrderStock::validateRetailSelection()) fail CLOSED — "Anda tidak punya akses ke gudang
     * tujuan transfer ini" / "Gudang eceran yang dipilih tidak termasuk gudang Anda" — for a staff
     * that HAS assignments not covering the warehouse in question. Against the old near-empty
     * default seed the acting staff typically had no assignments at all, which those same checks
     * treat as "unrestricted" and let through — so this was never needed until testing against
     * real data (see memory pegasus-testing-db-multiwarehouse-drift). A test that drives a flow
     * through one of those checks, or that creates its own destination warehouse on the fly (e.g.
     * a fresh main warehouse for a multi-main-warehouse scenario), needs this.
     *
     * Tests\TestCase uses DatabaseTransactions, so the rows are rolled back per test.
     */
    protected function assignWarehousesToActingStaff(int ...$warehouseIds): static
    {
        $staff = session('user');
        if (! $staff || empty($staff->staff_id)) {
            return $this;
        }

        foreach ($warehouseIds as $warehouseId) {
            $exists = StaffWarehouse::query()
                ->where('staff_id', (int) $staff->staff_id)
                ->where('warehouse_id', $warehouseId)
                ->exists();

            if ($exists) {
                continue;
            }

            $row = new StaffWarehouse();
            $row->staff_id = (int) $staff->staff_id;
            $row->warehouse_id = $warehouseId;
            $row->save();
        }

        return $this;
    }
}
