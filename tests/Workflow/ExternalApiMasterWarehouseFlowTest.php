<?php

namespace Tests\Workflow;

use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 gudang CRUD (#33, API-002 lanjutan) — App\Http\Controllers\ExternalApi\V1\
 * MasterWarehouseController. Shipped without any permanent test (verified manually via a
 * rolled-back DB transaction at the time per the commit message, but nothing committed).
 *
 * Covers: create/update reuse Warehouse::insertWarehouse()/updateWarehouse() (auto stock-row
 * generation, duplicate-name rejection), delete's stock-guard + force override, and the
 * tipe_id/tipe_nama upsert-against-warehouse_types behaviour documented at length on the
 * controller class (rename-in-place for an existing tipe_id, exact-id creation for a new one).
 */
class ExternalApiMasterWarehouseFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Gudang API Test '.uniqid(),
            'tipe_nama' => 'Tipe API Test '.uniqid(),
            'tipe_id' => random_int(90000, 99999),
            'alamat' => 'Jl. Test No. 1',
        ], $overrides);
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/master/warehouses', $this->payload())
            ->assertStatus(401)
            ->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
    }

    public function test_store_creates_a_warehouse_and_a_new_warehouse_type_with_the_exact_requested_id(): void
    {
        $headers = $this->externalApiHeaders();
        $tipeId = random_int(90000, 99999);

        $response = $this->postJson('/api/external/v1/master/warehouses', $this->payload(['tipe_id' => $tipeId]), $headers);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $gudangId = $response->json('data.gudang_id');

        $this->assertNotNull(WarehouseType::find($tipeId), 'warehouse_types row must be created with the EXACT requested id');
        $warehouse = Warehouse::findOrFail($gudangId);
        $this->assertSame($tipeId, (int) $warehouse->warehouse_type_id);
    }

    /**
     * upsertWarehouseType() always creates a brand-new tipe_id as non-main
     * (is_main_warehouse=0), so ProductStock::generateStocksForWarehouse() only auto-creates rows
     * for variants that already have a retail_unit set — and the committed seed snapshot has
     * none (see the pegasus-testing skill's own documented gotcha). Reusing the real seeded main
     * warehouse type (id=1, "Gudang Utama") is the only way to exercise the "main warehouse"
     * branch, which creates a row per product_unit regardless of retail_unit.
     */
    public function test_store_against_the_main_warehouse_type_auto_generates_zero_stock_rows(): void
    {
        $headers = $this->externalApiHeaders();

        $response = $this->postJson('/api/external/v1/master/warehouses', $this->payload([
            'tipe_id' => 1,
            'tipe_nama' => 'Gudang Utama',
        ]), $headers);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $gudangId = $response->json('data.gudang_id');

        $this->assertTrue(
            ProductStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $gudangId)->exists(),
            'insertWarehouse() must auto-generate 0-stock rows for a new main-type warehouse, same as the admin page'
        );
        $this->assertSame('Gudang Utama', WarehouseType::find(1)->warehouse_type_name, 'the shared type name must be unchanged (same name sent)');
    }

    public function test_store_rejects_a_duplicate_warehouse_name(): void
    {
        $headers = $this->externalApiHeaders();
        $payload = $this->payload();

        $this->postJson('/api/external/v1/master/warehouses', $payload, $headers)->assertStatus(201);

        $response = $this->postJson('/api/external/v1/master/warehouses', $this->payload(['nama' => $payload['nama']]), $headers);
        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'DUPLICATE_NAME']]);
    }

    public function test_store_with_an_existing_tipe_id_renames_that_type_for_every_warehouse_using_it(): void
    {
        $headers = $this->externalApiHeaders();

        $existingType = new WarehouseType();
        $existingType->warehouse_type_name = 'Old Type Name '.uniqid();
        $existingType->is_main_warehouse = 0;
        $existingType->status = 1;
        $existingType->save();

        $otherWarehouse = (new Warehouse())->insertWarehouse([
            'warehouse_name' => 'Other Warehouse Using Same Type '.uniqid(),
            'warehouse_type_id' => $existingType->id,
            'warehouse_address' => 'x',
        ]);

        $newName = 'Renamed Type '.uniqid();
        $this->postJson('/api/external/v1/master/warehouses', $this->payload([
            'tipe_id' => $existingType->id,
            'tipe_nama' => $newName,
        ]), $headers)->assertStatus(201);

        $this->assertSame($newName, $existingType->fresh()->warehouse_type_name);
        // Sanity: the OTHER warehouse's own row is untouched, only the shared type's name changed.
        $this->assertSame((int) $existingType->id, (int) Warehouse::find($otherWarehouse)->warehouse_type_id);
    }

    public function test_update_returns_not_found_for_a_nonexistent_gudang_id(): void
    {
        $headers = $this->externalApiHeaders();

        $this->putJson('/api/external/v1/master/warehouses/999999999', $this->payload(), $headers)
            ->assertStatus(404)
            ->assertJson(['success' => false, 'error' => ['code' => 'NOT_FOUND']]);
    }

    public function test_destroy_is_blocked_by_existing_stock_unless_forced(): void
    {
        $headers = $this->externalApiHeaders();
        // Main type (id=1) so insertWarehouse() actually generates stock rows to attach qty to
        // (see the previous test's docblock: a plain new type never gets retail-only rows).
        $create = $this->postJson('/api/external/v1/master/warehouses', $this->payload([
            'tipe_id' => 1,
            'tipe_nama' => 'Gudang Utama',
        ]), $headers);
        $gudangId = $create->json('data.gudang_id');

        ProductStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $gudangId)
            ->limit(1)
            ->update(['ps_stock' => 10]);

        $blocked = $this->deleteJson('/api/external/v1/master/warehouses/'.$gudangId, [], $headers);
        $blocked->assertStatus(409)->assertJson(['success' => false, 'error' => ['code' => 'WAREHOUSE_HAS_STOCK']]);
        $this->assertSame(1, (int) Warehouse::find($gudangId)->status, 'blocked delete must not soft-delete the warehouse');

        $forced = $this->deleteJson('/api/external/v1/master/warehouses/'.$gudangId, ['force' => 1], $headers);
        $forced->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(0, (int) Warehouse::find($gudangId)->status);
    }
}
