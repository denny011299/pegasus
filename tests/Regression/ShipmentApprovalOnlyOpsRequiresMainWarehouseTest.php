<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Support\RoleIds;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\Support\ResolvesTestWarehouses;
use Tests\TestCase;

/**
 * ✅ Koreksi keputusan 2026-09-23 (lihat
 * cdocs/docs/specs/shipment-external-api-approval-flow.md §4.2): keputusan awal mewajibkan gudang
 * aktif = gudang utama untuk KEDUA tahap approval Pengiriman ternyata salah — hanya tahap Kepala
 * Operasional (Ops) yang wajib gudang utama. Tahap Staf QC & Gudang (QC) boleh approve dari
 * gudang aktif mana pun.
 *
 * App\Support\ShipmentApproval::isAtWarehouseForApproval() sekarang hanya dipanggil untuk tahap
 * Ops di CustomerController::approveShipment()/rejectShipment() dan
 * SalesOrder::getSalesOrderDataTable() (can_approve_ops).
 */
class ShipmentApprovalOnlyOpsRequiresMainWarehouseTest extends TestCase
{
    use ActingAsStaff;
    use ResolvesTestWarehouses;

    private const MAIN_WAREHOUSE_ID = 1;

    private function customerId(): int
    {
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    private function createPendingShipment(): int
    {
        $category = new Category();
        $category->category_name = 'ShipmentApprovalWarehouseGate Category';
        $category->status = 1;
        $category->save();

        $unit = new Unit();
        $unit->unit_name = 'ShipmentApprovalWarehouseGate Unit '.uniqid();
        $unit->unit_short_name = 'SG-'.random_int(1000, 9999);
        $unit->status = 1;
        $unit->save();

        $product = new Product();
        $product->product_name = 'ShipmentApprovalWarehouseGate Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'ShipmentApprovalWarehouseGate Variant';
        $variant->product_variant_sku = 'SAWG-TEST-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        ProductStock::withoutGlobalScope('active_warehouse')->create([
            'product_id' => $product->product_id,
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => $unit->unit_id,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
            'ps_stock' => 100,
            'status' => 1,
        ]);

        $customer = new Customer();
        $customer->customer_name = 'ShipmentApprovalWarehouseGate Armada';
        $customer->customer_code = 'SAWG'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Test';
        $customer->status = 1;
        $customer->save();

        $this->actingAsSuperAdminStaff();
        config(['pegasus.shipment_internal_insert_enabled' => true]);

        $response = $this->post('/insertSalesOrder', [
            'so_customer' => $customer->customer_id,
            'so_date' => now()->toDateString(),
            'so_total' => 5000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'product_variant_id' => $variant->product_variant_id,
                'pr_name' => $product->product_name,
                'product_variant_name' => $variant->product_variant_name,
                'product_variant_sku' => $variant->product_variant_sku,
                'unit_id' => $unit->unit_id,
                'product_variant_price' => 1000,
                'so_qty' => 5,
                'so_subtotal' => 5000,
            ]]),
        ]);
        $response->assertStatus(200);
        $this->assertSame('1', trim($response->getContent(), '"'));

        return (int) SalesOrder::orderByDesc('so_id')->value('so_id');
    }

    public function test_qc_can_approve_from_a_non_main_warehouse(): void
    {
        $soId = $this->createPendingShipment();
        $retailWarehouseId = $this->resolveActiveRetailWarehouseId('Pengiriman');

        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view'], ['role_id' => RoleIds::DIREKSI]);
        $this->withActiveWarehouse($retailWarehouseId);

        $response = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'qc']);

        $response->assertStatus(200)->assertJson(['status' => 1]);
        $so = SalesOrder::findOrFail($soId);
        $this->assertNotNull($so->qc_approved_by);
        $this->assertSame(1, (int) $so->status, 'QC approval alone must not finalize/confirm the shipment');
    }

    public function test_ops_is_refused_from_a_non_main_warehouse_then_succeeds_after_switching(): void
    {
        $soId = $this->createPendingShipment();
        $retailWarehouseId = $this->resolveActiveRetailWarehouseId('Pengiriman');

        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view'], ['role_id' => RoleIds::DIREKSI]);
        $this->withActiveWarehouse($retailWarehouseId);
        $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'qc'])->assertStatus(200)->assertJson(['status' => 1]);

        // Masih di gudang eceran — tahap Ops harus ditolak.
        $refused = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops']);
        $refused->assertStatus(200);
        $this->assertSame(-1, (int) $refused->json('status'));
        $this->assertStringContainsString('gudang utama', (string) $refused->json('message'));

        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(1, (int) $so->status);
        $this->assertNull($so->ops_approved_by);

        // Pindah ke gudang utama — sekarang harus berhasil.
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);
        $accepted = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops']);
        $accepted->assertStatus(200)->assertJson(['status' => 1]);

        $so->refresh();
        $this->assertSame(2, (int) $so->status);
    }

    public function test_reject_at_qc_stage_also_does_not_require_main_warehouse(): void
    {
        $soId = $this->createPendingShipment();
        $retailWarehouseId = $this->resolveActiveRetailWarehouseId('Pengiriman');

        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view'], ['role_id' => RoleIds::DIREKSI]);
        $this->withActiveWarehouse($retailWarehouseId);

        $response = $this->post('/rejectShipment', ['so_id' => $soId, 'type' => 'qc', 'reason' => 'Test reject']);

        $response->assertStatus(200)->assertJson(['status' => 1]);
        $this->assertSame(3, (int) SalesOrder::findOrFail($soId)->status);
    }
}
