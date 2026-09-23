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

    /**
     * Approve a Pengiriman (sales_orders) row through BOTH stages of the QC -> Ops approval
     * (App\Support\ShipmentApproval, App\Http\Controllers\CustomerController::approveShipment())
     * as a single Direksi actor, which is allowed to stand in for either stage (in order) —
     * mirrors StockTransferApprovalPermissionTest's actingAsElevatedApprover() pattern rather than
     * relying on the -1 super-admin bypass, which does NOT apply to this actor-role gate (same as
     * StockTransferApproval — see its class docblock).
     *
     * Switches the acting staff via session (this call replaces whoever was previously acting()),
     * asserts both approvals succeed, and pins active_warehouse_id to the given warehouse (must be
     * the main warehouse — approval only happens there). Caller is responsible for switching back
     * to a different acting staff afterwards if the rest of the test needs one.
     */
    protected function approveShipmentTwoStage(int $soId, int $mainWarehouseId = 1): void
    {
        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view'], ['role_id' => \App\Support\RoleIds::DIREKSI]);
        $this->withActiveWarehouse($mainWarehouseId);

        $qc = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'qc']);
        $qc->assertStatus(200);
        if ((int) ($qc->json('status') ?? 0) !== 1) {
            throw new \RuntimeException('approveShipmentTwoStage: QC stage failed — '.$qc->json('message'));
        }

        $ops = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops']);
        $ops->assertStatus(200);
        if ((int) ($ops->json('status') ?? 0) !== 1) {
            throw new \RuntimeException('approveShipmentTwoStage: Ops stage failed — '.$ops->json('message'));
        }
    }

    /**
     * Reject a Pengiriman at the QC stage (App\Http\Controllers\CustomerController::
     * rejectShipment()) as a Direksi actor — see approveShipmentTwoStage()'s docblock for why a
     * real elevated role is used instead of the -1 super-admin bypass.
     */
    protected function rejectShipmentAtQcStage(int $soId, string $reason = 'Test rejection', int $mainWarehouseId = 1): void
    {
        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view'], ['role_id' => \App\Support\RoleIds::DIREKSI]);
        $this->withActiveWarehouse($mainWarehouseId);

        $response = $this->post('/rejectShipment', ['so_id' => $soId, 'type' => 'qc', 'reason' => $reason]);
        $response->assertStatus(200);
        if ((int) ($response->json('status') ?? 0) !== 1) {
            throw new \RuntimeException('rejectShipmentAtQcStage failed — '.$response->json('message'));
        }
    }
}
