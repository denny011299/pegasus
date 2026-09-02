<?php

namespace Tests\Regression;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\ReturnSupplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * QC4: setelah update qty detail dan/atau insert retur, po_total header harus
 * = (subtotal - diskon) + PPN + biaya - retur — sama dengan formula FE detail.
 */
class PurchaseOrderTotalSyncAfterQtyAndReturnTest extends TestCase
{
    use ActingAsStaff;

    private function insertApprovedPoWithDiscount(): array
    {
        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $qty = 10;
        $price = 1000;
        $subtotal = $qty * $price;
        $discountPct = 10;
        $discount = (int) round($subtotal * $discountPct / 100);
        $afterDisc = $subtotal - $discount;
        $ppnPct = 11;
        $ppn = (int) round($afterDisc * $ppnPct / 100);
        $cost = 500;
        $grand = $afterDisc + $ppn + $cost;

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $grand,
            'po_discount' => $discountPct,
            'jenis_discount' => 'persen',
            'po_ppn' => $ppnPct,
            'po_cost' => $cost,
            'po_desc' => 'QC4 total sync test',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'QC4 supplies',
                'supplies_variant_name' => $variant->supplies_variant_name,
                'supplies_variant_sku' => $variant->supplies_variant_sku,
                'qty' => $qty,
                'supplies_variant_price' => $price,
                'unit_id_select' => $stock->unit_id,
            ]]),
        ])->json();

        $this->post('/accPO', [
            'data' => [
                'po_id' => $poId,
                'po_supplier' => $supplier->supplier_id,
                'items' => [[
                    'supplies_variant_id' => $variant->supplies_variant_id,
                    'unit_id' => $stock->unit_id,
                    'pod_sku' => $variant->supplies_variant_sku,
                    'pod_qty' => $qty,
                ]],
            ],
        ])->assertStatus(200);

        return [$poId, $variant, $stock, $grand];
    }

    public function test_update_qty_recalculates_po_total_with_discount_ppn_cost(): void
    {
        $this->actingAsSuperAdminStaff();
        [$poId, $variant, $stock, $initialGrand] = $this->insertApprovedPoWithDiscount();

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame($initialGrand, (int) $po->po_total);

        $detail = PurchaseOrderDetail::where('po_id', $poId)->where('status', 1)->firstOrFail();
        $newQty = 8;
        $newSubtotal = $newQty * (int) $detail->pod_harga;
        $discount = (int) round($newSubtotal * 10 / 100);
        $afterDisc = $newSubtotal - $discount;
        $ppn = (int) round($afterDisc * 11 / 100);
        $expected = $afterDisc + $ppn + 500;

        $detailPayload = $detail->toArray();
        $detailPayload['pod_qty'] = $newQty;
        $detailPayload['pod_subtotal'] = 999999; // sengaja salah — BE harus override

        $response = $this->post('/updatePurchaseOrderDetail', [
            'po_id' => $poId,
            'po_detail' => json_encode([$detailPayload]),
        ])->assertOk()->json();

        $this->assertSame($expected, (int) $response['po_total']);
        $this->assertSame($expected, (int) PurchaseOrder::findOrFail($poId)->po_total);
        $this->assertSame($newQty * (int) $detail->pod_harga, (int) PurchaseOrderDetail::find($detail->pod_id)->pod_subtotal);
    }

    public function test_insert_return_keeps_po_total_in_sync_with_discount_formula(): void
    {
        $this->actingAsSuperAdminStaff();
        [$poId, $variant, $stock, $initialGrand] = $this->insertApprovedPoWithDiscount();

        $returnQty = 2;
        $returnTotal = $returnQty * 1000;
        $expected = $initialGrand - $returnTotal;

        $this->post('/insertReturnSupplies', [
            'po_id' => $poId,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'QC4 retur',
            'rs_total' => $returnTotal,
            'returs' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_id' => $variant->supplies_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'rsd_price' => 1000,
                'rsd_qty' => $returnQty,
                'unit_id' => $stock->unit_id,
            ]]),
        ])->assertOk();

        $this->assertSame($expected, (int) PurchaseOrder::findOrFail($poId)->po_total);
        $this->assertSame(1, ReturnSupplies::where('po_id', $poId)->where('status', 1)->count());
    }
}
