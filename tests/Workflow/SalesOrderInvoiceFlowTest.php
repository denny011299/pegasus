<?php

namespace Tests\Workflow;

use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetailInvoice;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/SALES_ORDER_INVOICE_FLOW.md for the fully-traced flow this asserts
 * against. Built specifically to check whether this mirrors Purchase Order's invoice over-payment
 * guard (cdocs/testing/workflows/PURCHASE_ORDER_INVOICE_FLOW.md) — it doesn't. These tests confirm
 * that gap is real: accepting invoices that together exceed so_total succeeds anyway, and
 * sales_orders.status is never recalculated as a side effect (unlike purchase_orders.status via
 * PurchaseOrderDetailInvoice::cekInvoice()). See cdocs/testing/KNOWN_ISSUES.md for the disposition.
 */
class SalesOrderInvoiceFlowTest extends TestCase
{
    use ActingAsStaff;

    private function customerId(): int
    {
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    private function pickFixtureStock(): ProductStock
    {
        $variantIdsWithoutRetailUnit = DB::table('product_variants')->whereNull('retail_unit')->pluck('product_variant_id');

        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->where('ps_stock', '>', 20)
            ->whereIn('product_variant_id', $variantIdsWithoutRetailUnit)
            ->firstOrFail();
    }

    private function insertAndApproveSo(int $qty): int
    {
        $stock = $this->pickFixtureStock();

        $response = $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => $qty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'pr_name' => 'Invoice flow test product',
                'product_variant_name' => 'Test Variant',
                'product_variant_sku' => 'WF-INV-SKU',
                'unit_id' => $stock->unit_id,
                'product_variant_price' => 1000,
                'so_qty' => $qty,
                'so_subtotal' => $qty * 1000,
            ]]),
        ]);
        $response->assertStatus(200);
        $this->assertSame('1', $response->getContent());

        $soId = (int) SalesOrder::orderByDesc('so_id')->value('so_id');
        $this->post('/accSO', ['so_id' => $soId])->assertStatus(200);

        return $soId;
    }

    public function test_accepting_invoices_that_together_exceed_so_total_is_currently_allowed(): void
    {
        $this->actingAsSuperAdminStaff();

        $soId = $this->insertAndApproveSo(qty: 5); // so_total = 5000
        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(2, (int) $so->status);

        $firstPoiId = (int) $this->post('/insertInvoiceSO', [
            'so_id' => $soId,
            'soi_date' => now()->toDateString(),
            'soi_due' => now()->addDays(14)->toDateString(),
            'soi_total' => 4000,
        ])->getContent();

        $secondPoiId = (int) $this->post('/insertInvoiceSO', [
            'so_id' => $soId,
            'soi_date' => now()->toDateString(),
            'soi_due' => now()->addDays(14)->toDateString(),
            'soi_total' => 4000, // 4000 + 4000 = 8000, already more than so_total (5000)
        ])->getContent();

        $this->post('/acceptInvoiceSO', ['soi_id' => $firstPoiId, 'status' => 2])->assertOk();
        $this->post('/acceptInvoiceSO', ['soi_id' => $secondPoiId, 'status' => 2])->assertOk();

        $first = SalesOrderDetailInvoice::findOrFail($firstPoiId);
        $second = SalesOrderDetailInvoice::findOrFail($secondPoiId);
        $this->assertSame(2, (int) $first->status, 'nothing blocks accepting the first invoice');
        $this->assertSame(2, (int) $second->status, 'nothing blocks accepting the second invoice either, despite the combined total exceeding so_total');

        $so->refresh();
        $this->assertSame(2, (int) $so->status, 'unlike Purchase Order, accepting Sales Order invoices never recalculates sales_orders.status');
    }

    public function test_decline_an_invoice_is_an_unconditional_status_flip(): void
    {
        $this->actingAsSuperAdminStaff();

        $soId = $this->insertAndApproveSo(qty: 3);

        $poiId = (int) $this->post('/insertInvoiceSO', [
            'so_id' => $soId,
            'soi_date' => now()->toDateString(),
            'soi_due' => now()->addDays(14)->toDateString(),
            'soi_total' => 3000,
        ])->getContent();

        $this->post('/declineInvoiceSO', ['soi_id' => $poiId, 'status' => 0])->assertOk();

        $invoice = SalesOrderDetailInvoice::findOrFail($poiId);
        $this->assertSame(0, (int) $invoice->status);

        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(2, (int) $so->status, 'declining an SO invoice does not touch sales_orders.status either');
    }
}
