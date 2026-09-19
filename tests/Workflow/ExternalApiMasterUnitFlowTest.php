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

    public function test_update_creates_a_new_unit_for_an_unknown_ref_unit_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);

        $this->putJson('/api/external/v1/master/units/'.$refUnitId, [
            'unit_name' => 'x',
            'unit_short_name' => 'x',
        ], $headers)->assertStatus(201)->assertJson(['success' => true, 'data' => ['ref_unit_id' => $refUnitId]]);

        $this->assertDatabaseHas('units', ['ref_unit_id' => $refUnitId, 'unit_name' => 'x', 'status' => 1]);
    }

    public function test_update_reactivates_a_soft_deleted_unit(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createManagedUnit($refUnitId);
        (new Unit())->deleteUnit(['unit_id' => $unit->unit_id]);

        $this->putJson('/api/external/v1/master/units/'.$refUnitId, [
            'unit_name' => 'Revived',
            'unit_short_name' => 'RV',
        ], $headers)->assertStatus(200)->assertJson(['success' => true]);

        $unit->refresh();
        $this->assertSame(1, (int) $unit->status);
        $this->assertSame('Revived', $unit->unit_name);
    }

    public function test_update_adopts_an_unlinked_unit_by_name_when_ref_unit_id_is_unknown(): void
    {
        $headers = $this->externalApiHeaders();
        $name = 'Adoptable Unit '.uniqid();
        $local = $this->createManagedUnit(null);
        $local->unit_name = $name;
        $local->save();
        $refUnitId = random_int(900000, 999999);

        // Same layered lookup as SyncUnitStep: unknown ref_unit_id first tries a name
        // match against units that have no ref_unit_id yet, before creating a new row.
        $this->putJson('/api/external/v1/master/units/'.$refUnitId, [
            'unit_name' => $name,
            'unit_short_name' => 'ADP',
        ], $headers)->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['id' => $local->unit_id, 'ref_unit_id' => $refUnitId, 'unit_short_name' => 'ADP'],
        ]);

        $local->refresh();
        $this->assertSame($refUnitId, $local->ref_unit_id);
        $this->assertSame('ADP', $local->unit_short_name);
        $this->assertSame(1, (int) $local->status);

        // No second unit row was created for this ref_unit_id.
        $this->assertSame(1, Unit::where('ref_unit_id', $refUnitId)->count());
    }

    public function test_update_rejects_an_ambiguous_name_match_when_ref_unit_id_is_unknown(): void
    {
        $headers = $this->externalApiHeaders();
        $name = 'Ambiguous Unit '.uniqid();
        $first = $this->createManagedUnit(null);
        $first->unit_name = $name;
        $first->save();
        $second = $this->createManagedUnit(null);
        $second->unit_name = $name;
        $second->save();
        $refUnitId = random_int(900000, 999999);

        $response = $this->putJson('/api/external/v1/master/units/'.$refUnitId, [
            'unit_name' => $name,
            'unit_short_name' => 'AMB',
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'AMBIGUOUS_NAME_MATCH']]);
        $this->assertNull($first->fresh()->ref_unit_id);
        $this->assertNull($second->fresh()->ref_unit_id);
        $this->assertDatabaseMissing('units', ['ref_unit_id' => $refUnitId]);
    }

    public function test_update_does_not_adopt_a_unit_whose_ref_unit_id_is_already_taken(): void
    {
        $headers = $this->externalApiHeaders();
        $name = 'Already Linked Unit '.uniqid();
        $alreadyLinked = $this->createManagedUnit(random_int(100000, 199999));
        $alreadyLinked->unit_name = $name;
        $alreadyLinked->save();
        $newRefUnitId = random_int(900000, 999999);

        // $alreadyLinked already has a different ref_unit_id, so it is not adoptable —
        // a brand new unit must be created instead of touching $alreadyLinked's ref.
        $this->putJson('/api/external/v1/master/units/'.$newRefUnitId, [
            'unit_name' => $name,
            'unit_short_name' => 'NEW',
        ], $headers)->assertStatus(201);

        $this->assertNotSame($newRefUnitId, $alreadyLinked->fresh()->ref_unit_id);
        $this->assertDatabaseHas('units', ['ref_unit_id' => $newRefUnitId, 'unit_name' => $name]);
    }

    public function test_update_and_delete_use_ref_unit_id_not_the_internal_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createManagedUnit($refUnitId);

        // Internal unit_id must not work as the path segment: it does not match any
        // ref_unit_id, so upsert creates a brand new unit instead of touching $unit.
        $this->putJson('/api/external/v1/master/units/'.$unit->unit_id, [
            'unit_name' => 'wrong path',
            'unit_short_name' => 'x',
        ], $headers)->assertStatus(201);

        $this->assertNotSame('wrong path', $unit->fresh()->unit_name);

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
