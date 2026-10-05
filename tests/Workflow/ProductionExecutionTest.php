<?php

namespace Tests\Workflow;

use App\Models\{
    Product,
    ProductVariant,
    ProductRelation,
    ProductStock,
    Staff,
    ProductionPlanning,
    ProductionPlanningItem,
    ProductionWorkOrder,
    ProductionOutputReport,
    ProductionExecutionDocument,
};
use App\Support\{ProductionExecution as Execution, ProductUnitStock};
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/** Alur WO: hasil → Form Gudang → QC → Ops → stok + Tally. */
class ProductionExecutionTest extends TestCase
{
    use ActingAsStaff;

    private function fixture(float $targetQty = 24): array
    {
        $actor = $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(1);
        Staff::whereKey($actor->staff_id)->update([
            'signature_data_uri' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg==',
        ]);
        $product = (new Product())->forceFill([
            'product_name' => 'WO test',
            'category_id' => \App\Models\Category::where('status', 1)->value('category_id'),
            'unit_id' => 7,
            'product_unit' => json_encode([7, 9]),
            'status' => 1,
        ]);
        $product->save();
        $variant = (new ProductVariant())->forceFill([
            'product_id' => $product->product_id,
            'product_variant_name' => 'WO test',
            'product_variant_sku' => uniqid('WO'),
            'product_variant_price' => 0,
            'retail_unit' => 9,
            'qty_per_pallet' => 10,
            'status' => 1,
        ]);
        $variant->save();
        (new ProductRelation())->forceFill([
            'product_variant_id' => $variant->product_variant_id,
            'pr_unit_id_1' => 7,
            'pr_unit_value_1' => 1,
            'pr_unit_id_2' => 9,
            'pr_unit_value_2' => 12,
            'pr_default' => 0,
            'status' => 1,
        ])->save();
        $pp = (new ProductionPlanning())->forceFill([
            'pp_number' => uniqid('PP'),
            'pp_date' => now()->toDateString(),
            'warehouse_id' => 1,
            'pp_status' => 'inprod',
            'status' => 1,
        ]);
        $pp->save();
        $wo = (new ProductionWorkOrder())->forceFill([
            'production_planning_id' => $pp->production_planning_id,
            'warehouse_id' => 1,
            'pic_staff_id' => $actor->staff_id,
            'wo_number' => uniqid('WO'),
            'wo_date' => now()->toDateString(),
            'status' => 1,
            'execution_status' => 'inprod',
        ]);
        $wo->save();
        $item = (new ProductionPlanningItem())->forceFill([
            'production_planning_id' => $pp->production_planning_id,
            'production_work_order_id' => $wo->production_work_order_id,
            'product_variant_id' => $variant->product_variant_id,
            'product_name' => 'WO test',
            'qty' => $targetQty,
            'unit_id' => 9,
            'unit_label' => 'PCS',
            'status' => 1,
        ]);
        $item->save();
        ProductUnitStock::clearCache();

        return [$wo, $item, $variant, $pp];
    }

    private function report($wo, $item, $qty, $unit, $key = 'report-key-01'): array
    {
        return Execution::report($wo->production_work_order_id, [
            'request_id' => $key,
            'items' => [['ppi_id' => $item->ppi_id, 'qty' => $qty, 'unit_id' => $unit]],
        ]);
    }

    private function stockPcs($v): float
    {
        return (float) ProductStock::withoutGlobalScopes()
            ->where('warehouse_id', 1)
            ->where('product_variant_id', $v->product_variant_id)
            ->where('status', 1)
            ->get()
            ->sum(fn ($s) => ProductUnitStock::convertQty($s->ps_stock, $s->unit_id, 9, $v->product_variant_id));
    }

    private function fg(int $woId): ?ProductionExecutionDocument
    {
        return ProductionExecutionDocument::where('production_work_order_id', $woId)
            ->where('type', 'warehouse')
            ->first();
    }

    public function test_partial_keeps_inprod_no_fg_no_stock(): void
    {
        [$wo, $item, $v] = $this->fixture(24);
        $r1 = $this->report($wo, $item, 12, 9, 'partial-rep-a');
        $this->assertFalse($r1['production_complete']);
        $this->assertEquals(12, (float) $item->fresh()->actual_qty);
        $this->assertEquals(0, $this->stockPcs($v));
        $this->assertNull($wo->fresh()->production_completed_at);
        $this->assertSame('inprod', $wo->fresh()->execution_status);
        $this->assertNull($this->fg($wo->production_work_order_id));
    }

    public function test_complete_issues_fg_without_stock_then_ops_qc_credits(): void
    {
        [$wo, $item, $v, $pp] = $this->fixture(24);
        $r1 = $this->report($wo, $item, 12, 9, 'partial-rep-a');
        $this->assertFalse($r1['production_complete']);

        $r2 = $this->report($wo, $item, 12, 9, 'partial-rep-b');
        $this->assertTrue($r2['production_complete']);
        $this->assertTrue($r2['wo_done']);
        $this->assertSame('done', $wo->fresh()->execution_status);
        $this->assertNotNull($wo->fresh()->production_completed_at);
        $this->assertNull($wo->fresh()->closed_at);
        $this->assertEquals(0, $this->stockPcs($v));
        // Histori / Selesai = produksi selesai (FG terbit); stok tetap menunggu ACC final.
        $this->assertSame('done', $pp->fresh()->pp_status);

        $fg = $this->fg($wo->production_work_order_id);
        $this->assertNotNull($fg);
        $this->assertSame('awaiting_qc', $fg->document_status);
        $this->assertNull($fg->warehouse_at);
        $this->assertNull($fg->tally_number);

        // Ops sebelum QC ditolak
        try {
            Execution::approve($fg->id, 'ops', []);
            $this->fail('Ops before QC accepted');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('berurutan', $e->getMessage());
        }

        $qc = Execution::approve($fg->id, 'qc', []);
        $this->assertSame(1, $qc['status']);
        $fg = $fg->fresh();
        $this->assertSame('awaiting_ops', $fg->document_status);
        $this->assertNotNull($fg->qc_approved_at);
        $this->assertNull($fg->warehouse_at);
        $this->assertEquals(0, $this->stockPcs($v));

        $ops = Execution::approve($fg->id, 'ops', []);
        $this->assertSame(1, $ops['status']);
        $this->assertNotEmpty($ops['tally_number']);
        $fg = $fg->fresh();
        $this->assertSame('approved', $fg->document_status);
        $this->assertNotNull($fg->warehouse_at);
        $this->assertNotNull($fg->tally_number);
        $this->assertEquals(24, $this->stockPcs($v));
        $this->assertNotNull($wo->fresh()->closed_at);
        $this->assertSame('done', $pp->fresh()->pp_status);
    }

    public function test_pallet_unit_issues_fg_then_ops_credits_stock(): void
    {
        [$wo, $item, $v, $pp] = $this->fixture(120);
        $r = $this->report($wo, $item, 1, 'pallet', 'pallet-done1');
        $this->assertTrue($r['wo_done']);
        $this->assertSame('done', $wo->fresh()->execution_status);
        $this->assertEquals(0, $this->stockPcs($v));
        $fg = $this->fg($wo->production_work_order_id);
        $this->assertNotNull($fg);
        Execution::approve($fg->id, 'qc', []);
        Execution::approve($fg->id, 'ops', []);
        $this->assertEquals(120, $this->stockPcs($v));
        $this->assertSame('done', $pp->fresh()->pp_status);
        $this->assertSame(1, ProductionOutputReport::where('production_work_order_id', $wo->production_work_order_id)->count());
    }

    public function test_invalid_unit_and_missing_signature_do_not_create_output(): void
    {
        [$wo, $item] = $this->fixture();
        try {
            $this->report($wo, $item, 1, 999999, 'bad-unit-key1');
            $this->fail('Unknown unit accepted');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Satuan', $e->getMessage());
        }
        Staff::whereKey(session('user')->staff_id)->update(['signature_data_uri' => null]);
        try {
            $this->report($wo, $item, 1, 'pallet', 'no-sign-key01');
            $this->fail('Missing signature accepted');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('tanda tangan', $e->getMessage());
        }
        $this->assertSame(0, ProductionOutputReport::where('production_work_order_id', $wo->production_work_order_id)->count());
    }

    public function test_listing_and_monitor_routes(): void
    {
        [$wo] = $this->fixture();
        $this->withActiveWarehouse(2);
        $this->get('/productionWorkOrders/'.$wo->production_work_order_id)->assertForbidden();
        $this->withActiveWarehouse(1);
        $this->get('/getProductionWorkOrders?draw=1&start=0&length=10')->assertOk()->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
        $this->get('/productionWorkOrders')->assertRedirect(route('productionPlanning', ['tab' => 'job']));
        $this->get('/productionWorkOrders?monitor=1')->assertOk();
    }
}
