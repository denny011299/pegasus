<?php

namespace Tests\Regression;

use App\Models\PurchaseOrder;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ACC Pembelian harus credit stok di warehouse_id dokumen PO,
 * bukan session active_warehouse (bug tab beda gudang).
 */
class PurchaseOrderAccUsesDocumentWarehouseTest extends TestCase
{
    use ActingAsStaff;

    public function test_acc_po_credits_po_warehouse_even_when_session_is_eceran(): void
    {
        $this->actingAsSuperAdminStaff();

        if (! Schema::hasColumn('purchase_orders', 'warehouse_id')) {
            $this->markTestSkipped('migration warehouse_id belum jalan');
        }

        $mainId = (int) Warehouse::firstMainId();
        $eceranId = (int) Warehouse::query()
            ->active()
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 0))
            ->orderBy('id')
            ->value('id');
        $this->assertGreaterThan(0, $mainId);
        $this->assertGreaterThan(0, $eceranId);
        $this->assertNotSame($mainId, $eceranId);

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stockMain = SuppliesStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $mainId)
            ->where('supplies_id', $variant->supplies_id)
            ->where('status', 1)
            ->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $qty = 3;
        $price = 1000;
        $beforeMain = (int) $stockMain->ss_stock;

        $stockEceran = SuppliesStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $eceranId)
            ->where('supplies_id', $variant->supplies_id)
            ->where('unit_id', $stockMain->unit_id)
            ->where('status', 1)
            ->first();
        $beforeEceran = $stockEceran ? (int) $stockEceran->ss_stock : 0;

        // Buat PO di gudang utama (menu Pembelian), lalu ACC dengan session Eceran.
        $this->withActiveWarehouse($mainId);

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * $price,
            'jenis_discount' => 1,
            'po_desc' => 'Regression PO warehouse bind',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'WH bind test',
                'supplies_variant_name' => $variant->supplies_variant_name,
                'supplies_variant_sku' => $variant->supplies_variant_sku,
                'qty' => $qty,
                'supplies_variant_price' => $price,
                'unit_id_select' => $stockMain->unit_id,
            ]]),
        ])->json();

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame($mainId, (int) $po->warehouse_id, 'PO baru default gudang utama');

        // Simulasi tab gudang salah: session = Eceran saat ACC.
        // Pastikan menu Pembelian boleh di eceran (hidup: bisa whitelist; test seed sering kosong).
        $eceran = Warehouse::findOrFail($eceranId);
        $menus = $eceran->allowedSidebarMenus();
        if (is_array($menus) && ! $eceran->allowsSidebarMenu('Pembelian')) {
            $eceran->sidebar_menus = array_values(array_unique(array_merge($menus, ['Pembelian'])));
            $eceran->save();
        }
        $this->withActiveWarehouse($eceranId);

        $this->post('/accPO', [
            'data' => [
                'po_id' => $poId,
                'po_supplier' => $supplier->supplier_id,
                'items' => [[
                    'supplies_variant_id' => $variant->supplies_variant_id,
                    'unit_id' => $stockMain->unit_id,
                    'pod_sku' => $variant->supplies_variant_sku,
                    'pod_qty' => $qty,
                ]],
            ],
        ])->assertStatus(200);

        $stockMain->refresh();
        $this->assertSame($beforeMain + $qty, (int) $stockMain->ss_stock, 'stok masuk ke gudang PO (utama)');

        if ($stockEceran) {
            $stockEceran->refresh();
            $this->assertSame($beforeEceran, (int) $stockEceran->ss_stock, 'stok eceran tidak boleh berubah');
        }
    }
}
