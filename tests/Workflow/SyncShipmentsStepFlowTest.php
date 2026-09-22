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
use App\Synchronization\Steps\ShipmentFlow\FetchShipmentsStep;
use App\Synchronization\Steps\ShipmentFlow\SyncShipmentsStep;
use App\Synchronization\SyncStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SyncShipmentsStep — Sinkronisasi Pengiriman (Spec B, 2026-09-23, lihat
 * cdocs/docs/specs/shipment-pmo-sync-flow.md). Rekonsiliasi dua arah: insert baris baru DAN
 * update baris yang sudah ada (selama masih bisa ditulis ulang dari sumber eksternal), keduanya
 * TANPA mutasi stok. Real warehouse id dari seed snapshot: 1 = Gudang Pusat (main).
 */
class SyncShipmentsStepFlowTest extends TestCase
{
    private const MAIN_WAREHOUSE_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'synchronization.pmo.base_url' => 'https://pmo.test',
            'synchronization.pmo.api_key' => 'test-key',
        ]);
    }

    private function createArmada(?string $refArmadaId = null): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Sync Test Armada';
        $customer->customer_code = 'SY'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Test';
        $customer->ref_armada_id = $refArmadaId;
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createUnit(int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Sync Test Unit '.uniqid();
        $unit->unit_short_name = 'ST-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    /** @return array{variant: ProductVariant, sku: string} */
    private function createProductFixture(Unit $unit): array
    {
        $category = new Category();
        $category->category_name = 'Sync Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Sync Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'SYNC-TEST-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Sync Test Variant';
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

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function runSync(array $rows): \App\Synchronization\SyncStepResult
    {
        Http::fake([
            'pmo.test/getShipments*' => Http::response([
                'pagination' => ['total' => count($rows), 'page' => 1, 'limit' => 50, 'total_pages' => 1],
                'items' => $rows,
            ], 200),
        ]);

        $fetch = app(FetchShipmentsStep::class);
        $fetch->fetchPage(1, ['date_start' => '2026-01-01', 'date_end' => '2026-12-31']);
        $fetch->finalize();

        return app(SyncShipmentsStep::class)->handle();
    }

    public function test_inserts_a_new_shipment_as_confirmed_without_touching_stock(): void
    {
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $armada = $this->createArmada('7788990011223344');
        $refShipmentId = 'SYN-'.uniqid();

        $result = $this->runSync([[
            'ref_shipment_id' => $refShipmentId,
            'armada_id' => '7788990011223344',
            'date' => '2026-07-25',
            'bukti_foto' => null,
            'status' => 'onprocess',
            'items' => [
                ['variant_sku' => $fx['sku'], 'qty' => 5, 'unit_id' => $refUnitId],
            ],
        ]]);

        $this->assertSame(SyncStatus::SUCCESS, $result->status);
        $this->assertSame(1, $result->inserted);

        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->firstOrFail();
        $this->assertSame(2, (int) $so->status, 'onprocess must map to internal status 2 (Diterima)');
        $this->assertSame((string) $armada->customer_id, $so->so_customer);
        $this->assertNull($so->pmo_sync_note, 'insert must not set the update-only sync note');

        $detail = SalesOrderDetail::where('so_id', $so->so_id)->firstOrFail();
        $this->assertSame(5, (int) $detail->sod_qty);

        $stock->refresh();
        $this->assertSame(100, (int) $stock->ps_stock, 'Sync must never deduct stock, even for onprocess/status 2');

        $this->assertSame(0, DB::table('sales_delivery_orders')->count(), 'sales_delivery_orders (deprecated module) must not be written anymore');
    }

    public function test_all_five_pmo_status_enums_map_to_the_documented_internal_status(): void
    {
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 100);
        $armada = $this->createArmada('1122334455667788');

        $cases = [
            'onschedule' => 4,
            'onprocess' => 2,
            'pending' => 5,
            'success' => 6,
            'canceled' => 7,
        ];

        $rows = [];
        $refs = [];
        foreach ($cases as $enum => $expectedStatus) {
            $ref = 'SYN-'.$enum.'-'.uniqid();
            $refs[$enum] = $ref;
            $rows[] = [
                'ref_shipment_id' => $ref,
                'armada_id' => '1122334455667788',
                'date' => '2026-07-25',
                'bukti_foto' => null,
                'status' => $enum,
                'items' => [['variant_sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $refUnitId]],
            ];
        }

        $result = $this->runSync($rows);

        $this->assertSame(5, $result->inserted);
        foreach ($cases as $enum => $expectedStatus) {
            $so = SalesOrder::where('ref_shipment_id', $refs[$enum])->firstOrFail();
            $this->assertSame($expectedStatus, (int) $so->status, "enum \"$enum\" must map to status $expectedStatus");
        }
    }

    public function test_unknown_status_enum_fails_the_row_and_creates_nothing(): void
    {
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $this->createArmada('9900112233445566');
        $refShipmentId = 'SYN-'.uniqid();

        $result = $this->runSync([[
            'ref_shipment_id' => $refShipmentId,
            'armada_id' => '9900112233445566',
            'date' => '2026-07-25',
            'status' => 'some_new_status_pmo_added',
            'items' => [['variant_sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $refUnitId]],
        ]]);

        $this->assertSame(1, $result->failed);
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('status PMO', $result->errors[0]);
        $this->assertNull(SalesOrder::where('ref_shipment_id', $refShipmentId)->first());
    }

    public function test_insert_fails_when_armada_id_does_not_resolve(): void
    {
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $refShipmentId = 'SYN-'.uniqid();

        $result = $this->runSync([[
            'ref_shipment_id' => $refShipmentId,
            'armada_id' => '0000000000000000',
            'date' => '2026-07-25',
            'status' => 'onprocess',
            'items' => [['variant_sku' => $fx['sku'], 'qty' => 1, 'unit_id' => $refUnitId]],
        ]]);

        $this->assertSame(1, $result->failed);
        $this->assertNull(SalesOrder::where('ref_shipment_id', $refShipmentId)->first());
    }

    public function test_updates_a_still_pending_shipment_and_sets_the_sync_note(): void
    {
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $stock = $this->createStock($fx['variant'], $unit->unit_id, 100);
        $armada = $this->createArmada('5566778899001122');
        $refShipmentId = 'SYN-'.uniqid();

        // Baris sudah ada, status 1 Pending, belum ada approval sama sekali.
        $so = (new SalesOrder())->insertSalesOrder([
            'so_customer' => (string) $armada->customer_id,
            'so_date' => '2026-07-20',
            'so_total' => 0,
            'so_img' => json_encode([]),
        ]);
        $so->ref_shipment_id = $refShipmentId;
        $so->status = 1;
        $so->save();

        $result = $this->runSync([[
            'ref_shipment_id' => $refShipmentId,
            'armada_id' => '5566778899001122',
            'date' => '2026-07-25',
            'status' => 'success',
            'items' => [['variant_sku' => $fx['sku'], 'qty' => 9, 'unit_id' => $refUnitId]],
        ]]);

        $this->assertSame(1, $result->updated);

        $so->refresh();
        $this->assertSame(6, (int) $so->status);
        $this->assertSame('2026-07-25', $so->so_date);
        $this->assertNotNull($so->pmo_sync_note);
        $this->assertStringContainsString('Sinkronisasi PMO', $so->pmo_sync_note);
        $this->assertNotNull($so->pmo_synced_at);

        $detail = SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)->firstOrFail();
        $this->assertSame(9, (int) $detail->sod_qty);

        $stock->refresh();
        $this->assertSame(100, (int) $stock->ps_stock);
    }

    public function test_skips_a_shipment_that_already_has_qc_approval_instead_of_overwriting_it(): void
    {
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $armada = $this->createArmada('4433221100998877');
        $refShipmentId = 'SYN-'.uniqid();

        $so = (new SalesOrder())->insertSalesOrder([
            'so_customer' => (string) $armada->customer_id,
            'so_date' => '2026-07-20',
            'so_total' => 0,
            'so_img' => json_encode([]),
        ]);
        $so->ref_shipment_id = $refShipmentId;
        $so->status = 1;
        $so->qc_approved_by = 1;
        $so->qc_approved_at = now();
        $so->save();

        $result = $this->runSync([[
            'ref_shipment_id' => $refShipmentId,
            'armada_id' => '4433221100998877',
            'date' => '2026-08-01',
            'status' => 'success',
            'items' => [['variant_sku' => $fx['sku'], 'qty' => 40, 'unit_id' => $refUnitId]],
        ]]);

        $this->assertSame(1, $result->skipped);
        $this->assertSame(0, $result->updated);
        $this->assertNotEmpty($result->notices);

        $so->refresh();
        $this->assertSame(1, (int) $so->status, 'a shipment already touched by approval must not be overwritten by Sync');
        $this->assertSame('2026-07-20', $so->so_date);
        $this->assertNull($so->pmo_synced_at);
    }

    public function test_rejects_an_unknown_variant_sku_per_item_but_keeps_other_rows(): void
    {
        $refUnitId = random_int(900000, 999999);
        $unit = $this->createUnit($refUnitId);
        $fx = $this->createProductFixture($unit);
        $armada = $this->createArmada('3322110099887766');
        $refShipmentId = 'SYN-'.uniqid();

        $result = $this->runSync([[
            'ref_shipment_id' => $refShipmentId,
            'armada_id' => '3322110099887766',
            'date' => '2026-07-25',
            'status' => 'onprocess',
            'items' => [
                ['variant_sku' => 'DOES-NOT-EXIST-'.uniqid(), 'qty' => 1, 'unit_id' => $refUnitId],
                ['variant_sku' => $fx['sku'], 'qty' => 3, 'unit_id' => $refUnitId],
            ],
        ]]);

        $this->assertSame(1, $result->inserted);
        $this->assertNotEmpty($result->errors, 'the unresolvable item must still be reported');

        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->firstOrFail();
        $this->assertSame(1, SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)->count());
    }
}
