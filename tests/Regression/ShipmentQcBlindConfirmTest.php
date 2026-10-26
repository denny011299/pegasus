<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Unit;
use App\Support\RoleIds;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Konfirmasi Pengiriman tahap QC bersifat buta: staf wajib mengirim hasil hitung
 * fisik (confirm_items) dan server mencocokkannya dengan sales_order_details
 * sebelum approval dicatat (CustomerController::validateShipmentConfirmItems()).
 */
class ShipmentQcBlindConfirmTest extends TestCase
{
    use ActingAsStaff;

    private const MAIN_WAREHOUSE_ID = 1;

    private function createPendingShipment(): int
    {
        $category = new Category();
        $category->category_name = 'QcBlindConfirm Category';
        $category->status = 1;
        $category->save();

        $unit = new Unit();
        $unit->unit_name = 'QcBlindConfirm Unit '.uniqid();
        $unit->unit_short_name = 'QB-'.random_int(1000, 9999);
        $unit->status = 1;
        $unit->save();

        $product = new Product();
        $product->product_name = 'QcBlindConfirm Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'QcBlindConfirm Variant';
        $variant->product_variant_sku = 'QB-TEST-'.uniqid();
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
        $customer->customer_name = 'QcBlindConfirm Armada';
        $customer->customer_code = 'QB'.random_int(1000, 9999);
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

    private function actAsQcApprover(): void
    {
        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view'], ['role_id' => RoleIds::DIREKSI]);
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);
    }

    public function test_qc_without_confirm_items_is_rejected(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsQcApprover();

        $response = $this->post('/approveShipment', ['so_id' => $soId, 'type' => 'qc']);

        $response->assertStatus(200);
        $this->assertSame(-1, (int) $response->json('status'));
        $this->assertNull(SalesOrder::findOrFail($soId)->qc_approved_by);
    }

    public function test_qc_with_wrong_physical_qty_is_rejected(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsQcApprover();

        $items = $this->shipmentConfirmItems($soId);
        $items[0]['qty'] = 4; // data asli 5
        $sodId = (int) SalesOrderDetail::where('so_id', $soId)->value('sod_id');

        $response = $this->post('/approveShipment', [
            'so_id' => $soId, 'type' => 'qc', 'confirm_items' => $items,
        ]);

        $response->assertStatus(200);
        $this->assertSame(-1, (int) $response->json('status'));
        $this->assertContains($sodId, array_map('intval', $response->json('confirm_mismatch_sod_ids') ?? []));
        $this->assertNull(SalesOrder::findOrFail($soId)->qc_approved_by);
    }

    public function test_qc_with_incomplete_confirm_items_is_rejected(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsQcApprover();

        $items = $this->shipmentConfirmItems($soId);
        $items[0]['qty'] = 0;
        $sodId = (int) SalesOrderDetail::where('so_id', $soId)->value('sod_id');

        $response = $this->post('/approveShipment', [
            'so_id' => $soId, 'type' => 'qc', 'confirm_items' => $items,
        ]);

        $response->assertStatus(200);
        $this->assertSame(-1, (int) $response->json('status'));
        $this->assertContains($sodId, array_map('intval', $response->json('confirm_invalid_sod_ids') ?? []));
        $this->assertNull(SalesOrder::findOrFail($soId)->qc_approved_by);
    }

    public function test_confirm_units_follow_variant_conversion_chain(): void
    {
        $soId = $this->createPendingShipment();
        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();

        // Tanpa relasi konversi: hanya satuan asli baris yang bisa dipilih.
        $so = (new SalesOrder())->getSalesOrder(['so_id' => $soId])->first();
        $units = collect($so->items[0]->confirm_units)->pluck('unit_id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame([(int) $detail->unit_id], $units);

        // Tambah relasi konversi varian → satuan rantai ikut muncul.
        $other = new Unit();
        $other->unit_name = 'QcBlindConfirm Unit2 '.uniqid();
        $other->unit_short_name = 'QB2-'.random_int(1000, 9999);
        $other->status = 1;
        $other->save();

        $rel = new \App\Models\ProductRelation();
        $rel->product_variant_id = (int) $detail->product_variant_id;
        $rel->pr_unit_id_1 = (int) $detail->unit_id;
        $rel->pr_unit_value_1 = 1;
        $rel->pr_unit_id_2 = (int) $other->unit_id;
        $rel->pr_unit_value_2 = 12;
        $rel->status = 1;
        $rel->save();
        \App\Support\ProductUnitStock::clearCache(); // cache relasi per proses

        $so = (new SalesOrder())->getSalesOrder(['so_id' => $soId])->first();
        $units = collect($so->items[0]->confirm_units)->pluck('unit_id')->map(fn ($id) => (int) $id)->all();
        $this->assertEqualsCanonicalizing([(int) $detail->unit_id, (int) $other->unit_id], $units);
    }

    public function test_print_shortage_form_when_document_exists(): void
    {
        $soId = $this->createPendingShipment();
        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();

        \App\Models\ShipmentShortageDocument::createForShortage($soId, 'REF-TEST-1', [[
            'sku' => (string) $detail->sod_sku,
            'unit_id' => (int) $detail->unit_id,
            'requested' => 5,
            'available' => 3,
            'shortage' => 2,
        ]], null);

        // Flag untuk tombol print di list Pengiriman.
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);
        $table = (new SalesOrder())->getSalesOrderDataTable([
            'draw' => 1, 'start' => 0, 'length' => 10,
            'search' => ['value' => ''], 'order' => [['column' => 4, 'dir' => 'asc']],
            'active_warehouse_id' => self::MAIN_WAREHOUSE_ID,
        ]);
        $row = collect($table['data'])->firstWhere('so_id', $soId);
        $this->assertTrue($row['has_shortage_doc'] ?? false);

        // Print hanya menghasilkan PDF (tidak membuat Production Planning).
        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view'], ['role_id' => RoleIds::DIREKSI]);
        $response = $this->get('/shipmentShortage/'.$soId.'/print');
        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_qc_with_matching_physical_count_is_approved(): void
    {
        $soId = $this->createPendingShipment();
        $this->actAsQcApprover();

        $response = $this->post('/approveShipment', [
            'so_id' => $soId, 'type' => 'qc',
            'confirm_items' => $this->shipmentConfirmItems($soId),
        ]);

        $response->assertStatus(200)->assertJson(['status' => 1]);
        $so = SalesOrder::findOrFail($soId);
        $this->assertNotNull($so->qc_approved_by);
        $this->assertSame(1, (int) $so->status, 'QC saja belum final — menunggu Ops');
    }
}
