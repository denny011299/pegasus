<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 shipment detail lookup — App\Http\Controllers\ExternalApi\V1\ShipmentController::
 * show(). Real warehouse id from the committed seed snapshot: 1 = Gudang Pusat (main), see
 * SalesOrderRetailAndUnitConversionFlowTest's docblock.
 *
 * Fixtures go through the real /shipments/scheduled and /shipments/shipped endpoints rather than
 * poking SalesOrder/SalesOrderDetail directly, so these tests exercise the full read-after-write
 * path the way a real caller would.
 */
class ExternalApiShipmentShowFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const MAIN_WAREHOUSE_ID = 1;

    private array $writtenPhotoPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->writtenPhotoPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Show Test Armada';
        $customer->customer_code = 'SW'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createUnit(?int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Show Test Unit '.uniqid();
        $unit->unit_short_name = 'SW-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    /** @return array{variant: ProductVariant, sku: string} */
    private function createProductFixture(Unit $unit): array
    {
        $category = new Category();
        $category->category_name = 'Show Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Show Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'SHW-TEST-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Show Test Variant';
        $variant->product_variant_sku = $sku;
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        return ['variant' => $variant, 'sku' => $sku];
    }

    private function createStock(ProductVariant $variant, int $unitId, int $qty): void
    {
        ProductStock::withoutGlobalScope('active_warehouse')->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => $unitId,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
            'ps_stock' => $qty,
            'status' => 1,
        ]);
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->getJson('/api/external/v1/shipments/SHP-1')
            ->assertStatus(401)->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
    }

    public function test_show_returns_shipment_not_found_for_an_unknown_ref(): void
    {
        $headers = $this->externalApiHeaders();

        $response = $this->getJson('/api/external/v1/shipments/DOES-NOT-EXIST-'.uniqid(), $headers);

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'error' => ['code' => 'SHIPMENT_NOT_FOUND'],
        ]);
        $this->assertStringContainsString('tidak ditemukan', $response->json('error.message'));
    }

    public function test_show_returns_a_scheduled_shipments_details(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-23',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 24, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(201);

        $response = $this->getJson('/api/external/v1/shipments/'.$refShipmentId, $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $refShipmentId,
                'ipm_status' => 1,
                'ipm_status_label' => 'Dijadwalkan',
                'shipment_date' => '2026-07-23',
                'armada_code' => $armada->customer_code,
                'notes' => null,
                'photos' => [],
                'items' => [
                    [
                        'variant_sku' => $fx['sku'],
                        'qty' => 24,
                        'unit_id' => $refUnitId,
                        'product_name' => 'Show Test Product',
                    ],
                ],
            ],
        ]);
        $this->assertNotNull($response->json('data.shipment_internal_id'));
        $this->assertNotNull($response->json('data.created_at'));
        $this->assertArrayNotHasKey('unit', $response->json('data.items.0'));
    }

    public function test_show_returns_a_shipped_shipments_details_with_notes_and_photos(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->post('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-24',
            'armada_code' => $armada->customer_code,
            'notes' => 'Catatan pengiriman',
            'items' => [[
                'variant_sku' => $fx['sku'],
                'qty' => 24,
                'unit_id' => $refUnitId,
                'product_name' => 'AIR AKI HIKARI',
                'variant_name' => '20 x 400ml',
            ]],
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 10, 10)],
        ], array_merge($headers, ['Accept' => 'application/json']))->assertStatus(201);

        $response = $this->getJson('/api/external/v1/shipments/'.$refShipmentId, $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $refShipmentId,
                'ipm_status' => 2,
                'ipm_status_label' => 'Berjalan',
                'shipment_date' => '2026-07-24',
                'armada_code' => $armada->customer_code,
                'notes' => 'Catatan pengiriman',
                'items' => [
                    [
                        'variant_sku' => $fx['sku'],
                        'qty' => 24,
                        'unit_id' => $refUnitId,
                        'product_name' => 'AIR AKI HIKARI',
                        'variant_name' => '20 x 400ml',
                    ],
                ],
            ],
        ]);

        $photos = $response->json('data.photos');
        $this->assertCount(1, $photos);
        $this->assertStringContainsString('/issue/', $photos[0]);

        $fileName = basename(parse_url($photos[0], PHP_URL_PATH));
        $this->writtenPhotoPaths[] = public_path('issue/'.$fileName);
    }

    public function test_show_with_show_unit_adds_the_resolved_unit_object_per_item(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-23',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 24, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(201);

        $response = $this->getJson('/api/external/v1/shipments/'.$refShipmentId.'?show_unit=true', $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'items' => [
                    [
                        'unit' => [
                            'id' => $unit->unit_id,
                            'unit_name' => $unit->unit_name,
                            'unit_short_name' => $unit->unit_short_name,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
