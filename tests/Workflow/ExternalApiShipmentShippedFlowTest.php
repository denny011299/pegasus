<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 shipment confirmation — App\Http\Controllers\ExternalApi\V1\ShipmentController::
 * shipped(). Real warehouse id from the committed seed snapshot: 1 = Gudang Pusat (main), see
 * SalesOrderRetailAndUnitConversionFlowTest's docblock.
 */
class ExternalApiShipmentShippedFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const MAIN_WAREHOUSE_ID = 1;

    /** sales_orders.status, lihat migrasi 2026_08_11_130000_* dan model SalesOrder. */
    private const STATUS_SCHEDULED = 4;

    private const STATUS_CONFIRMED = 2;

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
        $customer->customer_name = 'Shipped Test Armada';
        $customer->customer_code = 'SD'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createUnit(?int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Shipped Test Unit '.uniqid();
        $unit->unit_short_name = 'SU-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    /** @return array{variant: ProductVariant, sku: string} */
    private function createProductFixture(Unit $unit): array
    {
        $category = new Category();
        $category->category_name = 'Shipped Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Shipped Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'SHD-TEST-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Shipped Test Variant';
        $variant->product_variant_sku = $sku;
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        return ['variant' => $variant, 'sku' => $sku];
    }

    private function createStock(ProductVariant $variant, int $unitId, int $qty): ProductStock
    {
        return ProductStock::withoutGlobalScope('active_warehouse')->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => $unitId,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
            'ps_stock' => $qty,
            'status' => 1,
        ]);
    }

    private function itemPayload(string $sku, int $refUnitId, int $qty = 24): array
    {
        return [
            'variant_sku' => $sku,
            'qty' => $qty,
            'unit_id' => $refUnitId,
            'product_name' => 'AIR AKI HIKARI',
            'variant_name' => '20 x 400ml',
        ];
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => 'SHP-1',
            'shipment_date' => '2026-07-25',
            'armada_code' => 'X',
            'items' => [],
        ])->assertStatus(401)->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
    }

    public function test_shipped_creates_and_confirms_a_brand_new_shipment(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $response = $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'notes' => 'Pengiriman test',
            'items' => [$this->itemPayload($fx['sku'], $refUnitId)],
        ], $headers);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $refShipmentId,
                'ipm_status' => 2,
                'ipm_status_label' => 'Berjalan',
            ],
        ]);

        $soId = $response->json('data.shipment_internal_id');
        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(self::STATUS_CONFIRMED, (int) $so->status);
        $this->assertSame($refShipmentId, $so->ref_shipment_id);
        $this->assertSame((string) $armada->customer_id, $so->so_customer);
        $this->assertSame('2026-07-25', $so->so_date);
        $this->assertSame('Pengiriman test', $so->notes);

        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();
        $this->assertSame($fx['variant']->product_variant_id, $detail->product_variant_id);
        $this->assertSame($unit->unit_id, $detail->unit_id);
        $this->assertSame('AIR AKI HIKARI', $detail->sod_nama);
        $this->assertSame('20 x 400ml', $detail->sod_variant);
        $this->assertSame(24, (int) $detail->sod_qty);

        $stock->refresh();
        $this->assertSame(76, (int) $stock->ps_stock, 'stock must actually be deducted on shipped()');
    }

    public function test_shipped_confirms_an_existing_scheduled_shipment_with_matching_data(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $scheduleResponse = $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 24, 'unit_id' => $refUnitId],
            ],
        ], $headers);
        $scheduleResponse->assertStatus(201);
        $soId = $scheduleResponse->json('data.shipment_internal_id');

        $response = $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [$this->itemPayload($fx['sku'], $refUnitId)],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'shipment_internal_id' => $soId,
                'ipm_status' => 2,
                'ipm_status_label' => 'Berjalan',
            ],
        ]);

        $this->assertSame(self::STATUS_CONFIRMED, (int) SalesOrder::findOrFail($soId)->status);
        $stock->refresh();
        $this->assertSame(76, (int) $stock->ps_stock);
    }

    public function test_shipped_force_upserts_differing_details_before_confirming(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 10, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(201);

        // Qty berbeda dari yang tersimpan (10 -> 30) - detail_handler default "force".
        $response = $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [$this->itemPayload($fx['sku'], $refUnitId, qty: 30)],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ipm_status' => 2, 'ipm_status_label' => 'Berjalan'],
        ]);

        $soId = $response->json('data.shipment_internal_id');
        $detail = SalesOrderDetail::where('so_id', $soId)->where('status', 1)->firstOrFail();
        $this->assertSame(30, (int) $detail->sod_qty, 'force must overwrite the stored qty');

        $stock->refresh();
        $this->assertSame(70, (int) $stock->ps_stock, '100 - 30 (the forced qty, not the original 10)');
    }

    public function test_shipped_rejects_differing_details_when_detail_handler_is_validate(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 10, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(201);

        $response = $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'detail_handler' => 'validate',
            'items' => [$this->itemPayload($fx['sku'], $refUnitId, qty: 30)],
        ], $headers);

        $response->assertStatus(409)->assertJson([
            'success' => false,
            'error' => ['code' => 'SHIPMENT_DETAIL_MISMATCH'],
        ]);
        $this->assertContains('items', $response->json('error.details.mismatched_fields'));

        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->firstOrFail();
        $this->assertSame(self::STATUS_SCHEDULED, (int) $so->status, 'a rejected mismatch must not confirm the shipment');

        $detail = SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)->firstOrFail();
        $this->assertSame(10, (int) $detail->sod_qty, 'validate must never change stored data');

        $stock->refresh();
        $this->assertSame(100, (int) $stock->ps_stock, 'nothing should be deducted when the request is rejected');
    }

    public function test_shipped_with_validate_still_confirms_when_data_already_matches(): void
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
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 24, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(201);

        $response = $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'detail_handler' => 'validate',
            'items' => [$this->itemPayload($fx['sku'], $refUnitId, qty: 24)],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ipm_status' => 2],
        ]);
    }

    public function test_shipped_is_idempotent_and_does_not_deduct_stock_twice(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $payload = [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [$this->itemPayload($fx['sku'], $refUnitId)],
        ];

        $first = $this->postJson('/api/external/v1/shipments/shipped', $payload, $headers);
        $first->assertStatus(201);
        $soId = $first->json('data.shipment_internal_id');

        $second = $this->postJson('/api/external/v1/shipments/shipped', $payload, $headers);
        $second->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'shipment_internal_id' => $soId,
                'ipm_status' => 2,
                'ipm_status_label' => 'Berjalan',
            ],
            'meta' => ['idempotent_replay' => true],
        ]);

        $stock->refresh();
        $this->assertSame(76, (int) $stock->ps_stock, 'a replayed request must not deduct stock a second time');
        $this->assertSame(1, SalesOrder::where('ref_shipment_id', $refShipmentId)->count());
    }

    public function test_shipped_replay_ignores_a_different_payload_once_already_confirmed(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [$this->itemPayload($fx['sku'], $refUnitId, qty: 24)],
        ], $headers)->assertStatus(201);

        // Payload beda (qty 99, detail_handler validate) - tetap idempotent replay murni karena
        // sudah "Berjalan", TIDAK dibandingkan / ditolak.
        $response = $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'detail_handler' => 'validate',
            'items' => [$this->itemPayload($fx['sku'], $refUnitId, qty: 99)],
        ], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ipm_status' => 2],
            'meta' => ['idempotent_replay' => true],
        ]);

        $stock->refresh();
        $this->assertSame(76, (int) $stock->ps_stock);
        $detail = SalesOrderDetail::where('so_id', SalesOrder::where('ref_shipment_id', $refShipmentId)->value('so_id'))
            ->where('status', 1)->firstOrFail();
        $this->assertSame(24, (int) $detail->sod_qty, 'the original confirmed qty must be untouched');
    }

    public function test_shipped_leaves_the_shipment_scheduled_when_stock_is_insufficient(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 5);
        $refShipmentId = 'SHP-'.uniqid();

        $response = $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [$this->itemPayload($fx['sku'], $refUnitId, qty: 24)],
        ], $headers);

        $response->assertStatus(409)->assertJson([
            'success' => false,
            'error' => ['code' => 'INSUFFICIENT_STOCK'],
        ]);

        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->firstOrFail();
        $this->assertSame(self::STATUS_SCHEDULED, (int) $so->status, 'the SO must still exist, just not confirmed');

        $stock->refresh();
        $this->assertSame(5, (int) $stock->ps_stock, 'nothing must be deducted on a failed confirm');
    }

    public function test_shipped_rejects_an_unknown_armada_code(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);

        $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => 'SHP-'.uniqid(),
            'shipment_date' => '2026-07-25',
            'armada_code' => 'DOES-NOT-EXIST',
            'items' => [$this->itemPayload($fx['sku'], $refUnitId)],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_shipped_rejects_an_unknown_variant_sku(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $this->createUnit($refUnitId);

        $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => 'SHP-'.uniqid(),
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [$this->itemPayload('DOES-NOT-EXIST-'.uniqid(), $refUnitId)],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_shipped_accepts_photos_as_multipart_file_uploads(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $response = $this->post('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [$this->itemPayload($fx['sku'], $refUnitId)],
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 10, 10)],
        ], array_merge($headers, ['Accept' => 'application/json']));

        $response->assertStatus(201)->assertJson(['success' => true]);

        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->firstOrFail();
        $storedPhotos = json_decode($so->so_img, true);
        $this->assertCount(1, $storedPhotos);

        $path = public_path('issue/'.$storedPhotos[0]);
        $this->assertFileExists($path);
        $this->writtenPhotoPaths[] = $path;
    }
}
