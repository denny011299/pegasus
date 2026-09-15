<?php

namespace Tests\Workflow;

use App\Models\Role;
use App\Models\Staff;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 non-Sales staff CRUD/connect (GitHub #177) —
 * App\Http\Controllers\ExternalApi\V1\MasterStaffController.
 *
 * Mirrors ExternalApiMasterSalesFlowTest's coverage, but the managed role
 * comes from config('externalapi.staff_sync_roles') (exact match, not
 * "%like%") instead of a hard-coded "sales" keyword. The exact role list is
 * still awaiting PM confirmation (see GitHub #177) — this test only relies
 * on "Owner" being in that list, which is already agreed.
 *
 * Unlike sales, `role` is never a request field here — rows created via
 * this endpoint never get login credentials, so letting the caller pick a
 * role/permission set would be meaningless (and needless risk). role_id is
 * resolved server-side from staff_sync_roles, same as
 * MasterSalesController::salesRoleId().
 */
class ExternalApiMasterStaffFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerRoleId();
    }

    private function ownerRoleId(): int
    {
        $role = Role::whereRaw('LOWER(role_name) = ?', ['owner'])->first();

        if ($role === null) {
            $role = new Role();
            $role->role_name = 'Owner';
            $role->role_access = '{}';
            $role->status = 1;
            $role->save();
        }

        return (int) $role->role_id;
    }

    private function createManagedStaff(?string $externalRefId = null): Staff
    {
        $staff = new Staff();
        $staff->staff_name = 'External API Staff Fixture';
        $staff->staff_email = 'staff-fixture-'.uniqid().'@example.test';
        $staff->role_id = $this->ownerRoleId();
        $staff->external_ref_id = $externalRefId;
        $staff->status = 1;
        $staff->save();

        return $staff;
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/master/staff', ['staff_id' => 1])
            ->assertStatus(401);
    }

    public function test_store_creates_a_new_staff_row_scoped_to_the_configured_role_without_login_credentials(): void
    {
        $headers = $this->externalApiHeaders();
        $refId = 'ext-'.uniqid();

        $response = $this->postJson('/api/external/v1/master/staff', [
            'staff_id' => $refId,
            'nama_depan' => 'Budi',
            'nama_belakang' => 'Santoso',
            'email' => 'budi-'.uniqid().'@example.test',
        ], $headers);

        $response->assertStatus(201)->assertJson(['success' => true, 'data' => ['staff_id' => $refId]]);

        $staff = Staff::where('external_ref_id', $refId)->firstOrFail();
        $this->assertSame($this->ownerRoleId(), (int) $staff->role_id);
        $this->assertSame('Budi Santoso', $staff->staff_name);
        $this->assertNull($staff->staff_username, 'a POST-created row must not get login credentials');
        $this->assertNull($staff->staff_password);
    }

    public function test_store_ignores_a_role_field_sent_in_the_body(): void
    {
        $headers = $this->externalApiHeaders();
        $refId = 'ext-'.uniqid();

        $response = $this->postJson('/api/external/v1/master/staff', [
            'staff_id' => $refId,
            'role' => 'Sales',
            'nama_depan' => 'Ignored Role',
        ], $headers);

        $response->assertStatus(201);

        $staff = Staff::where('external_ref_id', $refId)->firstOrFail();
        $this->assertSame($this->ownerRoleId(), (int) $staff->role_id, 'role must always resolve server-side, never from the request body');
    }

    public function test_index_does_not_expose_the_staff_role(): void
    {
        $headers = $this->externalApiHeaders();
        $refId = 'ext-'.uniqid();
        $this->createManagedStaff($refId);

        $response = $this->getJson('/api/external/v1/master/staff?search='.$refId, $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertArrayNotHasKey('role', $response->json('data.0'), 'role must never be exposed to external callers');
    }

    public function test_store_rejects_a_duplicate_external_ref_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refId = 'ext-'.uniqid();
        $this->createManagedStaff($refId);

        $response = $this->postJson('/api/external/v1/master/staff', [
            'staff_id' => $refId,
            'nama_depan' => 'Duplicate',
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'DUPLICATE_REF_ID']]);
    }

    public function test_update_and_delete_use_external_ref_id_in_the_path_not_the_internal_staff_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refId = 'ext-'.uniqid();
        $staff = $this->createManagedStaff($refId);

        $this->putJson('/api/external/v1/master/staff/'.$staff->staff_id, [
            'nama_depan' => 'Should Not Match',
        ], $headers)->assertStatus(404);

        $this->putJson('/api/external/v1/master/staff/'.$refId, [
            'nama_depan' => 'Updated',
            'nama_belakang' => 'Name',
            'email' => 'updated-'.uniqid().'@example.test',
        ], $headers)->assertStatus(200)->assertJson(['success' => true]);

        $this->assertSame('Updated Name', $staff->fresh()->staff_name);

        $this->deleteJson('/api/external/v1/master/staff/'.$refId, [], $headers)
            ->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(0, (int) $staff->fresh()->status, 'delete must soft-delete via Staff::deletestaff()');
    }

    public function test_a_staff_outside_the_configured_roles_is_invisible_to_this_endpoint(): void
    {
        $headers = $this->externalApiHeaders();
        $otherRoleId = (int) Role::whereRaw('LOWER(role_name) != ?', ['owner'])->value('role_id');
        $refId = 'ext-'.uniqid();

        $staff = new Staff();
        $staff->staff_name = 'Non Owner Staff';
        $staff->staff_email = 'nonowner-'.uniqid().'@example.test';
        $staff->role_id = $otherRoleId;
        $staff->external_ref_id = $refId;
        $staff->status = 1;
        $staff->save();

        $this->putJson('/api/external/v1/master/staff/'.$refId, [
            'nama_depan' => 'x',
        ], $headers)->assertStatus(404);
    }

    public function test_connect_uses_the_internal_staff_id_and_moves_a_ref_id_held_by_another_staff(): void
    {
        $headers = $this->externalApiHeaders();
        $target = $this->createManagedStaff(null);
        $mapId = 'ext-'.uniqid();
        $holder = $this->createManagedStaff($mapId);

        $response = $this->patchJson('/api/external/v1/master/staff/connect', [
            'connections' => [
                ['staff_id' => $target->staff_id, 'map_staff_id' => $mapId],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson(['success' => true, 'meta' => ['success' => 1, 'failed' => 0]]);
        $this->assertSame($mapId, $target->fresh()->external_ref_id);
        $this->assertNull($holder->fresh()->external_ref_id, 'the ref must be released from whoever held it before');
    }

    public function test_connect_reports_a_per_item_failure_without_failing_the_whole_batch(): void
    {
        $headers = $this->externalApiHeaders();
        $target = $this->createManagedStaff(null);

        $response = $this->patchJson('/api/external/v1/master/staff/connect', [
            'connections' => [
                ['staff_id' => 999999999, 'map_staff_id' => 'ghost'],
                ['staff_id' => $target->staff_id, 'map_staff_id' => 'ext-'.uniqid()],
            ],
        ], $headers);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('meta.success'));
        $this->assertSame(1, $response->json('meta.failed'));
        $this->assertFalse($response->json('data.0.success'));
        $this->assertTrue($response->json('data.1.success'));
    }
}
