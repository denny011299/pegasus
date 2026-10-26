<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Staff;
use App\Models\StaffWarehouse;
use App\Models\Unit;
use App\Support\RoleIds;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Paritas aktor approval Pengiriman dengan Stock Transfer
 * (App\Support\ShipmentApproval::resolveActorRole, pola StockTransferApproval):
 * QC = role Staf QC & Gudang ter-assign ke gudang utama; Kepala Ops = flag
 * is_kepala_cabang di gudang utama; Direksi/Developer boleh menggantikan
 * keduanya secara berurutan; selain itu ditolak.
 */
class ShipmentApprovalActorParityTest extends TestCase
{
    use ActingAsStaff;

    private const MAIN_WAREHOUSE_ID = 1;

    private function createPendingShipment(): int
    {
        $category = new Category();
        $category->category_name = 'ActorParity Category';
        $category->status = 1;
        $category->save();

        $unit = new Unit();
        $unit->unit_name = 'ActorParity Unit '.uniqid();
        $unit->unit_short_name = 'AP-'.random_int(1000, 9999);
        $unit->status = 1;
        $unit->save();

        $product = new Product();
        $product->product_name = 'ActorParity Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'ActorParity Variant';
        $variant->product_variant_sku = 'AP-TEST-'.uniqid();
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
        $customer->customer_name = 'ActorParity Armada';
        $customer->customer_code = 'AP'.random_int(1000, 9999);
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

    /** Staf baru (bukan staf pertama DB) agar assignment gudang tidak bocor antar aktor. */
    private function actAsNewStaff(int $roleId, bool $assignMain = false, bool $kepala = false): Staff
    {
        $staff = new Staff();
        $staff->staff_name = 'ActorParity Staff '.uniqid();
        $staff->role_id = $roleId;
        $staff->status = 1;
        $staff->save();

        if ($assignMain) {
            $row = new StaffWarehouse();
            $row->staff_id = (int) $staff->staff_id;
            $row->warehouse_id = self::MAIN_WAREHOUSE_ID;
            $row->is_kepala_cabang = $kepala ? 1 : 0;
            $row->save();
        }

        $staff->role_name = Role::find($roleId)->role_name ?? 'Test';
        $staff->role_access = json_encode([['name' => 'Pengiriman', 'akses' => ['view']]]);
        session(['user' => $staff]);
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        return $staff;
    }

    private function approveQc(int $soId)
    {
        return $this->post('/approveShipment', [
            'so_id' => $soId, 'type' => 'qc',
            'confirm_items' => $this->shipmentConfirmItems($soId),
        ]);
    }

    public function test_assigned_qc_staff_can_approve_qc_but_not_ops(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsNewStaff(RoleIds::QC_GUDANG, assignMain: true);

        $this->approveQc($soId)->assertStatus(200)->assertJson(['status' => 1]);
        $this->assertNotNull(SalesOrder::findOrFail($soId)->qc_approved_by);

        $ops = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops']);
        $ops->assertStatus(200);
        $this->assertSame(-1, (int) $ops->json('status'), 'Staf QC murni tidak boleh menggantikan Kepala Ops');
        $this->assertNull(SalesOrder::findOrFail($soId)->ops_approved_by);
    }

    public function test_kepala_flagged_staff_can_approve_ops_after_qc(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsNewStaff(RoleIds::QC_GUDANG, assignMain: true);
        $this->approveQc($soId)->assertJson(['status' => 1]);

        $this->actAsNewStaff(6, assignMain: true, kepala: true); // Kepala Operasional + flag kepala
        $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops'])
            ->assertStatus(200)->assertJson(['status' => 1]);

        $so = SalesOrder::findOrFail($soId);
        $this->assertNotNull($so->ops_approved_by);
        $this->assertSame(2, (int) $so->status);
    }

    public function test_direksi_can_substitute_qc_then_ops_sequentially_but_not_skip(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsNewStaff(RoleIds::DIREKSI);

        $skip = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops']);
        $this->assertSame(-1, (int) $skip->json('status'), 'Ops tidak boleh melompati QC');

        $this->approveQc($soId)->assertJson(['status' => 1]);
        $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops'])
            ->assertStatus(200)->assertJson(['status' => 1]);
        $this->assertSame(2, (int) SalesOrder::findOrFail($soId)->status);
    }

    public function test_developer_can_substitute_qc(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsNewStaff(RoleIds::DEVELOPER);

        $this->approveQc($soId)->assertStatus(200)->assertJson(['status' => 1]);
        $this->assertNotNull(SalesOrder::findOrFail($soId)->qc_approved_by);
    }

    public function test_dual_role_staff_does_qc_then_ops(): void
    {
        $soId = $this->createPendingShipment();
        // Role QC + assignment + flag kepala pada orang yang sama.
        $this->actAsNewStaff(RoleIds::QC_GUDANG, assignMain: true, kepala: true);

        $this->approveQc($soId)->assertJson(['status' => 1]);
        $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'ops'])
            ->assertStatus(200)->assertJson(['status' => 1]);
        $this->assertSame(2, (int) SalesOrder::findOrFail($soId)->status);
    }

    public function test_qc_role_without_warehouse_assignment_is_rejected(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsNewStaff(RoleIds::QC_GUDANG, assignMain: false);

        $response = $this->approveQc($soId);
        $response->assertStatus(200);
        $this->assertSame(-1, (int) $response->json('status'));
        $this->assertNull(SalesOrder::findOrFail($soId)->qc_approved_by);
    }

    public function test_unrelated_role_cannot_approve_qc(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsNewStaff(3); // Sales

        $response = $this->approveQc($soId);
        $response->assertStatus(200);
        $this->assertSame(-1, (int) $response->json('status'));
        $this->assertNull(SalesOrder::findOrFail($soId)->qc_approved_by);
    }
}
