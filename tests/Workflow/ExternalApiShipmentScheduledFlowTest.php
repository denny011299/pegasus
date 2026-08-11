<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ShipmentShortageDocument;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 shipment scheduling — App\Http\Controllers\ExternalApi\V1\ShipmentController::
 * scheduled(). Real warehouse id from the committed seed snapshot: 1 = Gudang Pusat (main), see
 * SalesOrderRetailAndUnitConversionFlowTest's docblock.
 */
class ExternalApiShipmentScheduledFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const MAIN_WAREHOUSE_ID = 1;

    /** sales_orders.status = 4 ("Dijadwalkan"), see migration 2026_08_11_130000_*. */
    private const STATUS_SCHEDULED = 4;

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Shipment Test Armada';
        $customer->customer_code = 'SC'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createUnit(?int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Shipment Test Unit '.uniqid();
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
        $category->category_name = 'Shipment Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Shipment Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'SHP-TEST-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Shipment Test Variant';
        $variant->product_variant_sku = $sku;
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        return ['variant' => $variant, 'sku' => $sku];
    }

    private function createStock(ProductVariant $variant, int $unitId, int $qty): void
    {
        \App\Models\ProductStock::withoutGlobalScope('active_warehouse')->create([
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
        $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => 'SHP-1',
            'scheduled_date' => '2026-07-25',
            'armada_code' => 'X',
            'items' => [],
        ])->assertStatus(401)->assertJson(['success' => false, 'error' => ['code' => 'unauthenticated']]);
    }

    public function test_scheduled_creates_a_sales_order_with_status_scheduled_and_no_shortage(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $response = $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'auto_create_shortage_doc' => true,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 24, 'unit_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $refShipmentId,
                'status' => 'scheduled',
                'shortage_doc_created' => false,
                'shortage_doc_number' => null,
            ],
        ]);

        $soId = $response->json('data.shipment_internal_id');
        $this->assertNotNull($soId);

        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(self::STATUS_SCHEDULED, (int) $so->status);
        $this->assertSame($refShipmentId, $so->ref_shipment_id);
        $this->assertSame((string) $armada->customer_id, $so->so_customer);
        $this->assertSame('2026-07-25', $so->so_date);
        $this->assertSame(0, (int) $so->so_total);

        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();
        $this->assertSame($fx['variant']->product_variant_id, $detail->product_variant_id);
        $this->assertSame($unit->unit_id, $detail->unit_id, 'sales_order_details.unit_id must be the INTERNAL unit_id, not the ref_unit_id sent in the request');
        $this->assertSame(self::MAIN_WAREHOUSE_ID, $detail->warehouse_id);
        $this->assertSame(24, (int) $detail->sod_qty);
        $this->assertSame(0, (int) $detail->sod_harga);
        $this->assertSame(0, (int) $detail->sod_subtotal);

        $this->assertSame(0, ShipmentShortageDocument::where('so_id', $soId)->count());
    }

    public function test_scheduled_still_creates_the_so_when_stock_is_short_and_creates_a_shortage_doc_when_asked(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 5);
        $refShipmentId = 'SHP-'.uniqid();

        $response = $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'auto_create_shortage_doc' => true,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 24, 'unit_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => [
                'status' => 'scheduled',
                'shortage_doc_created' => true,
            ],
        ]);

        $soId = $response->json('data.shipment_internal_id');
        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(self::STATUS_SCHEDULED, (int) $so->status, 'shortage must NOT block scheduling');

        $docNumber = $response->json('data.shortage_doc_number');
        $this->assertMatchesRegularExpression('/^BG-\d{4}$/', $docNumber);

        $doc = ShipmentShortageDocument::where('so_id', $soId)->firstOrFail();
        $this->assertSame($docNumber, $doc->doc_number);
        $this->assertSame($refShipmentId, $doc->ref_shipment_id);
        $this->assertCount(1, $doc->items);
        $this->assertSame($fx['sku'], $doc->items[0]['sku']);
        $this->assertSame(24, $doc->items[0]['requested']);
        $this->assertSame(5, $doc->items[0]['available']);
        $this->assertSame(19, $doc->items[0]['shortage']);
    }

    public function test_scheduled_does_not_create_a_shortage_doc_when_not_asked_even_if_short(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 5);
        $refShipmentId = 'SHP-'.uniqid();

        $response = $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'auto_create_shortage_doc' => false,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 24, 'unit_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => [
                'status' => 'scheduled',
                'shortage_doc_created' => false,
                'shortage_doc_number' => null,
            ],
        ]);

        $soId = $response->json('data.shipment_internal_id');
        $this->assertSame(self::STATUS_SCHEDULED, (int) SalesOrder::findOrFail($soId)->status);
        $this->assertSame(0, ShipmentShortageDocument::where('so_id', $soId)->count());
    }

    public function test_scheduled_rejects_a_duplicate_ref_shipment_id(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $payload = [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $refUnitId],
            ],
        ];

        $this->postJson('/api/external/v1/shipments/scheduled', $payload, $headers)->assertStatus(201);

        $second = $this->postJson('/api/external/v1/shipments/scheduled', $payload, $headers);
        $second->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'duplicate_ref_id']]);

        $this->assertSame(1, SalesOrder::where('ref_shipment_id', $refShipmentId)->count());
    }

    public function test_scheduled_rejects_an_unknown_armada_code(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);

        $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => 'SHP-'.uniqid(),
            'scheduled_date' => '2026-07-25',
            'armada_code' => 'DOES-NOT-EXIST',
            'items' => [
                ['sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'validation_failed']]);
    }

    public function test_scheduled_rejects_an_unknown_sku(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $this->createUnit($refUnitId);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => $refShipmentId,
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => 'DOES-NOT-EXIST-'.uniqid(), 'qty' => 1, 'unit_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'validation_failed']]);

        $this->assertSame(0, SalesOrder::where('ref_shipment_id', $refShipmentId)->count());
    }

    public function test_scheduled_handles_multiple_items(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitIdA = random_int(900000, 949999);
        $refUnitIdB = random_int(950000, 999999);
        $unitA = $this->createUnit($refUnitIdA);
        $unitB = $this->createUnit($refUnitIdB);
        $fxA = $this->createProductFixture($unitA);
        $fxB = $this->createProductFixture($unitB);
        $this->createStock($fxA['variant'], $unitA->unit_id, 100);
        $this->createStock($fxB['variant'], $unitB->unit_id, 100);

        $response = $this->postJson('/api/external/v1/shipments/scheduled', [
            'ref_shipment_id' => 'SHP-'.uniqid(),
            'scheduled_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['sku' => $fxA['sku'], 'qty' => 10, 'unit_id' => $refUnitIdA],
                ['sku' => $fxB['sku'], 'qty' => 20, 'unit_id' => $refUnitIdB],
            ],
        ], $headers);

        $response->assertStatus(201);
        $soId = $response->json('data.shipment_internal_id');
        $this->assertSame(2, SalesOrderDetail::where('so_id', $soId)->count());
    }
}
