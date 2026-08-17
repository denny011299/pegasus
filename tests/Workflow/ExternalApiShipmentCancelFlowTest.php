<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 shipment cancellation — App\Http\Controllers\ExternalApi\V1\ShipmentController::
 * cancel(), backed by the new App\Support\SalesOrderCancellation::cancel(). Real warehouse id from
 * the committed seed snapshot: 1 = Gudang Pusat (main), see
 * SalesOrderRetailAndUnitConversionFlowTest's docblock.
 *
 * cancel() runs a REAL cancellation (restores stock if the shipment was Confirmed), unlike
 * changeStatus() which only force-writes the status column — these tests specifically cover that
 * distinction, plus the idempotency guard against double stock restoration.
 */
class ExternalApiShipmentCancelFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const MAIN_WAREHOUSE_ID = 1;

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Cancel Test Armada';
        $customer->customer_code = 'CX'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createUnit(?int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Cancel Test Unit '.uniqid();
        $unit->unit_short_name = 'CX-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    /** @return array{variant: ProductVariant, sku: string} */
    private function createProductFixture(Unit $unit): array
    {
        $category = new Category();
        $category->category_name = 'Cancel Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Cancel Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'CXT-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Cancel Test Variant';
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

    /** @return array{ref: string, variant: ProductVariant} scheduled (status=4, stock untouched) shipment. */
    private function createScheduledShipment(array $headers): array
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

        return ['ref' => $refShipmentId, 'variant' => $fx['variant']];
    }

    /** @return array{ref: string, variant: ProductVariant} confirmed/"Berjalan" (status=2, stock deducted) shipment. */
    private function createShippedShipment(array $headers): array
    {
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armada->customer_code,
            'items' => [[
                'variant_sku' => $fx['sku'],
                'qty' => 24,
                'unit_id' => $refUnitId,
                'product_name' => 'Cancel Test Product',
            ]],
        ], $headers)->assertStatus(201);

        return ['ref' => $refShipmentId, 'variant' => $fx['variant']];
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->putJson('/api/external/v1/shipments/SHP-1/cancel', ['reason' => 'test'])
            ->assertStatus(401)->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
    }

    public function test_cancel_returns_shipment_not_found_for_an_unknown_ref(): void
    {
        $headers = $this->externalApiHeaders();

        $response = $this->putJson(
            '/api/external/v1/shipments/DOES-NOT-EXIST-'.uniqid().'/cancel',
            ['reason' => 'test'],
            $headers,
        );

        $response->assertStatus(404)->assertJson([
            'success' => false,
            'error' => ['code' => 'SHIPMENT_NOT_FOUND'],
        ]);
    }

    public function test_cancel_a_scheduled_shipment_does_not_touch_stock(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers);

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $fixture['variant']->product_variant_id)
            ->where('warehouse_id', self::MAIN_WAREHOUSE_ID)->first();
        $stockBefore = $stock->ps_stock;

        $response = $this->putJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/cancel',
            ['reason' => 'Dibatalkan di PMO karena armada rusak'],
            $headers,
        );

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $fixture['ref'],
                'ipm_status' => -1,
                'ipm_status_label' => 'Dibatalkan',
                'message' => 'Dokumen berhasil dibatalkan.',
            ],
        ]);
        $this->assertNotNull($response->json('data.shipment_internal_id'));

        $this->assertSame(7, SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('status'));
        $this->assertSame(
            'Dibatalkan di PMO karena armada rusak',
            SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('cancel_reason'),
        );
        $this->assertSame($stockBefore, $stock->fresh()->ps_stock);
    }

    public function test_cancel_a_confirmed_shipment_restores_stock(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createShippedShipment($headers);

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $fixture['variant']->product_variant_id)
            ->where('warehouse_id', self::MAIN_WAREHOUSE_ID)->first();
        $stockAfterShip = $stock->fresh()->ps_stock;
        $this->assertSame(76, $stockAfterShip); // 100 - 24, sanity check the shipped() fixture deducted

        $response = $this->putJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/cancel',
            ['reason' => 'Dibatalkan di PMO karena armada rusak'],
            $headers,
        );

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $fixture['ref'],
                'ipm_status' => -1,
                'ipm_status_label' => 'Dibatalkan',
                'message' => 'Dokumen berhasil dibatalkan dan stok telah dikembalikan.',
            ],
        ]);

        $this->assertSame(7, SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('status'));
        $this->assertSame(100, $stock->fresh()->ps_stock); // fully restored back to pre-shipment level
    }

    public function test_cancel_a_sudah_terkirim_shipment_restores_stock(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers);

        // Dijadwalkan -> Sudah terkirim, per App\Support\SalesOrderApproval::confirm() deducting
        // stock the same way /shipments/shipped does (DIKONFIRMASI pemilik produk 2026-08-13).
        $this->patchJson('/api/external/v1/shipments/'.$fixture['ref'].'/change-status', ['status' => 'Sudah terkirim'], $headers)
            ->assertStatus(200);

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $fixture['variant']->product_variant_id)
            ->where('warehouse_id', self::MAIN_WAREHOUSE_ID)->first();
        $this->assertSame(76, $stock->fresh()->ps_stock); // 100 - 24, sanity check change-status deducted

        $response = $this->putJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/cancel',
            ['reason' => 'Dibatalkan setelah Sudah terkirim'],
            $headers,
        );

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $fixture['ref'],
                'ipm_status' => -1,
                'ipm_status_label' => 'Dibatalkan',
                'message' => 'Dokumen berhasil dibatalkan dan stok telah dikembalikan.',
            ],
        ]);

        $this->assertSame(7, SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('status'));
        $this->assertSame(100, $stock->fresh()->ps_stock); // fully restored back to pre-shipment level
    }

    public function test_cancel_is_idempotent_and_does_not_double_restore_stock(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createShippedShipment($headers);

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $fixture['variant']->product_variant_id)
            ->where('warehouse_id', self::MAIN_WAREHOUSE_ID)->first();

        $this->putJson('/api/external/v1/shipments/'.$fixture['ref'].'/cancel', ['reason' => 'first'], $headers)
            ->assertStatus(200);
        $stockAfterFirstCancel = $stock->fresh()->ps_stock;
        $this->assertSame(100, $stockAfterFirstCancel);

        $response = $this->putJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/cancel',
            ['reason' => 'second, should be a no-op replay'],
            $headers,
        );

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ipm_status' => -1, 'ipm_status_label' => 'Dibatalkan'],
            'meta' => ['already_cancelled' => true],
        ]);

        // Stock must NOT have been restored a second time.
        $this->assertSame($stockAfterFirstCancel, $stock->fresh()->ps_stock);
    }

    public function test_cancel_reason_is_optional(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers);

        $this->putJson('/api/external/v1/shipments/'.$fixture['ref'].'/cancel', [], $headers)
            ->assertStatus(200)->assertJson(['success' => true, 'data' => ['ipm_status_label' => 'Dibatalkan']]);

        $this->assertNull(SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('cancel_reason'));
    }

    public function test_get_shipment_reflects_cancelled_status_afterwards(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers);

        $this->putJson('/api/external/v1/shipments/'.$fixture['ref'].'/cancel', ['reason' => 'x'], $headers)
            ->assertStatus(200);

        $response = $this->getJson('/api/external/v1/shipments/'.$fixture['ref'], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ref_shipment_id' => $fixture['ref'], 'ipm_status' => -1, 'ipm_status_label' => 'Dibatalkan'],
        ]);
    }
}
