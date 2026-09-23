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
 * External API v1 shipment upsert — App\Http\Controllers\ExternalApi\V1\ShipmentController::
 * shipped(). Sejak flow "shipment-approval-flow v2" (2026-09), insert/update TIDAK LAGI
 * memotong stok maupun mengubah status ke Confirmed — hasilnya SELALU status 1 "Pending",
 * menunggu approval 2 tahap (App\Support\ShipmentApproval, App\Http\Controllers\
 * CustomerController::approveShipment()) di sisi admin sebelum stok dipotong. Lihat
 * cdocs/docs/specs/shipment-external-api-approval-flow.md.
 *
 * Real warehouse id from the committed seed snapshot: 1 = Gudang Pusat (main), see
 * SalesOrderRetailAndUnitConversionFlowTest's docblock.
 */
class ExternalApiShipmentShippedFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const MAIN_WAREHOUSE_ID = 1;

    /** sales_orders.status "Pending" — SATU-SATUNYA hasil insert/update shipped() sekarang. */
    private const STATUS_PENDING = 1;

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

    private function itemPayload(string $sku, int $refUnitId, int $qty = 24, ?int $refNotaId = null): array
    {
        $item = [
            'variant_sku' => $sku,
            'qty' => $qty,
            'unit_id' => $refUnitId,
            'product_name' => 'AIR AKI HIKARI',
            'variant_name' => '20 x 400ml',
        ];

        if ($refNotaId !== null) {
            $item['ref_nota_id'] = $refNotaId;
        }

        return $item;
    }

    /** @param  array<string, mixed>  $overrides */
    private function payload(string $refShipmentId, string $armadaCode, array $items, array $overrides = []): array
    {
        return array_merge([
            'ref_shipment_id' => $refShipmentId,
            'shipment_date' => '2026-07-25',
            'armada_code' => $armadaCode,
            'status' => 'onprocess',
            'items' => $items,
        ], $overrides);
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/shipments/shipped', [
            'ref_shipment_id' => 'SHP-1',
            'shipment_date' => '2026-07-25',
            'armada_code' => 'X',
            'status' => 'onprocess',
            'items' => [],
        ])->assertStatus(401)->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED']]);
    }

    public function test_shipped_rejects_a_status_other_than_onprocess(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            'SHP-'.uniqid(),
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId)],
            ['status' => 'onschedule'],
        ), $headers);

        $response->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'INVALID_STATUS']]);
    }

    public function test_shipped_creates_a_new_shipment_as_pending_without_touching_stock(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId)],
            ['notes' => 'Pengiriman test'],
        ), $headers);

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
        $this->assertSame(self::STATUS_PENDING, (int) $so->status, 'insert must always land on Pending, never auto-confirm');
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
        $this->assertSame(100, (int) $stock->ps_stock, 'shipped() must never deduct stock — that only happens at Ops approval');
    }

    public function test_shipped_stores_the_optional_ref_nota_id_per_item(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        // 16-digit PMO id (oms_order.id), see GitHub #180.
        $refNotaId = 4328012026102327;

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, refNotaId: $refNotaId)],
        ), $headers);

        $response->assertStatus(201);
        $soId = $response->json('data.shipment_internal_id');

        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();
        $this->assertSame($refNotaId, (int) $detail->ref_nota_id);
    }

    public function test_shipped_resolves_a_variant_sku_that_differs_only_in_case(): void
    {
        // GitHub #181 — see the equivalent test on /shipments/scheduled for the full explanation.
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $mixedCaseSku = strtolower($fx['sku']);
        $this->assertNotSame($fx['sku'], $mixedCaseSku, 'fixture sku must contain uppercase letters for this test to be meaningful');

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($mixedCaseSku, $refUnitId, qty: 7)],
        ), $headers);

        $response->assertStatus(201);
        $soId = $response->json('data.shipment_internal_id');
        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();
        $this->assertSame($fx['variant']->product_variant_id, $detail->product_variant_id);
        $this->assertSame(7, (int) $detail->sod_qty);
    }

    public function test_shipped_force_upserts_differing_details_while_still_pending(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 10)],
        ), $headers)->assertStatus(201);

        // Qty berbeda dari yang tersimpan (10 -> 30) - detail_handler default "force".
        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 30)],
        ), $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ipm_status' => 2, 'ipm_status_label' => 'Berjalan'],
        ]);

        $soId = $response->json('data.shipment_internal_id');
        $detail = SalesOrderDetail::where('so_id', $soId)->where('status', 1)->firstOrFail();
        $this->assertSame(30, (int) $detail->sod_qty, 'force must overwrite the stored qty');
        $this->assertSame(self::STATUS_PENDING, (int) SalesOrder::findOrFail($soId)->status);

        $stock->refresh();
        $this->assertSame(100, (int) $stock->ps_stock, 'still Pending — nothing deducted yet');
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

        $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 10)],
        ), $headers)->assertStatus(201);

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 30)],
            ['detail_handler' => 'validate'],
        ), $headers);

        $response->assertStatus(409)->assertJson([
            'success' => false,
            'error' => ['code' => 'SHIPMENT_DETAIL_MISMATCH'],
        ]);
        $this->assertContains('items', $response->json('error.details.mismatched_fields'));

        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->firstOrFail();
        $this->assertSame(self::STATUS_PENDING, (int) $so->status, 'a rejected mismatch must not change the status');

        $detail = SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)->firstOrFail();
        $this->assertSame(10, (int) $detail->sod_qty, 'validate must never change stored data');

        $stock->refresh();
        $this->assertSame(100, (int) $stock->ps_stock);
    }

    public function test_shipped_with_validate_still_succeeds_when_data_already_matches(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 24)],
        ), $headers)->assertStatus(201);

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 24)],
            ['detail_handler' => 'validate'],
        ), $headers);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'data' => ['ipm_status' => 2],
        ]);
    }

    public function test_shipped_rejects_updating_a_shipment_that_already_has_approval(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $created = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 24)],
        ), $headers)->assertStatus(201);
        $soId = $created->json('data.shipment_internal_id');

        // Simulasikan approval QC sudah terjadi di sisi admin.
        $so = SalesOrder::findOrFail($soId);
        $so->qc_approved_by = 1;
        $so->qc_approved_at = now();
        $so->save();

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 99)],
        ), $headers);

        $response->assertStatus(409)->assertJson([
            'success' => false,
            'error' => ['code' => 'SHIPMENT_NOT_UPDATABLE'],
        ]);

        $detail = SalesOrderDetail::where('so_id', $soId)->where('status', 1)->firstOrFail();
        $this->assertSame(24, (int) $detail->sod_qty, 'PMO must not be able to rewrite a shipment once approval has started');

        $stock->refresh();
        $this->assertSame(100, (int) $stock->ps_stock);
    }

    public function test_shipped_rejects_updating_a_shipment_that_is_already_confirmed(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $refShipmentId = 'SHP-'.uniqid();

        $created = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId)],
        ), $headers)->assertStatus(201);
        $soId = $created->json('data.shipment_internal_id');

        $so = SalesOrder::findOrFail($soId);
        $so->status = 2; // Confirmed/Diterima, seperti hasil approval Ops selesai.
        $so->save();

        $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 5)],
        ), $headers)->assertStatus(409)->assertJson([
            'success' => false,
            'error' => ['code' => 'SHIPMENT_NOT_UPDATABLE'],
        ]);
    }

    public function test_shipped_auto_creates_an_unknown_armada_code(): void
    {
        // GitHub #187: armada_code that doesn't exist yet is upserted (bare — just the code),
        // not rejected — PMO shouldn't have to call the Armada endpoints before shipping.
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $newArmadaCode = 'NEW-ARMADA-'.uniqid();

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            'SHP-'.uniqid(),
            $newArmadaCode,
            [$this->itemPayload($fx['sku'], $refUnitId)],
        ), $headers);

        $response->assertStatus(201);

        $customer = Customer::where('customer_code', $newArmadaCode)->first();
        $this->assertNotNull($customer, 'armada_code must be auto-created when it does not exist yet');
        $this->assertSame(1, (int) $customer->status);
    }

    public function test_shipped_rejects_an_unknown_variant_sku(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $this->createUnit($refUnitId);

        $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            'SHP-'.uniqid(),
            $armada->customer_code,
            [$this->itemPayload('DOES-NOT-EXIST-'.uniqid(), $refUnitId)],
        ), $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
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
            'status' => 'onprocess',
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

    public function test_shipped_creates_a_shortage_document_when_requested(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 5);
        $refShipmentId = 'SHP-'.uniqid();

        $response = $this->postJson('/api/external/v1/shipments/shipped', $this->payload(
            $refShipmentId,
            $armada->customer_code,
            [$this->itemPayload($fx['sku'], $refUnitId, qty: 24)],
            ['auto_create_shortage_doc' => true],
        ), $headers);

        // Shortage tidak menghalangi shipment tetap tersimpan Pending.
        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => ['shortage_doc_created' => true],
        ]);
        $this->assertNotNull($response->json('data.shortage_doc_number'));

        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->firstOrFail();
        $this->assertSame(self::STATUS_PENDING, (int) $so->status);
    }
}
