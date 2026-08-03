<?php

namespace Tests\Workflow;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Pilot Workflow test. See cdocs/testing/workflows/PURCHASE_ORDER_FLOW.md for the fully-traced
 * flow this asserts against — Insert -> Approve -> Reject-after-approval is the part of the
 * Purchase Order lifecycle that touches supplies_stocks + log_stocks + the payable/Hutang record.
 * Tanda Terima (payment batching), manual delivery batches, and Return Supplies are separate
 * sub-flows, deliberately out of scope for this pilot (see the doc for why).
 */
class PurchaseOrderFlowTest extends TestCase
{
    use ActingAsStaff;

    public function test_insert_then_approve_then_reject_round_trips_stock_and_log(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)
            ->where('status', 1)
            ->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $qty = 5;
        $price = 1000;
        $startingStock = $stock->ss_stock;
        $logCountBefore = DB::table('log_stocks')->count();

        // --- 1. Insert: creates the PO header + detail, no stock/log movement yet ---
        $insertResponse = $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * $price,
            'jenis_discount' => 1,
            'po_desc' => 'Workflow test PO',
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
        ]);
        $insertResponse->assertStatus(200);
        $poId = (int) $insertResponse->json();

        $po = PurchaseOrder::find($poId);
        $this->assertNotNull($po);
        $this->assertSame(1, (int) $po->status, 'a freshly inserted PO should be pending approval');
        $this->assertDatabaseHas('purchase_orders_details', [
            'po_id' => $poId,
            'supplies_variant_id' => $variant->supplies_variant_id,
            'pod_qty' => $qty,
        ]);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'inserting a PO must not touch stock before approval');
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'inserting a PO must not write a log_stocks row');

        // --- 2. Approve: stock increment + log_stocks + invoice, all in one action ---
        $accResponse = $this->post('/accPO', [
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
        ]);
        $accResponse->assertStatus(200);

        $po->refresh();
        $this->assertSame(2, (int) $po->status, 'approving a PO sets status to 2');
        $this->assertNotNull($po->acc_by);

        $stock->refresh();
        $this->assertSame($startingStock + $qty, $stock->ss_stock, 'approval must increment supplies stock by the ordered qty');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 1,
            'log_item_id' => $variant->supplies_id,
            'log_jumlah' => $qty,
        ]);

        $invoice = PurchaseOrderDetailInvoice::where('po_id', $poId)->first();
        $this->assertNotNull($invoice, 'approval must create exactly one payable/Hutang invoice row');
        $this->assertEquals($po->po_total, $invoice->poi_total);
        $this->assertSame(1, (int) $invoice->status);

        // --- 3. Reject an already-approved PO: must reverse stock and cascade-cancel ---
        $tolakResponse = $this->post('/tolakPO', ['po_id' => $poId]);
        $tolakResponse->assertStatus(200);

        $po->refresh();
        $this->assertSame(-1, (int) $po->status, 'rejecting an approved PO sets status to -1');

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'rejecting an approved PO must reverse the stock increment exactly');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $variant->supplies_id,
            'log_jumlah' => $qty,
        ]);

        $invoice->refresh();
        $this->assertSame(0, (int) $invoice->status, 'rejecting an approved PO must cancel its invoice');
    }

    public function test_reject_blocks_when_reversal_would_make_stock_negative(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)
            ->where('status', 1)
            ->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $qty = 5;

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * 1000,
            'jenis_discount' => 1,
            'po_desc' => 'Workflow test PO (negative-stock guard)',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'Workflow test supplies',
                'supplies_variant_name' => $variant->supplies_variant_name,
                'supplies_variant_sku' => $variant->supplies_variant_sku,
                'qty' => $qty,
                'supplies_variant_price' => 1000,
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

        // Drain the stock below what a reversal would need, simulating it having been
        // consumed elsewhere (e.g. production) before this PO gets rejected.
        $stock->refresh();
        $stock->ss_stock = $qty - 1;
        $stock->save();

        $tolakResponse = $this->post('/tolakPO', ['po_id' => $poId]);
        $tolakResponse->assertJson(['status' => -1]);

        $po = PurchaseOrder::find($poId);
        $this->assertSame(2, (int) $po->status, 'a blocked rejection must leave the PO approved, not partially rejected');

        $stock->refresh();
        $this->assertSame($qty - 1, $stock->ss_stock, 'a blocked rejection must not touch stock at all');
    }
}
