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
use App\Models\Warehouse;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 POST /shipments/returns (GitHub #58, extended by GitHub #203 for
 * ref_shipment_id/items[].ref_nota_id/optional proof/idempotency) —
 * App\Http\Controllers\ExternalApi\V1\ShipmentReturnController::store(), which delegates the
 * actual row-creation to App\Support\CustomerReturnCreation::create() — the same code path
 * App\Http\Controllers\CustomerReturnController::store() uses for the admin "Tambah Pengembalian"
 * button. This file only proves the External API's OWN contract (armada_code/item resolution/
 * per-line warehouse resolution); it does not re-test CustomerReturnCreation's storage logic in
 * isolation.
 *
 * Warehouse resolution — FINAL decision 2026-09-25 after a same-day back-and-forth (GitHub #203):
 * back to the rule confirmed by the product owner on 2026-08-17, PLUS one addition kept from the
 * #203 attempt. Bahan mentah lines and produk jadi lines whose unit ISN'T the product's retail
 * unit always resolve to the main warehouse automatically when gudang_id isn't sent; only a
 * retail-unit produk jadi line without gudang_id is left unassigned (warehouse_id NULL), requiring
 * manual assignment via the admin Pengiriman > Pengembalian edit modal before the document can be
 * accepted. The addition kept from #203: gudang_id is now honored for EVERY line type when the
 * caller does send it (bahan mentah and non-eceran produk included), not just retail-unit produk
 * as before 2026-08-17. gudang_id is the warehouse's own internal id (warehouses.id), NOT an
 * externally-synced ref column — same convention as gudang_id on POST /stock/check.
 *
 * A 1x1 PNG data URI stands in for "foto" throughout — proof storage itself is
 * CustomerReturnCreation::storeProofFromInput(), already exercised by the admin flow.
 */
class ExternalApiShipmentReturnFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const PROOF_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function mainWarehouseId(): int
    {
        return (int) Warehouse::query()
            ->where('warehouses.status', 1)
            ->whereHas('type', fn ($q) => $q->where('status', 1)->where('is_main_warehouse', 1))
            ->orderBy('warehouses.id')
            ->value('id');
    }

    private function retailWarehouseId(): int
    {
        return (int) Warehouse::query()
            ->where('warehouses.status', 1)
            ->whereHas('type', fn ($q) => $q->where('status', 1)->where('is_main_warehouse', 0))
            ->orderBy('warehouses.id')
            ->value('id');
    }

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

    /**
     * Like createProductVariant(), but the variant also accepts a second unit
     * (retailUnit) registered as product_variants.retail_unit — the "satuan
     * eceran" that triggers gudang_id resolution in resolveProductWarehouses().
     *
     * @return array{variant: ProductVariant, sku: string}
     */
    private function createProductVariantWithRetailUnit(Unit $normalUnit, Unit $retailUnit): array
    {
        $category = new Category();
        $category->category_name = 'Return Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Return Test Product Retail';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$normalUnit->unit_id, $retailUnit->unit_id]);
        $product->unit_id = $normalUnit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'RTN-TEST-RETAIL-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Return Test Variant Retail';
        $variant->product_variant_sku = $sku;
        $variant->product_variant_price = 0;
        $variant->retail_unit = $retailUnit->unit_id;
        $variant->status = 1;
        $variant->save();

        return ['variant' => $variant, 'sku' => $sku];
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/shipments/returns', [])->assertStatus(401);
    }

    public function test_store_creates_a_mixed_return_resolving_bahan_and_non_eceran_produk_to_main_warehouse(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $supplies = $this->createSupplies($refSuppliesId, $unit);
        $fx = $this->createProductVariant($unit);
        $mainWarehouseId = $this->mainWarehouseId();
        $this->assertGreaterThan(0, $mainWarehouseId, 'fixture needs a main warehouse (is_main_warehouse=1) in the seeded data');

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
                'pending_warehouse_items' => 0,
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
        $this->assertSame($mainWarehouseId, (int) $supplyDetail->warehouse_id, 'bahan mentah lines must always resolve to the main warehouse');

        $productDetail = CustomerProductReturnDetail::where('return_id', $productReturnId)->firstOrFail();
        $this->assertSame($fx['variant']->product_variant_id, $productDetail->product_variant_id);
        $this->assertSame(3, (int) $productDetail->qty);
        $this->assertSame($mainWarehouseId, (int) $productDetail->warehouse_id, 'produk jadi lines whose unit is NOT the retail unit must also resolve to the main warehouse');

        $proofPath = \App\Models\CustomerSupplyReturn::find($supplyReturnId)->proof_path;
        $this->assertNotNull($proofPath);
        $this->assertFileExists(public_path($proofPath));
        @unlink(public_path($proofPath));
    }

    public function test_store_uses_gudang_id_when_sent_for_any_line_type(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);
        $fx = $this->createProductVariant($unit);
        $retailWarehouseId = $this->retailWarehouseId();
        $this->assertGreaterThan(0, $retailWarehouseId, 'fixture needs a non-main warehouse (is_main_warehouse=0) in the seeded data');

        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                // GitHub #203: gudang_id now honored for EVERY line type, not just retail-unit produk
                // (a bahan mentah line ignoring gudang_id was the 2026-08-17 behavior; not anymore).
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId, 'gudang_id' => $retailWarehouseId],
                ['type' => 2, 'ref_id' => $fx['sku'], 'qty' => 2, 'satuan_id' => $refUnitId, 'gudang_id' => $retailWarehouseId],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson(['data' => ['pending_warehouse_items' => 0]]);
        $supplyDetail = CustomerSupplyReturnDetail::where('return_id', $response->json('data.supply_return_id'))->firstOrFail();
        $productDetail = CustomerProductReturnDetail::where('return_id', $response->json('data.product_return_id'))->firstOrFail();
        $this->assertSame($retailWarehouseId, (int) $supplyDetail->warehouse_id);
        $this->assertSame($retailWarehouseId, (int) $productDetail->warehouse_id);

        $proofPath = \App\Models\CustomerSupplyReturn::find($response->json('data.supply_return_id'))->proof_path;
        @unlink(public_path($proofPath));
    }

    public function test_store_leaves_warehouse_empty_only_for_a_retail_unit_produk_line_without_gudang_id(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refNormalUnitId = random_int(900000, 924999);
        $normalUnit = $this->createUnit($refNormalUnitId);
        $refRetailUnitId = random_int(925000, 949999);
        $retailUnit = $this->createUnit($refRetailUnitId);
        $fx = $this->createProductVariantWithRetailUnit($normalUnit, $retailUnit);

        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                // No gudang_id -- not required, must still succeed (201), left unassigned since the
                // unit sent IS the product's retail unit.
                ['type' => 2, 'ref_id' => $fx['sku'], 'qty' => 2, 'satuan_id' => $refRetailUnitId],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson(['data' => ['pending_warehouse_items' => 1]]);
        $productReturnId = $response->json('data.product_return_id');
        $productDetail = CustomerProductReturnDetail::where('return_id', $productReturnId)->firstOrFail();
        $this->assertNull($productDetail->warehouse_id, 'a retail-unit produk line without gudang_id must still be allowed to pass, unresolved');

        $proofPath = \App\Models\CustomerProductReturn::find($productReturnId)->proof_path;
        @unlink(public_path($proofPath));
    }

    public function test_store_resolves_a_non_eceran_produk_line_to_main_warehouse_even_without_gudang_id(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refNormalUnitId = random_int(900000, 924999);
        $normalUnit = $this->createUnit($refNormalUnitId);
        $refRetailUnitId = random_int(925000, 949999);
        $retailUnit = $this->createUnit($refRetailUnitId);
        $fx = $this->createProductVariantWithRetailUnit($normalUnit, $retailUnit);
        $mainWarehouseId = $this->mainWarehouseId();
        $this->assertGreaterThan(0, $mainWarehouseId, 'fixture needs a main warehouse (is_main_warehouse=1) in the seeded data');

        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                // Same product/variant, but the unit sent is the NORMAL unit, not the retail one.
                ['type' => 2, 'ref_id' => $fx['sku'], 'qty' => 2, 'satuan_id' => $refNormalUnitId],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson(['data' => ['pending_warehouse_items' => 0]]);
        $productReturnId = $response->json('data.product_return_id');
        $productDetail = CustomerProductReturnDetail::where('return_id', $productReturnId)->firstOrFail();
        $this->assertSame($mainWarehouseId, (int) $productDetail->warehouse_id);

        $proofPath = \App\Models\CustomerProductReturn::find($productReturnId)->proof_path;
        @unlink(public_path($proofPath));
    }

    public function test_store_rejects_an_invalid_gudang_id(): void
    {
        $headers = $this->externalApiHeaders();
        // okeh8644 snapshot data ships pre-existing CustomerSupplyReturn rows -- assert nothing
        // NEW was created, not that the global count is 0.
        $before = \App\Models\CustomerSupplyReturn::count();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'proof_base64' => self::PROOF_BASE64,
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId, 'gudang_id' => 999999],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);

        $this->assertSame($before, \App\Models\CustomerSupplyReturn::count(), 'the rejected request must not create a return');
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
        // okeh8644 snapshot data ships pre-existing CustomerSupplyReturn rows -- assert nothing
        // NEW was created, not that the global count is 0.
        $before = \App\Models\CustomerSupplyReturn::count();
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

        $this->assertSame($before, \App\Models\CustomerSupplyReturn::count(), 'the rejected request must not create a return');
    }

    /**
     * GitHub #203 follow-up (2026-09-25): body.armada is an alternative to armada_code that
     * upserts the Customer row (App\Support\ArmadaUpsert::upsertProfile()) instead of requiring
     * it to already exist -- mirrors the field vocabulary of PUT /api/external/v1/armada/{code}
     * (pic/pic_phone/nomor_polisi/category/merk_model/tahun_kendaraan/lokasi).
     */
    public function test_store_creates_a_brand_new_armada_via_the_armada_object(): void
    {
        $headers = $this->externalApiHeaders();
        $code = 'NEWARM'.random_int(1000, 9999);
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'proof_base64' => self::PROOF_BASE64,
            'armada' => [
                'code' => $code,
                'pic' => 'Budi',
                'pic_phone' => '08123456789',
            ],
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJson(['data' => ['armada_code' => $code]]);
        $this->assertDatabaseHas('customers', [
            'customer_code' => $code,
            'customer_pic' => 'Budi',
            'customer_pic_phone' => '08123456789',
            'status' => 1,
        ]);
    }

    public function test_store_reactivates_and_updates_an_existing_inactive_armada_via_the_armada_object(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = $this->createArmada();
        $customer->status = 0;
        $customer->customer_pic = 'Nama Lama';
        $customer->save();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'proof_base64' => self::PROOF_BASE64,
            'armada' => [
                'code' => $customer->customer_code,
                'pic' => 'Nama Baru',
            ],
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('customers', [
            'customer_id' => $customer->customer_id,
            'status' => 1,
            'customer_pic' => 'Nama Baru',
        ]);
    }

    public function test_store_upsert_via_armada_object_never_wipes_fields_not_sent(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = $this->createArmada();
        $customer->customer_pic = 'PIC Lama';
        $customer->customer_pic_phone = '0800000000';
        $customer->save();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        // Cuma kirim lokasi -- pic/pic_phone yang sudah ada TIDAK BOLEH ikut ter-null-kan,
        // beda sengaja dari semantik full-replace PUT /armada/{code}.
        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'proof_base64' => self::PROOF_BASE64,
            'armada' => [
                'code' => $customer->customer_code,
                'lokasi' => 'Gudang Cikarang',
            ],
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('customers', [
            'customer_id' => $customer->customer_id,
            'customer_pic' => 'PIC Lama',
            'customer_pic_phone' => '0800000000',
            'customer_lokasi' => 'Gudang Cikarang',
        ]);
    }

    public function test_store_rejects_mismatched_armada_code_and_armada_object(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => 'ARM-A',
            'armada' => ['code' => 'ARM-B'],
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
    }

    public function test_store_rejects_when_neither_armada_code_nor_armada_is_sent(): void
    {
        $headers = $this->externalApiHeaders();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ], $headers)->assertStatus(422)->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_FAILED']]);
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
        // okeh8644 snapshot data ships pre-existing CustomerSupplyReturn rows -- assert nothing
        // NEW was created, not that the global count is 0.
        $before = \App\Models\CustomerSupplyReturn::count();
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

        $this->assertSame($before, \App\Models\CustomerSupplyReturn::count(), 'the rejected request must not create a return');
    }

    public function test_store_rejects_a_request_without_proof(): void
    {
        $headers = $this->externalApiHeaders();
        // okeh8644 snapshot data ships pre-existing CustomerSupplyReturn rows -- assert nothing
        // NEW was created, not that the global count is 0.
        $before = \App\Models\CustomerSupplyReturn::count();
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

        $this->assertSame($before, \App\Models\CustomerSupplyReturn::count(), 'the rejected request must not create a return');
    }

    /**
     * GitHub #203: proof becomes optional the moment ref_shipment_id is sent -- PMO's edit-shipment
     * form has no photo field for this case. No ref_shipment_id still requires proof (proven above
     * by test_store_rejects_a_request_without_proof).
     */
    public function test_store_allows_no_proof_when_ref_shipment_id_is_sent(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);
        $refNotaId = random_int(100000, 999999);

        $response = $this->postJson('/api/external/v1/shipments/returns', [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'ref_shipment_id' => 'PMO-SHP-'.uniqid(),
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId, 'ref_nota_id' => $refNotaId],
            ],
        ], $headers);

        $response->assertStatus(201);
        $supplyReturnId = $response->json('data.supply_return_id');
        $this->assertNull(\App\Models\CustomerSupplyReturn::find($supplyReturnId)->proof_path);

        $supplyDetail = CustomerSupplyReturnDetail::where('return_id', $supplyReturnId)->firstOrFail();
        $this->assertSame($refNotaId, (int) $supplyDetail->ref_nota_id, 'items[].ref_nota_id must be stored on the detail row');
    }

    /**
     * GitHub #203: a repeated request with the same ref_shipment_id + return_date + items must
     * return the SAME document (200, meta.idempotent_replay: true), not create a second one --
     * beda dengan test_store_is_not_idempotent_and_creates_a_new_document_each_time() di atas,
     * yang mengirim payload TANPA ref_shipment_id sama sekali.
     */
    public function test_store_is_idempotent_when_ref_shipment_id_is_sent(): void
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
            'ref_shipment_id' => 'PMO-SHP-'.uniqid(),
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ];

        $before = \App\Models\CustomerSupplyReturn::count();
        $first = $this->postJson('/api/external/v1/shipments/returns', $payload, $headers);
        $second = $this->postJson('/api/external/v1/shipments/returns', $payload, $headers);

        $first->assertStatus(201);
        $this->assertNull($first->json('meta.idempotent_replay'), 'a freshly-created document has no idempotent_replay meta at all');
        $second->assertStatus(200)->assertJson(['meta' => ['idempotent_replay' => true]]);
        $this->assertSame($first->json('data.return_number'), $second->json('data.return_number'));
        $this->assertSame($first->json('data.supply_return_id'), $second->json('data.supply_return_id'));
        $this->assertSame($before + 1, \App\Models\CustomerSupplyReturn::count(), 'the replayed request must not create a second document');
    }

    /**
     * A different ref_shipment_id (or different items[]) must NOT collide with an unrelated
     * document's idempotency key -- proves the key is actually scoped per shipment+payload, not a
     * blanket "any ref_shipment_id already used" check.
     */
    public function test_store_creates_separate_documents_for_different_ref_shipment_ids(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $refUnitId = random_int(900000, 949999);
        $unit = $this->createUnit($refUnitId);
        $refSuppliesId = random_int(900000, 949999);
        $this->createSupplies($refSuppliesId, $unit);

        $makePayload = fn (string $refShipmentId) => [
            'return_date' => '2026-08-17',
            'armada_code' => $armada->customer_code,
            'ref_shipment_id' => $refShipmentId,
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 1, 'satuan_id' => $refUnitId],
            ],
        ];

        $first = $this->postJson('/api/external/v1/shipments/returns', $makePayload('PMO-SHP-'.uniqid()), $headers);
        $second = $this->postJson('/api/external/v1/shipments/returns', $makePayload('PMO-SHP-'.uniqid()), $headers);

        $first->assertStatus(201);
        $second->assertStatus(201);
        $this->assertNotSame($first->json('data.return_number'), $second->json('data.return_number'));
    }

    /**
     * GitHub #203: two lines with the same supplies_id/unit_id but a different ref_nota_id must
     * NOT be merged into one detail row -- per-nota traceability would be lost otherwise.
     */
    public function test_store_does_not_merge_lines_that_differ_only_by_ref_nota_id(): void
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
            'ref_shipment_id' => 'PMO-SHP-'.uniqid(),
            'items' => [
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 3, 'satuan_id' => $refUnitId, 'ref_nota_id' => 111],
                ['type' => 1, 'ref_id' => $refSuppliesId, 'qty' => 4, 'satuan_id' => $refUnitId, 'ref_nota_id' => 222],
            ],
        ], $headers);

        $response->assertStatus(201);
        $supplyReturnId = $response->json('data.supply_return_id');
        $details = CustomerSupplyReturnDetail::where('return_id', $supplyReturnId)->orderBy('ref_nota_id')->get();
        $this->assertCount(2, $details, 'lines with the same item+unit but different ref_nota_id must stay separate');
        $this->assertSame(111, (int) $details[0]->ref_nota_id);
        $this->assertSame(3, (int) $details[0]->qty);
        $this->assertSame(222, (int) $details[1]->ref_nota_id);
        $this->assertSame(4, (int) $details[1]->qty);
    }
}
