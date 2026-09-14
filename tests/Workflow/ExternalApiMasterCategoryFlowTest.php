<?php

namespace Tests\Workflow;

use App\Models\Category;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 GET /master/categories (GitHub #171) —
 * App\Http\Controllers\ExternalApi\V1\MasterDataController::categories(). Read-only: PMO has no
 * concept of Pegasus's internal category_id, this endpoint is how it resolves/caches the mapping
 * before calling POST/PUT /produk, same purpose GET /master/units serves for unit_id.
 */
class ExternalApiMasterCategoryFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->getJson('/api/external/v1/master/categories')->assertStatus(401);
    }

    public function test_index_returns_only_active_categories(): void
    {
        $active = new Category();
        $active->category_name = 'External API Category Fixture '.uniqid();
        $active->status = 1;
        $active->save();

        $inactive = new Category();
        $inactive->category_name = 'External API Inactive Category '.uniqid();
        $inactive->status = 0;
        $inactive->save();

        $response = $this->getJson('/api/external/v1/master/categories', $this->externalApiHeaders());

        $response->assertStatus(200)->assertJson(['success' => true]);

        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($active->category_id, $ids);
        $this->assertNotContains($inactive->category_id, $ids);

        $row = collect($response->json('data'))->firstWhere('id', $active->category_id);
        $this->assertSame($active->category_name, $row['nama']);
    }

    public function test_search_filters_by_category_name(): void
    {
        $category = new Category();
        $category->category_name = 'Unik Kategori Pencarian '.uniqid();
        $category->status = 1;
        $category->save();

        $response = $this->getJson(
            '/api/external/v1/master/categories?'.http_build_query(['search' => 'Unik Kategori Pencarian']),
            $this->externalApiHeaders(),
        );

        $response->assertStatus(200);
        $ids = array_column($response->json('data'), 'id');
        $this->assertSame([$category->category_id], $ids);
    }
}
