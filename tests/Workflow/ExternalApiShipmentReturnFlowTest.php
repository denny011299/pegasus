<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerProductReturnDetail;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 POST /shipments/returns (GitHub #58) —
 * App\Http\Controllers\ExternalApi\V1\ShipmentReturnController::store(), which delegates the
 * actual row-creation to App\Support\CustomerReturnCreation::create() — the same code path
 * App\Http\Controllers\CustomerReturnController::store() uses for the admin "Tambah Pengembalian"
 * button. This file only proves the External API's OWN contract (armada_code/item resolution/
 * warehouse-left-empty); it does not re-test CustomerReturnCreation's storage logic in isolation.
 *
 * A 1x1 PNG data URI stands in for "foto" throughout — proof storage itself is
 * CustomerReturnCreation::storeProofFromInput(), already exercised by the admin flow.
 */
class ExternalApiShipmentReturnFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const PROOF_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Return Test Armada';
        $customer->customer_code = 'RTN'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Retur Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createUnit(int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Return Test Unit '.uniqid();
        $unit->unit_short_name = 'RU-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    private function createSupplies(int $refSuppliesId, Unit $unit): Supplies
    {
        $supplies = new Supplies();
        $supplies->ref_supplies_id = $refSuppliesId;
        $supplies->supplies_name = 'Return Test Supplies '.uniqid();
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->supplies_unit = json_encode([(string) $unit->unit_id]);
        $supplies->supplies_alert = 0;
        $supplies->status = 1;
        $supplies->save();

        return $supplies;
    }

    /** @return array{variant: ProductVariant, sku: string} */
    private function createProductVariant(Unit $unit): array
    {
        $category = new Category();
        $category->category_name = 'Return Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Return Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'RTN-TEST-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Return Test Variant';
        $variant->product_variant_sku = $sku;
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        return ['variant' => $variant, 'sku' => $sku];
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/shipments/returns', [])->assertStatus(401);
    }

    public function test_store_creates_a_mixed_return_with_warehouse_left_empty(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $supplies = $this->createSupplies($refSuppliesId, $unit);
        $fx = $this->createProductVariant($unit);

        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'ref_number' => 'RTN-7788',
            'notes' => 'Sisa muatan',
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 5, 'satuan_id' => $refUnitId],
                ['type' => 2, 'ref_id' => $fx['sku'], 'qty' => 3, 'satuan_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => [
                'return_type' => 'mixed',
                'armada_code' => $armada->customer_code,
            ],
        ]);

        $returnGroup = $response->json('data.return_number');
        $this->assertMatchesRegularExpression('/^PKR\d{4}$/', $returnGroup);
        $supplyReturnId = $response->json('data.supply_return_id');
        $productReturnId = $response->json('data.product_return_id');
        $this->assertNotNull($supplyReturnId);
        $this->assertNotNull($productReturnId);

        $this->assertDatabaseHas('customer_supply_returns', [
            'return_id' => $supplyReturnId,
            'return_group' => $returnGroup,
            'customer_id' => $armada->customer_id,
            'status' => 1,
            'qc_staff_id' => null,
        ]);
        $this->assertDatabaseHas('customer_product_returns', [
            'return_id' => $productReturnId,
            'return_group' => $returnGroup,
            'customer_id' => $armada->customer_id,
            'status' => 1,
        ]);

        $supplyDetail = CustomerSupplyReturnDetail::where('return_id', $supplyReturnId)->firstOrFail();
        $this->assertSame($supplies->supplies_id, $supplyDetail->supplies_id);
        $this->assertSame($unit->unit_id, $supplyDetail->unit_id, 'satuan_id must resolve to the INTERNAL unit_id, not the ref_unit_id sent');
        $this->assertSame(5, (int) $supplyDetail->qty);
        $this->assertNull($supplyDetail->warehouse_id, 'warehouse must be left empty for the warehouse module to fill in later');

        $productDetail = CustomerProductReturnDetail::where('return_id', $productReturnId)->firstOrFail();
        $this->assertSame($fx['variant']->product_variant_id, $productDetail->product_variant_id);
        $this->assertSame(3, (int) $productDetail->qty);
        $this->assertNull($productDetail->warehouse_id);

        $proofPath = \App\Models\CustomerSupplyReturn::find($supplyReturnId)->proof_path;
        $this->assertNotNull($proofPath);
        $this->assertFileExists(public_path($proofPath));
        @unlink(public_path($proofPath));
    }

    public function test_store_merges_duplicate_item_lines_by_summing_qty(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 5, 'satuan_id' => $refUnitId],
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 2, 'satuan_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(201);
        $supplyReturnId = $response->json('data.supply_return_id');
        $this->assertSame(1, CustomerSupplyReturnDetail::where('return_id', $supplyReturnId)->count());
        $this->assertSame(7, (int) CustomerSupplyReturnDetail::where('return_id', $supplyReturnId)->value('qty'));

        $proofPath = \App\Models\CustomerSupplyReturn::find($supplyReturnId)->proof_path;
        @unlink(public_path($proofPath));
    }

    public function test_store_is_not_idempotent_and_creates_a_new_document_each_time(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $payload = [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'ref_number' => 'RTN-SAME',
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ];

        $first = $this->postJson('/api/external/v1/shipments/returns', $payload, $headers);
        $second = $this->postJson('/api/external/v1/shipments/returns', $payload, $headers);

        $first->assertStatus(201);
        $second->assertStatus(201);
        $this->assertNotSame($first->json('data.return_number'), $second->json('data.return_number'));

        foreach ([$first, $second] as $resp) {
            $proofPath = \App\Models\CustomerSupplyReturn::find($resp->json('data.supply_return_id'))->proof_path;
            @unlink(public_path($proofPath));
        }
    }

    public function test_store_rejects_an_unknown_armada_code(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => 'DOES-NOT-EXIST',
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);

        $this->assertSame(0, \App\Models\CustomerSupplyReturn::count());
    }

    public function test_store_rejects_an_unknown_ref_supplies_id_for_type_1(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $this->createUnit($refUnitId);

        $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                ['type' => 1, 'ref_id' => random_int(1, 999), 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_store_rejects_an_unknown_sku_for_type_2(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $this->createUnit($refUnitId);

        $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                ['type' => 2, 'ref_id' => 'DOES-NOT-EXIST-'.uniqid(), 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_store_rejects_a_unit_not_registered_for_the_supplies(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitIdRegistered = random_int(900000, 924999);
        $registeredUnit = $this->createUnit($refUnitIdRegistered);
        $refSuppliesId = random_int(900000, 924999);
        $this->createSupplies($refSuppliesId, $registeredUnit);

        $refUnitIdOther = random_int(925000, 949999);
        $this->createUnit($refUnitIdOther);

        $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitIdOther],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);

        $this->assertSame(0, \App\Models\CustomerSupplyReturn::count());
    }

    public function test_store_rejects_a_request_without_proof(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);

        $this->assertSame(0, \App\Models\CustomerSupplyReturn::count());
    }
}
