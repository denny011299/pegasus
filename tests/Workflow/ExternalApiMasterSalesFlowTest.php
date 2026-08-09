<?php

namespace Tests\Workflow;

use App\Models\Role;
use App\Models\Staff;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 sales CRUD/connect (#33, API-002 lanjutan) —
 * App\Http\Controllers\ExternalApi\V1\MasterSalesController. Shipped without any permanent test.
 *
 * The controller's own docblock spells out the trickiest contract in this whole batch: "sales"
 * is really `staffs` filtered to a role named like "sales"; POST body's `staff_id` and the PUT/
 * DELETE `{staff_id}` path segment are BOTH actually `staffs.external_ref_id`, never the real
 * `staffs.staff_id` — except on PATCH /connect, where `staff_id` flips back to meaning the real
 * internal id. This file exists specifically to pin that id-meaning switch down.
 */
class ExternalApiMasterSalesFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private function salesRoleId(): int
    {
        return (int) Role::where('role_name', 'like', '%sales%')->value('role_id');
    }

    private function createManagedSalesStaff(?string $externalRefId = null): Staff
    {
        $staff = new Staff();
        $staff->staff_name = 'External API Sales Fixture';
        $staff->staff_email = 'sales-fixture-'.uniqid().'@example.test';
        $staff->role_id = $this->salesRoleId();
        $staff->external_ref_id = $externalRefId;
        $staff->status = 1;
        $staff->save();

        return $staff;
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/master/sales', ['staff_id' => 1])
            ->assertStatus(401);
    }

    public function test_store_creates_a_new_staff_row_scoped_to_the_sales_role_without_login_credentials(): void
    {
        $headers = $this->externalApiHeaders();
        $refId = 'ext-'.uniqid();

        $response = $this->postJson('/api/external/v1/master/sales', [
            'staff_id' => $refId,
            'nama_depan' => 'Budi',
            'nama_belakang' => 'Santoso',
            'email' => 'budi-'.uniqid().'@example.test',
        ], $headers);

        $response->assertStatus(201)->assertJson(['success' => true, 'data' => ['staff_id' => $refId]]);

        $staff = Staff::where('external_ref_id', $refId)->firstOrFail();
        $this->assertSame($this->salesRoleId(), (int) $staff->role_id);
        $this->assertSame('Budi Santoso', $staff->staff_name);
        $this->assertNull($staff->staff_username, 'a POST-created sales row must not get login credentials');
        $this->assertNull($staff->staff_password);
    }

    public function test_store_rejects_a_duplicate_external_ref_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refId = 'ext-'.uniqid();
        $this->createManagedSalesStaff($refId);

        $response = $this->postJson('/api/external/v1/master/sales', [
            'staff_id' => $refId,
            'nama_depan' => 'Duplicate',
            'email' => 'dup-'.uniqid().'@example.test',
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'duplicate_ref_id']]);
    }

    public function test_update_and_delete_use_external_ref_id_in_the_path_not_the_internal_staff_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refId = 'ext-'.uniqid();
        $staff = $this->createManagedSalesStaff($refId);

        // The internal staff_id must NOT work as the path segment.
        $this->putJson('/api/external/v1/master/sales/'.$staff->staff_id, [
            'nama_depan' => 'Should Not Match',
            'email' => 'x@example.test',
        ], $headers)->assertStatus(404);

        $this->putJson('/api/external/v1/master/sales/'.$refId, [
            'nama_depan' => 'Updated',
            'nama_belakang' => 'Name',
            'email' => 'updated-'.uniqid().'@example.test',
        ], $headers)->assertStatus(200)->assertJson(['success' => true]);

        $this->assertSame('Updated Name', $staff->fresh()->staff_name);

        $this->deleteJson('/api/external/v1/master/sales/'.$refId, [], $headers)
            ->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(0, (int) $staff->fresh()->status, 'delete must soft-delete via Staff::deletestaff()');
    }

    public function test_a_staff_outside_the_sales_role_is_invisible_to_this_endpoint(): void
    {
        $headers = $this->externalApiHeaders();
        $nonSalesRoleId = (int) Role::where('role_name', 'not like', '%sales%')->value('role_id');
        $refId = 'ext-'.uniqid();

        $staff = new Staff();
        $staff->staff_name = 'Non Sales Staff';
        $staff->staff_email = 'nonsales-'.uniqid().'@example.test';
        $staff->role_id = $nonSalesRoleId;
        $staff->external_ref_id = $refId;
        $staff->status = 1;
        $staff->save();

        $this->putJson('/api/external/v1/master/sales/'.$refId, [
            'nama_depan' => 'x',
            'email' => 'x@example.test',
        ], $headers)->assertStatus(404);
    }

    public function test_connect_uses_the_internal_staff_id_and_moves_a_ref_id_held_by_another_staff(): void
    {
        $headers = $this->externalApiHeaders();
        $target = $this->createManagedSalesStaff(null);
        $mapId = 'ext-'.uniqid();
        $holder = $this->createManagedSalesStaff($mapId);

        $response = $this->patchJson('/api/external/v1/master/sales/connect', [
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
        $target = $this->createManagedSalesStaff(null);

        $response = $this->patchJson('/api/external/v1/master/sales/connect', [
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
