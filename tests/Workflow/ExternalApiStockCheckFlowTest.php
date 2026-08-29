<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\Support\ResolvesTestWarehouses;
use Tests\TestCase;

/**
 * External API v1 stock check — App\Http\Controllers\ExternalApi\V1\StockController::check().
 *
 * Real warehouse ids from the committed seed snapshot: 1 = Gudang Pusat (main), 2 = Gudang
 * Eceran Toko (retail) — see SalesOrderRetailAndUnitConversionFlowTest's docblock. Units are
 * built fresh per test instead of reusing seeded ones, because this endpoint's unit_id is
 * looked up by units.ref_unit_id (an external reference), not the internal unit_id, and the
 * fixture needs full control over that column.
 */
class ExternalApiStockCheckFlowTest extends TestCase
{
    use ActingAsExternalApiClient;
    use ResolvesTestWarehouses;

    private const MAIN_WAREHOUSE_ID = 1;
    private const RETAIL_WAREHOUSE_ID = 2;

    private function createUnit(?int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Stock Check Unit '.uniqid();
        $unit->unit_short_name = 'SC-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    /** @return array{variant: ProductVariant, sku: string} */
    private function createProductFixture(Unit $unit): array
    {
        $category = new Category();
        $category->category_name = 'Stock Check Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Stock Check Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'SC-TEST-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Stock Check Test Variant';
        $variant->product_variant_sku = $sku;
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        return ['variant' => $variant, 'sku' => $sku];
    }

    private function createStock(ProductVariant $variant, int $warehouseId, int $unitId, int $qty): ProductStock
    {
        $ps = new ProductStock();
        $ps->product_id = $variant->product_id;
        $ps->product_variant_id = $variant->product_variant_id;
        $ps->unit_id = $unitId;
        $ps->warehouse_id = $warehouseId;
        $ps->ps_stock = $qty;
        $ps->status = 1;
        $ps->save();

        return $ps;
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-1',
            'items' => [],
        ])->assertStatus(401)->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
    }

    public function test_check_returns_zero_shortage_when_stock_is_sufficient(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], self::MAIN_WAREHOUSE_ID, $unit->unit_id, 10);

        $response = $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-SUFFICIENT',
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 5, 'unit_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => 'SHP-SUFFICIENT',
                'has_shortage' => false,
                'items' => [
                    ['sku' => $fx['sku'], 'unit_id' => $refUnitId, 'requested' => 5, 'available' => 10, 'shortage' => 0],
                ],
            ],
        ]);
    }

    public function test_check_reports_shortage_when_stock_is_insufficient(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], self::MAIN_WAREHOUSE_ID, $unit->unit_id, 10);

        $response = $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-SHORT',
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 20, 'unit_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'has_shortage' => true,
                'items' => [
                    ['sku' => $fx['sku'], 'unit_id' => $refUnitId, 'requested' => 20, 'available' => 10, 'shortage' => 10],
                ],
            ],
        ]);
    }

    public function test_check_unpacks_ancestor_unit_stock_into_the_requested_unit(): void
    {
        $headers = $this->externalApiHeaders();
        $bigRefUnitId = random_int(900000, 949999);
        $smallRefUnitId = random_int(950000, 999999);
        $bigUnit = $this->createUnit($bigRefUnitId);
        $smallUnit = $this->createUnit($smallRefUnitId);
        $fx = $this->createProductFixture($smallUnit);

        // 1 x bigUnit = 12 x smallUnit, scoped to this variant only.
        $relation = new ProductRelation();
        $relation->product_variant_id = $fx['variant']->product_variant_id;
        $relation->pr_unit_id_1 = $bigUnit->unit_id;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = $smallUnit->unit_id;
        $relation->pr_unit_value_2 = 12;
        $relation->status = 1;
        $relation->save();

        // Stock only exists in the bigger unit — none directly in smallUnit.
        $this->createStock($fx['variant'], self::MAIN_WAREHOUSE_ID, $bigUnit->unit_id, 3);

        $response = $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-UNPACK',
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 30, 'unit_id' => $smallRefUnitId],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'has_shortage' => false,
                'items' => [
                    // 3 big units x 12 = 36 available in the requested small unit.
                    ['sku' => $fx['sku'], 'unit_id' => $smallRefUnitId, 'requested' => 30, 'available' => 36, 'shortage' => 0],
                ],
            ],
        ]);
    }

    public function test_check_does_not_unpack_the_smaller_unit_upward(): void
    {
        $headers = $this->externalApiHeaders();
        $bigRefUnitId = random_int(900000, 949999);
        $smallRefUnitId = random_int(950000, 999999);
        $bigUnit = $this->createUnit($bigRefUnitId);
        $smallUnit = $this->createUnit($smallRefUnitId);
        $fx = $this->createProductFixture($bigUnit);

        $relation = new ProductRelation();
        $relation->product_variant_id = $fx['variant']->product_variant_id;
        $relation->pr_unit_id_1 = $bigUnit->unit_id;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = $smallUnit->unit_id;
        $relation->pr_unit_value_2 = 12;
        $relation->status = 1;
        $relation->save();

        // Plenty of stock in the SMALLER unit, none in the bigger one being requested.
        $this->createStock($fx['variant'], self::MAIN_WAREHOUSE_ID, $smallUnit->unit_id, 120);

        $response = $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-NO-PACK',
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $bigRefUnitId],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'has_shortage' => true,
                'items' => [
                    ['sku' => $fx['sku'], 'unit_id' => $bigRefUnitId, 'requested' => 1, 'available' => 0, 'shortage' => 1],
                ],
            ],
        ]);
    }

    public function test_check_rejects_an_unknown_sku(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $this->createUnit($refUnitId);

        $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-BAD-SKU',
            'items' => [
                ['sku' => 'DOES-NOT-EXIST-'.uniqid(), 'qty' => 1, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_check_rejects_an_unmapped_unit_id(): void
    {
        $headers = $this->externalApiHeaders();
        $unit = $this->createUnit(random_int(900000, 999999));
        $fx = $this->createProductFixture($unit);

        $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-BAD-UNIT',
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 1, 'unit_id' => random_int(100000000, 199999999)],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_check_defaults_to_the_main_warehouse_when_gudang_id_is_omitted(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], self::MAIN_WAREHOUSE_ID, $unit->unit_id, 7);
        $this->createStock($fx['variant'], self::RETAIL_WAREHOUSE_ID, $unit->unit_id, 999);

        $response = $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-DEFAULT-WH',
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(200);
        $this->assertSame(7, $response->json('data.items.0.available'), 'omitting gudang_id must resolve to the main warehouse, not the retail one');
    }

    public function test_check_uses_the_explicit_gudang_id_when_given(): void
    {
        // gudang_id is validated as a real, ACTIVE warehouse (Rule::exists(...)->where('status',1)
        // in StockController::check()) -- self::RETAIL_WAREHOUSE_ID is only valid against the
        // committed default seed's layout, not whatever real data is actually loaded. See
        // ResolvesTestWarehouses.
        $retailWarehouseId = $this->resolveActiveRetailWarehouseId();

        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], self::MAIN_WAREHOUSE_ID, $unit->unit_id, 7);
        $this->createStock($fx['variant'], $retailWarehouseId, $unit->unit_id, 42);

        $response = $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-EXPLICIT-WH',
            'gudang_id' => $retailWarehouseId,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(200);
        $this->assertSame(42, $response->json('data.items.0.available'));
    }

    public function test_check_rejects_an_unknown_gudang_id(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);

        $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-BAD-WH',
            'gudang_id' => 999999999,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_check_handles_multiple_items_independently(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitIdA = random_int(900000, 949999);
        $refUnitIdB = random_int(950000, 999999);
        $unitA = $this->createUnit($refUnitIdA);
        $unitB = $this->createUnit($refUnitIdB);
        $fxA = $this->createProductFixture($unitA);
        $fxB = $this->createProductFixture($unitB);
        $this->createStock($fxA['variant'], self::MAIN_WAREHOUSE_ID, $unitA->unit_id, 100);
        $this->createStock($fxB['variant'], self::MAIN_WAREHOUSE_ID, $unitB->unit_id, 2);

        $response = $this->postJson('/api/external/v1/stock/check', [
            'ref_shipment_id' => 'SHP-MULTI',
            'items' => [
                ['sku' => $fxA['sku'], 'qty' => 10, 'unit_id' => $refUnitIdA],
                ['sku' => $fxB['sku'], 'qty' => 10, 'unit_id' => $refUnitIdB],
            ],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'has_shortage' => true,
                'items' => [
                    ['sku' => $fxA['sku'], 'requested' => 10, 'available' => 100, 'shortage' => 0],
                    ['sku' => $fxB['sku'], 'requested' => 10, 'available' => 2, 'shortage' => 8],
                ],
            ],
        ]);
    }
}
