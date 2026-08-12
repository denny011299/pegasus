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
 * External API v1 shipment status change — App\Http\Controllers\ExternalApi\V1\ShipmentController::
 * changeStatus(). Real warehouse id from the committed seed snapshot: 1 = Gudang Pusat (main), see
 * SalesOrderRetailAndUnitConversionFlowTest's docblock.
 *
 * DIKONFIRMASI pemilik produk 2026-08-13: hanya SATU transisi diizinkan saat ini — "Dijadwalkan"
 * ke "Sudah terkirim" — dan transisi itu MEMOTONG STOK sungguhan (proses yang sama dengan
 * POST /shipments/shipped). Segala kombinasi lain (label lain, atau status shipment saat ini
 * bukan "Dijadwalkan") ditolak INVALID_STATUS_TRANSITION.
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

    /** @return array{ref: string, variant: ProductVariant} "Dijadwalkan" (status=4, stock untouched) shipment. */
    private function createScheduledShipment(array $headers, int $stockQty = 100): array
    {
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, $stockQty);
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

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->patchJson('/api/external/v1/shipments/SHP-1/change-status', ['status' => 'Sudah terkirim'])
            ->assertStatus(401)->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
    }

    public function test_change_status_returns_shipment_not_found_for_an_unknown_ref(): void
    {
        $headers = $this->externalApiHeaders();

        $response = $this->patchJson(
            '/api/external/v1/shipments/DOES-NOT-EXIST-'.uniqid().'/change-status',
            ['status' => 'Sudah terkirim'],
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
        $fixture = $this->createScheduledShipment($headers);

        $response = $this->patchJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/change-status',
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

        $this->assertSame(4, SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('status'));
    }

    public function test_change_status_requires_the_status_field(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers);

        $this->patchJson('/api/external/v1/shipments/'.$fixture['ref'].'/change-status', [], $headers)
            ->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_change_status_from_dijadwalkan_to_sudah_terkirim_deducts_stock(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers, 100);

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $fixture['variant']->product_variant_id)
            ->where('warehouse_id', self::MAIN_WAREHOUSE_ID)->first();

        $response = $this->patchJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/change-status',
            ['status' => 'Sudah terkirim'],
            $headers,
        );

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => [
                'ref_shipment_id' => $fixture['ref'],
                'status' => 'Sudah terkirim',
                'message' => 'Status Pengiriman dengan referensi '.$fixture['ref'].' menjadi Sudah terkirim',
            ],
        ]);

        $this->assertSame(6, SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('status'));
        // 100 - 24 = 76, the same real deduction /shipments/shipped would have done.
        $this->assertSame(76, $stock->fresh()->ps_stock);
    }

    public function test_change_status_rejects_any_other_transition(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers);

        // Dijadwalkan -> Berjalan is not the one allowed transition.
        $response = $this->patchJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/change-status',
            ['status' => 'Berjalan'],
            $headers,
        );

        $response->assertStatus(409)->assertJson([
            'success' => false,
            'error' => ['code' => 'INVALID_STATUS_TRANSITION'],
        ]);
        $this->assertSame(4, SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('status'));
    }

    public function test_change_status_rejects_repeating_sudah_terkirim_once_already_there(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers);

        $this->patchJson('/api/external/v1/shipments/'.$fixture['ref'].'/change-status', ['status' => 'Sudah terkirim'], $headers)
            ->assertStatus(200);

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $fixture['variant']->product_variant_id)
            ->where('warehouse_id', self::MAIN_WAREHOUSE_ID)->first();
        $stockAfterFirstCall = $stock->fresh()->ps_stock;

        // Sending "Sudah terkirim" again once already there is not the allowed transition either
        // (Sudah Terkirim -> Sudah Terkirim isn't in ALLOWED_TRANSITIONS) — must not deduct twice.
        $response = $this->patchJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/change-status',
            ['status' => 'Sudah terkirim'],
            $headers,
        );

        $response->assertStatus(409)->assertJson(['success' => false, 'error' => ['code' => 'INVALID_STATUS_TRANSITION']]);
        $this->assertSame($stockAfterFirstCall, $stock->fresh()->ps_stock);
    }

    public function test_change_status_insufficient_stock_leaves_shipment_scheduled(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers, 5); // less than the 24 requested

        $response = $this->patchJson(
            '/api/external/v1/shipments/'.$fixture['ref'].'/change-status',
            ['status' => 'Sudah terkirim'],
            $headers,
        );

        $response->assertStatus(409)->assertJson(['success' => false, 'error' => ['code' => 'INSUFFICIENT_STOCK']]);
        $this->assertSame(4, SalesOrder::where('ref_shipment_id', $fixture['ref'])->value('status'));
    }

    public function test_get_shipment_reflects_sudah_terkirim_after_change_status(): void
    {
        $headers = $this->externalApiHeaders();
        $fixture = $this->createScheduledShipment($headers);

        $this->patchJson('/api/external/v1/shipments/'.$fixture['ref'].'/change-status', ['status' => 'Sudah terkirim'], $headers)
            ->assertStatus(200);

        $response = $this->getJson('/api/external/v1/shipments/'.$fixture['ref'], $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ref_shipment_id' => $fixture['ref'], 'ipm_status' => 4, 'ipm_status_label' => 'Sudah terkirim'],
        ]);
    }
}
