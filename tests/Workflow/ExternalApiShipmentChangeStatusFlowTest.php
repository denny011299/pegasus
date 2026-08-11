<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 shipment status change — App\Http\Controllers\ExternalApi\V1\ShipmentController::
 * changeStatus(). Real warehouse id from the committed seed snapshot: 1 = Gudang Pusat (main), see
 * SalesOrderRetailAndUnitConversionFlowTest's docblock.
 *
 * changeStatus() FORCE-writes sales_orders.status with no transition guard and no side effects
 * (no stock deduction even when targeting "Berjalan") — see the controller's docblock. These tests
 * cover exactly that: any status -> any valid label succeeds, no stock is touched, and the round
 * trip through GET /shipments/{ref} shows the correct ipm_status/label back, including for the two
 * new internal codes (5, 6) that only this endpoint can produce.
 */
class ExternalApiShipmentChangeStatusFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const MAIN_WAREHOUSE_ID = 1;

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Change Status Test Armada';
        $customer->customer_code = 'CS'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createUnit(?int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Change Status Test Unit '.uniqid();
        $unit->unit_short_name = 'CS-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    /** @return array{variant: ProductVariant, sku: string} */
    private function createProductFixture(Unit $unit): array
    {
        $category = new Category();
        $category->category_name = 'Change Status Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Change Status Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'CST-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Change Status Test Variant';
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

    /** Creates a "Dijadwalkan" (status=4) shipment via the real endpoint, returns its ref. */
    private function createScheduledShipment(array $headers): string
    {
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

        return $refShipmentId;
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->patchJson('/api/external/v1/shipments/SHP-1/change-status', ['status' => 'Berjalan'])
            ->assertStatus(401)->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
    }

    public function test_change_status_returns_shipment_not_found_for_an_unknown_ref(): void
    {
        $headers = $this->externalApiHeaders();

        $response = $this->patchJson(
            '/api/external/v1/shipments/DOES-NOT-EXIST-'.uniqid().'/change-status',
            ['status' => 'Berjalan'],
            $headers,
        );

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'error' => ['code' => 'SHIPMENT_NOT_FOUND'],
        ]);
        $this->assertStringContainsString('tidak ditemukan', $response->json('error.message'));
    }

    public function test_change_status_rejects_a_label_outside_the_agreed_four(): void
    {
        $headers = $this->externalApiHeaders();
        $refShipmentId = $this->createScheduledShipment($headers);

        $response = $this->patchJson(
            '/api/external/v1/shipments/'.$refShipmentId.'/change-status',
            ['status' => 'Diterima'],
            $headers,
        );

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'error' => ['code' => 'INVALID_STATUS'],
        ]);
        $message = $response->json('error.message');
        $this->assertStringContainsString('Dijadwalkan', $message);
        $this->assertStringContainsString('Sudah terkirim', $message);

        $this->assertSame(4, SalesOrder::where('ref_shipment_id', $refShipmentId)->value('status'));
    }

    public function test_change_status_requires_the_status_field(): void
    {
        $headers = $this->externalApiHeaders();
        $refShipmentId = $this->createScheduledShipment($headers);

        $this->patchJson('/api/external/v1/shipments/'.$refShipmentId.'/change-status', [], $headers)
            ->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    public static function labelToInternalStatusProvider(): array
    {
        return [
            'Dijadwalkan' => ['Dijadwalkan', 4],
            'Berjalan' => ['Berjalan', 2],
            'Belum terkirim' => ['Belum terkirim', 5],
            'Sudah terkirim' => ['Sudah terkirim', 6],
        ];
    }

    #[DataProvider('labelToInternalStatusProvider')]
    public function test_change_status_force_writes_the_target_internal_status(string $label, int $expectedInternalStatus): void
    {
        $headers = $this->externalApiHeaders();
        $refShipmentId = $this->createScheduledShipment($headers);

        $response = $this->patchJson(
            '/api/external/v1/shipments/'.$refShipmentId.'/change-status',
            ['status' => $label],
            $headers,
        );

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $refShipmentId,
                'status' => $label,
                'message' => 'Status Pengiriman dengan referensi '.$refShipmentId.' menjadi '.$label,
            ],
        ]);

        $this->assertSame($expectedInternalStatus, SalesOrder::where('ref_shipment_id', $refShipmentId)->value('status'));
    }

    public function test_change_status_to_berjalan_does_not_deduct_stock(): void
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

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $fx['variant']->product_variant_id)
            ->where('warehouse_id', self::MAIN_WAREHOUSE_ID)->first();
        $stockBefore = $stock->ps_stock;

        $this->patchJson(
            '/api/external/v1/shipments/'.$refShipmentId.'/change-status',
            ['status' => 'Berjalan'],
            $headers,
        )->assertStatus(200);

        // changeStatus() force-writes the status column only — no SalesOrderApproval::confirm()
        // call, so the stock row must be untouched (this is the documented, needs-confirmation
        // gap, not a bug — see the controller/doc-class docblocks).
        $this->assertSame($stockBefore, $stock->fresh()->ps_stock);
    }

    public function test_change_status_has_no_transition_guard_any_status_to_any_label(): void
    {
        $headers = $this->externalApiHeaders();
        $refShipmentId = $this->createScheduledShipment($headers);

        // Dijadwalkan (4) -> Sudah terkirim (6), skipping Berjalan/Belum terkirim entirely.
        $this->patchJson(
            '/api/external/v1/shipments/'.$refShipmentId.'/change-status',
            ['status' => 'Sudah terkirim'],
            $headers,
        )->assertStatus(200)->assertJson(['data' => ['status' => 'Sudah terkirim']]);

        // ...then straight back to Dijadwalkan, going "backwards".
        $this->patchJson(
            '/api/external/v1/shipments/'.$refShipmentId.'/change-status',
            ['status' => 'Dijadwalkan'],
            $headers,
        )->assertStatus(200)->assertJson(['data' => ['status' => 'Dijadwalkan']]);

        $this->assertSame(4, SalesOrder::where('ref_shipment_id', $refShipmentId)->value('status'));
    }

    #[DataProvider('labelToInternalStatusProvider')]
    public function test_get_shipment_reflects_the_changed_status_afterwards(string $label): void
    {
        $headers = $this->externalApiHeaders();
        $refShipmentId = $this->createScheduledShipment($headers);

        $this->patchJson(
            '/api/external/v1/shipments/'.$refShipmentId.'/change-status',
            ['status' => $label],
            $headers,
        )->assertStatus(200);

        $response = $this->getJson('/api/external/v1/shipments/'.$refShipmentId, $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ref_shipment_id' => $refShipmentId, 'ipm_status_label' => $label],
        ]);
    }
}
