<?php

namespace Tests\Workflow;

use App\Models\PurchaseOrder;
use App\Models\ReturnSupplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/RETURN_SUPPLIES_FLOW.md for the fully-traced flow this asserts
 * against — builds on the base PurchaseOrderFlowTest pilot. The only flow traced so far that
 * combines a money adjustment (po_total) and a stock adjustment in the same action.
 */
class ReturnSuppliesFlowTest extends TestCase
{
    use ActingAsStaff;

    private function insertAndApprovePo(int $qty, int $price): array
    {
        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * $price,
            'jenis_discount' => 1,
            'po_desc' => 'Return Supplies flow test PO',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'Workflow test supplies',
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

        return [$poId, $variant, $stock];
    }

    public function test_insert_return_reduces_po_total_and_reverses_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        [$poId, $variant, $stock] = $this->insertAndApprovePo(qty: 10, price: 1000);
        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(10000, (int) $po->po_total);

        $stock->refresh();
        $stockAfterApproval = $stock->ss_stock; // already incremented by 10 from accPO

        $returnQty = 3;
        $returnTotal = $returnQty * 1000;
        $logCountBefore = DB::table('log_stocks')->count();

        $response = $this->post('/insertReturnSupplies', [
            'po_id' => $poId,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Workflow test return',
            'rs_total' => $returnTotal,
            'returs' => json_encode([[
                'supplies_id' => $variant->supplies_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'unit_id' => $stock->unit_id,
                'rsd_qty' => $returnQty,
                'rsd_price' => 1000,
            ]]),
        ]);
        $response->assertStatus(200);
        $this->assertSame('1', $response->getContent());

        $po->refresh();
        $this->assertSame(10000 - $returnTotal, (int) $po->po_total, 'a return must reduce po_total by the returned amount');

        $stock->refresh();
        $this->assertSame($stockAfterApproval - $returnQty, $stock->ss_stock, 'a return must decrement supplies stock by the returned qty');

        $this->assertSame($logCountBefore + 1, DB::table('log_stocks')->count());
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $variant->supplies_id,
            'log_jumlah' => $returnQty,
        ]);
        $this->assertDatabaseHas('return_supplies', [
            'po_id' => $poId,
            'rs_total' => $returnTotal,
        ]);
    }

    public function test_insert_return_exceeding_po_total_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        [$poId, $variant, $stock] = $this->insertAndApprovePo(qty: 2, price: 1000); // po_total = 2000
        $po = PurchaseOrder::findOrFail($poId);
        $stock->refresh();
        $stockAfterApproval = $stock->ss_stock;

        $response = $this->post('/insertReturnSupplies', [
            'po_id' => $poId,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Workflow test over-return',
            'rs_total' => 3000,
            'returs' => json_encode([[
                'supplies_id' => $variant->supplies_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'unit_id' => $stock->unit_id,
                'rsd_qty' => 3,
                'rsd_price' => 1000, // 3 * 1000 = 3000 > po_total (2000)
            ]]),
        ]);
        $response->assertJson(['status' => -1]);

        $po->refresh();
        $this->assertSame(2000, (int) $po->po_total, 'a rejected over-limit return must not change po_total');

        $stock->refresh();
        $this->assertSame($stockAfterApproval, $stock->ss_stock, 'a rejected over-limit return must not touch stock');
    }

    public function test_delete_return_restores_po_total_and_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        [$poId, $variant, $stock] = $this->insertAndApprovePo(qty: 10, price: 1000);
        $stock->refresh();
        $stockAfterApproval = $stock->ss_stock;

        $returnQty = 4;
        $this->post('/insertReturnSupplies', [
            'po_id' => $poId,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Workflow test return (to be deleted)',
            'rs_total' => $returnQty * 1000,
            'returs' => json_encode([[
                'supplies_id' => $variant->supplies_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'unit_id' => $stock->unit_id,
                'rsd_qty' => $returnQty,
                'rsd_price' => 1000,
            ]]),
        ])->assertStatus(200);

        $rs = ReturnSupplies::where('po_id', $poId)->firstOrFail();

        $this->post('/deleteReturnSupplies', ['rs_id' => $rs->rs_id, 'po_id' => $poId])->assertStatus(200);

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame(10000, (int) $po->po_total, 'deleting a return must restore po_total exactly');

        $stock->refresh();
        $this->assertSame($stockAfterApproval, $stock->ss_stock, 'deleting a return must restore stock exactly');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 1,
            'log_item_id' => $variant->supplies_id,
            'log_jumlah' => $returnQty,
        ]);
    }
}
