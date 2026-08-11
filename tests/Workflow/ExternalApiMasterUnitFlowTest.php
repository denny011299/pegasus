<?php

namespace Tests\Workflow;

use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 satuan CRUD/connect (#33, API-001 lanjutan) —
 * App\Http\Controllers\ExternalApi\V1\MasterUnitController. Shipped without any permanent test.
 *
 * `units.ref_unit_id` is also written by the Sync Center (SyncUnitStep, pulling from PMO) — this
 * controller is a deliberate SECOND writer to the same column, confirmed intentional by the
 * product owner per the class docblock (last write wins, no extra locking). Not this file's job
 * to test that interaction, only this endpoint's own contract.
 */
class ExternalApiMasterUnitFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private function createManagedUnit(?int $refUnitId = null): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'External API Unit Fixture '.uniqid();
        $unit->unit_short_name = 'EXT';
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/master/units', ['ref_unit_id' => 1])
            ->assertStatus(401);
    }

    public function test_store_creates_a_unit(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);

        $response = $this->postJson('/api/external/v1/master/units', [
            'ref_unit_id' => $refUnitId,
            'unit_name' => 'Kilogram Test',
            'unit_short_name' => 'kg-test',
        ], $headers);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => ['ref_unit_id' => $refUnitId, 'unit_name' => 'Kilogram Test'],
        ]);
        $this->assertDatabaseHas('units', ['ref_unit_id' => $refUnitId, 'unit_short_name' => 'kg-test']);
    }

    public function test_store_rejects_a_duplicate_ref_unit_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $this->createManagedUnit($refUnitId);

        $this->postJson('/api/external/v1/master/units', [
            'ref_unit_id' => $refUnitId,
            'unit_name' => 'Dup',
            'unit_short_name' => 'dup',
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'DUPLICATE_REF_ID']]);
    }

    public function test_update_never_creates_a_new_unit_for_an_unknown_ref_unit_id(): void
    {
        $headers = $this->externalApiHeaders();

        $this->putJson('/api/external/v1/master/units/999999999', [
            'unit_name' => 'x',
            'unit_short_name' => 'x',
        ], $headers)->assertStatus(404)->assertJson(['success' => false, 'error' => ['code' => 'NOT_FOUND']]);
    }

    public function test_update_and_delete_use_ref_unit_id_not_the_internal_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createManagedUnit($refUnitId);

        // Internal unit_id must not work as the path segment.
        $this->putJson('/api/external/v1/master/units/'.$unit->unit_id, [
            'unit_name' => 'wrong path',
            'unit_short_name' => 'x',
        ], $headers)->assertStatus(404);

        $this->putJson('/api/external/v1/master/units/'.$refUnitId, [
            'unit_name' => 'Renamed',
            'unit_short_name' => 'RN',
        ], $headers)->assertStatus(200)->assertJson(['success' => true]);

        $this->assertSame('Renamed', $unit->fresh()->unit_name);

        $this->deleteJson('/api/external/v1/master/units/'.$refUnitId, [], $headers)
            ->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(0, (int) $unit->fresh()->status);
    }

    public function test_a_soft_deleted_unit_cannot_be_reused_via_post(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createManagedUnit($refUnitId);
        (new Unit())->deleteUnit(['unit_id' => $unit->unit_id]);

        $response = $this->postJson('/api/external/v1/master/units', [
            'ref_unit_id' => $refUnitId,
            'unit_name' => 'Reuse Attempt',
            'unit_short_name' => 'RA',
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'DUPLICATE_REF_ID']]);
    }

    public function test_connect_moves_a_ref_unit_id_from_whichever_unit_previously_held_it(): void
    {
        $headers = $this->externalApiHeaders();
        $target = $this->createManagedUnit(null);
        $refUnitId = random_int(900000, 999999);
        $holder = $this->createManagedUnit($refUnitId);

        $response = $this->patchJson('/api/external/v1/master/units/connect', [
            'connections' => [
                ['id' => $target->unit_id, 'ref_unit_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson(['success' => true, 'meta' => ['success' => 1, 'failed' => 0]]);
        $this->assertSame($refUnitId, $target->fresh()->ref_unit_id);
        $this->assertNull($holder->fresh()->ref_unit_id);
    }

    public function test_connect_rejects_an_inactive_unit_without_failing_other_items_in_the_batch(): void
    {
        $headers = $this->externalApiHeaders();
        $inactive = $this->createManagedUnit(null);
        $inactive->status = 0;
        $inactive->save();
        $active = $this->createManagedUnit(null);

        $response = $this->patchJson('/api/external/v1/master/units/connect', [
            'connections' => [
                ['id' => $inactive->unit_id, 'ref_unit_id' => random_int(900000, 999999)],
                ['id' => $active->unit_id, 'ref_unit_id' => random_int(900000, 999999)],
            ],
        ], $headers);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.0.success'));
        $this->assertSame('NOT_FOUND', $response->json('data.0.error.code'));
        $this->assertTrue($response->json('data.1.success'));
    }
}
