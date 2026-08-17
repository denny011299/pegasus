<?php

namespace Tests\Workflow;

use App\Models\Supplies;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 Data Bahan CRUD/connect (GitHub #58) —
 * App\Http\Controllers\ExternalApi\V1\MasterSuppliesController. Built alongside
 * POST /shipments/returns: item type=1 on that endpoint resolves item.ref_id through
 * supplies.ref_supplies_id, managed here — same relationship as Data Produk to /shipments/shipped's
 * variant_sku (see ExternalApiMasterProductFlowTest's sibling, if any) but supplies has no such
 * test yet, so this one covers the module end-to-end, mirroring
 * ExternalApiMasterUnitFlowTest/ExternalApiMasterWarehouseFlowTest's structure.
 */
class ExternalApiMasterSuppliesFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private function createUnit(): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Supplies Ext Unit '.uniqid();
        $unit->unit_short_name = 'SU-'.random_int(1000, 9999);
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    private function createManagedSupplies(?int $refSuppliesId, Unit $unit): Supplies
    {
        $supplies = new Supplies();
        $supplies->ref_supplies_id = $refSuppliesId;
        $supplies->supplies_name = 'Supplies Ext Fixture '.uniqid();
        $supplies->supplies_desc = 'Fixture';
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->supplies_unit = json_encode([(string) $unit->unit_id]);
        $supplies->supplies_alert = 0;
        $supplies->status = 1;
        $supplies->save();

        return $supplies;
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/bahan', ['ref_supplies_id' => 1])
            ->assertStatus(401);
    }

    public function test_store_creates_a_supplies(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $refSuppliesId = random_int(900000, 999999);

        $response = $this->postJson('/api/external/v1/bahan', [
            'ref_supplies_id' => $refSuppliesId,
            'supplies_name' => 'Aki Zuur Test',
            'supplies_desc' => 'Cairan pengisi aki',
            'supplies_default_unit' => $unit->unit_id,
            'supplies_unit' => [$unit->unit_id],
        ], $headers);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => [
                'ref_supplies_id' => $refSuppliesId,
                'supplies_name' => 'Aki Zuur Test',
                'supplies_default_unit' => $unit->unit_id,
            ],
        ]);
        $this->assertDatabaseHas('supplies', ['ref_supplies_id' => $refSuppliesId, 'supplies_name' => 'Aki Zuur Test']);
    }

    public function test_store_rejects_a_duplicate_ref_supplies_id(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $refSuppliesId = random_int(900000, 999999);
        $this->createManagedSupplies($refSuppliesId, $unit);

        $this->postJson('/api/external/v1/bahan', [
            'ref_supplies_id' => $refSuppliesId,
            'supplies_name' => 'Dup',
            'supplies_default_unit' => $unit->unit_id,
            'supplies_unit' => [$unit->unit_id],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'DUPLICATE_REF_ID']]);
    }

    public function test_store_rejects_an_inactive_unit(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $unit->status = 0;
        $unit->save();

        $this->postJson('/api/external/v1/bahan', [
            'ref_supplies_id' => random_int(900000, 999999),
            'supplies_name' => 'Bad Unit',
            'supplies_default_unit' => $unit->unit_id,
            'supplies_unit' => [$unit->unit_id],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_update_never_creates_a_new_supplies_for_an_unknown_ref_supplies_id(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();

        $this->putJson('/api/external/v1/bahan/999999999', [
            'supplies_name' => 'x',
            'supplies_default_unit' => $unit->unit_id,
            'supplies_unit' => [$unit->unit_id],
        ], $headers)->assertStatus(404)->assertJson(['success' => false, 'error' => ['code' => 'NOT_FOUND']]);
    }

    public function test_update_and_delete_use_ref_supplies_id_not_the_internal_id(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $refSuppliesId = random_int(900000, 999999);
        $supplies = $this->createManagedSupplies($refSuppliesId, $unit);

        // Internal supplies_id must not work as the path segment.
        $this->putJson('/api/external/v1/bahan/'.$supplies->supplies_id, [
            'supplies_name' => 'wrong path',
            'supplies_default_unit' => $unit->unit_id,
            'supplies_unit' => [$unit->unit_id],
        ], $headers)->assertStatus(404);

        $this->putJson('/api/external/v1/bahan/'.$refSuppliesId, [
            'supplies_name' => 'Renamed',
            'supplies_default_unit' => $unit->unit_id,
            'supplies_unit' => [$unit->unit_id],
        ], $headers)->assertStatus(200)->assertJson(['success' => true]);

        $this->assertSame('Renamed', $supplies->fresh()->supplies_name);

        $this->deleteJson('/api/external/v1/bahan/'.$refSuppliesId, [], $headers)
            ->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(0, (int) $supplies->fresh()->status);
    }

    public function test_a_soft_deleted_supplies_cannot_be_reused_via_post(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $refSuppliesId = random_int(900000, 999999);
        $supplies = $this->createManagedSupplies($refSuppliesId, $unit);
        (new Supplies())->deleteSupplies(['supplies_id' => $supplies->supplies_id]);

        $response = $this->postJson('/api/external/v1/bahan', [
            'ref_supplies_id' => $refSuppliesId,
            'supplies_name' => 'Reuse Attempt',
            'supplies_default_unit' => $unit->unit_id,
            'supplies_unit' => [$unit->unit_id],
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'DUPLICATE_REF_ID']]);
    }

    public function test_connect_moves_a_ref_supplies_id_from_whichever_supplies_previously_held_it(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $target = $this->createManagedSupplies(null, $unit);
        $refSuppliesId = random_int(900000, 999999);
        $holder = $this->createManagedSupplies($refSuppliesId, $unit);

        $response = $this->patchJson('/api/external/v1/bahan/connect', [
            'connections' => [
                ['id' => $target->supplies_id, 'ref_supplies_id' => $refSuppliesId],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson(['success' => true, 'meta' => ['success' => 1, 'failed' => 0]]);
        $this->assertSame($refSuppliesId, $target->fresh()->ref_supplies_id);
        $this->assertNull($holder->fresh()->ref_supplies_id);
    }

    public function test_connect_rejects_an_inactive_supplies_without_failing_other_items_in_the_batch(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $inactive = $this->createManagedSupplies(null, $unit);
        $inactive->status = 0;
        $inactive->save();
        $active = $this->createManagedSupplies(null, $unit);

        $response = $this->patchJson('/api/external/v1/bahan/connect', [
            'connections' => [
                ['id' => $inactive->supplies_id, 'ref_supplies_id' => random_int(900000, 949999)],
                ['id' => $active->supplies_id, 'ref_supplies_id' => random_int(950000, 999999)],
            ],
        ], $headers);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.0.success'));
        $this->assertSame('NOT_FOUND', $response->json('data.0.error.code'));
        $this->assertTrue($response->json('data.1.success'));
    }

    public function test_index_lists_active_supplies_with_units_when_asked(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $refSuppliesId = random_int(900000, 999999);
        $this->createManagedSupplies($refSuppliesId, $unit);

        $response = $this->getJson('/api/external/v1/bahan?show_units=true', $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $rows = collect($response->json('data'));
        $row = $rows->firstWhere('ref_supplies_id', $refSuppliesId);
        $this->assertNotNull($row);
        $this->assertSame($unit->unit_id, $row['units'][0]['id']);
    }
}
