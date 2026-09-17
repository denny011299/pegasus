<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 Data Produk — App\Http\Controllers\ExternalApi\V1\MasterProductController.
 * Focused on the auto-sync layer: unit_id/product_unit/category_id may arrive as objects/names
 * for satuan/kategori PMO has never synced before, resolved via App\ExternalApi\Support\UnitAutoSync
 * / CategoryAutoSync — the same layered logic as PUT /master/units/{ref_unit_id} and
 * SyncUnitStep/SyncCategoryStep (Pusat Sinkronisasi > Sinkronisasi Produk).
 */
class ExternalApiMasterProductFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private function createUnit(?int $refUnitId = null, ?string $name = null): Unit
    {
        $unit = new Unit();
        $unit->unit_name = $name ?? 'Produk Ext Unit '.uniqid();
        $unit->unit_short_name = 'PU-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    private function createCategory(?string $name = null): Category
    {
        $category = new Category();
        $category->category_name = $name ?? 'Produk Ext Category '.uniqid();
        $category->status = 1;
        $category->save();

        return $category;
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/produk', ['ref_product_id' => 1])->assertStatus(401);
    }

    public function test_store_accepts_plain_pegasus_ids_for_unit_and_category(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $category = $this->createCategory();
        $refProductId = random_int(900000, 999999);

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => $refProductId,
            'product_name' => 'Produk Plain Id',
            'category_id' => $category->category_id,
            'unit_id' => $unit->unit_id,
            'product_unit' => [$unit->unit_id],
        ], $headers);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => [
                'ref_product_id' => $refProductId,
                'category_id' => $category->category_id,
                'unit_id' => $unit->unit_id,
            ],
        ]);
    }

    public function test_store_rejects_a_plain_unit_id_that_does_not_exist(): void
    {
        $headers = $this->externalApiHeaders();
        $category = $this->createCategory();

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => random_int(900000, 999999),
            'product_name' => 'Produk Unit Salah',
            'category_id' => $category->category_id,
            'unit_id' => 999999999,
            'product_unit' => [999999999],
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_store_auto_syncs_an_unknown_unit_ref_by_creating_it(): void
    {
        $headers = $this->externalApiHeaders();
        $category = $this->createCategory();
        $refUnitId = random_int(900000, 999999);
        $refProductId = random_int(900000, 999999);

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => $refProductId,
            'product_name' => 'Produk Satuan Baru',
            'category_id' => $category->category_id,
            'unit_id' => ['ref_unit_id' => $refUnitId, 'unit_name' => 'Dus PMO', 'unit_short_name' => 'dus'],
            'product_unit' => [
                ['ref_unit_id' => $refUnitId, 'unit_name' => 'Dus PMO', 'unit_short_name' => 'dus'],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $unit = Unit::where('ref_unit_id', $refUnitId)->firstOrFail();
        $this->assertSame('Dus PMO', $unit->unit_name);
        $this->assertSame('dus', $unit->unit_short_name);
        $this->assertSame(1, (int) $unit->status);

        $product = Product::where('ref_product_id', $refProductId)->firstOrFail();
        $this->assertSame($unit->unit_id, $product->unit_id);
        $this->assertSame([$unit->unit_id], json_decode($product->product_unit, true) === null
            ? []
            : array_map('intval', json_decode($product->product_unit, true)));
    }

    public function test_store_auto_syncs_an_unknown_unit_ref_by_adopting_an_unlinked_unit_by_name(): void
    {
        $headers = $this->externalApiHeaders();
        $category = $this->createCategory();
        $name = 'Adoptable Unit '.uniqid();
        $local = $this->createUnit(null, $name);
        $refUnitId = random_int(900000, 999999);

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => random_int(900000, 999999),
            'product_name' => 'Produk Adopsi Satuan',
            'category_id' => $category->category_id,
            'unit_id' => ['ref_unit_id' => $refUnitId, 'unit_name' => $name],
            'product_unit' => [
                ['ref_unit_id' => $refUnitId, 'unit_name' => $name],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson(['success' => true, 'data' => ['unit_id' => $local->unit_id]]);

        $local->refresh();
        $this->assertSame($refUnitId, $local->ref_unit_id);

        // No second unit row created for this ref_unit_id.
        $this->assertSame(1, Unit::where('ref_unit_id', $refUnitId)->count());
    }

    public function test_store_rejects_an_ambiguous_unit_name_match(): void
    {
        $headers = $this->externalApiHeaders();
        $category = $this->createCategory();
        $name = 'Ambiguous Unit '.uniqid();
        $this->createUnit(null, $name);
        $this->createUnit(null, $name);

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => random_int(900000, 999999),
            'product_name' => 'Produk Ambigu',
            'category_id' => $category->category_id,
            'unit_id' => ['ref_unit_id' => random_int(900000, 999999), 'unit_name' => $name],
            'product_unit' => [
                ['ref_unit_id' => random_int(900000, 999999), 'unit_name' => $name],
            ],
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'AMBIGUOUS_NAME_MATCH']]);
    }

    public function test_store_accepts_category_name_instead_of_category_id_and_creates_it(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $categoryName = 'Kategori PMO Baru '.uniqid();

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => random_int(900000, 999999),
            'product_name' => 'Produk Kategori Baru',
            'category_name' => $categoryName,
            'unit_id' => $unit->unit_id,
            'product_unit' => [$unit->unit_id],
        ], $headers);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $category = Category::where('category_name', $categoryName)->firstOrFail();
        $response->assertJson(['data' => ['category_id' => $category->category_id]]);
    }

    public function test_store_resolves_category_name_to_an_existing_category_by_name(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $category = $this->createCategory();

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => random_int(900000, 999999),
            'product_name' => 'Produk Kategori Existing',
            'category_name' => $category->category_name,
            'unit_id' => $unit->unit_id,
            'product_unit' => [$unit->unit_id],
        ], $headers);

        $response->assertStatus(201)->assertJson(['success' => true, 'data' => ['category_id' => $category->category_id]]);
        $this->assertSame(1, Category::where('category_name', $category->category_name)->count());
    }

    public function test_store_rejects_an_ambiguous_category_name_match(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();
        $name = 'Ambiguous Category '.uniqid();
        $this->createCategory($name);
        $this->createCategory($name);

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => random_int(900000, 999999),
            'product_name' => 'Produk Kategori Ambigu',
            'category_name' => $name,
            'unit_id' => $unit->unit_id,
            'product_unit' => [$unit->unit_id],
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'AMBIGUOUS_NAME_MATCH']]);
    }

    public function test_store_requires_either_category_id_or_category_name(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit();

        $response = $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => random_int(900000, 999999),
            'product_name' => 'Produk Tanpa Kategori',
            'unit_id' => $unit->unit_id,
            'product_unit' => [$unit->unit_id],
        ], $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_put_upsert_auto_syncs_an_unknown_unit_ref_for_a_brand_new_product(): void
    {
        $headers = $this->externalApiHeaders();
        $category = $this->createCategory();
        $refUnitId = random_int(900000, 999999);
        $refProductId = random_int(900000, 999999);

        $response = $this->putJson('/api/external/v1/produk/'.$refProductId, [
            'product_name' => 'Produk via PUT',
            'category_id' => $category->category_id,
            'unit_id' => ['ref_unit_id' => $refUnitId, 'unit_name' => 'Krat PMO'],
            'product_unit' => [
                ['ref_unit_id' => $refUnitId, 'unit_name' => 'Krat PMO'],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $unit = Unit::where('ref_unit_id', $refUnitId)->firstOrFail();
        $this->assertSame('Krat PMO', $unit->unit_name);
    }

    public function test_put_upsert_on_an_existing_product_still_auto_syncs_a_newly_seen_unit(): void
    {
        $headers = $this->externalApiHeaders();
        $category = $this->createCategory();
        $unit = $this->createUnit();
        $refProductId = random_int(900000, 999999);

        $this->postJson('/api/external/v1/produk', [
            'ref_product_id' => $refProductId,
            'product_name' => 'Produk Awal',
            'category_id' => $category->category_id,
            'unit_id' => $unit->unit_id,
            'product_unit' => [$unit->unit_id],
        ], $headers)->assertStatus(201);

        $refUnitId = random_int(900000, 999999);

        $response = $this->putJson('/api/external/v1/produk/'.$refProductId, [
            'product_name' => 'Produk Diperbarui',
            'category_id' => $category->category_id,
            'unit_id' => ['ref_unit_id' => $refUnitId, 'unit_name' => 'Pallet PMO'],
            'product_unit' => [
                ['ref_unit_id' => $refUnitId, 'unit_name' => 'Pallet PMO'],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $newUnit = Unit::where('ref_unit_id', $refUnitId)->firstOrFail();
        $this->assertSame($newUnit->unit_id, Product::where('ref_product_id', $refProductId)->firstOrFail()->unit_id);
    }
}
