<?php

namespace Tests\Regression;

use App\Models\{
    Bom,
    BomDetail,
    ProductionExecutionDocument,
    ProductionPlanning,
    ProductionPlanningItem,
    ProductionWorkOrder,
    Staff,
    Supplies,
    SuppliesStock,
    Unit,
};
use App\Support\ProductionExecution as Execution;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/** WO Ambil Bahan: saran resep + cek stok saat request + potong saat QC. */
class WorkOrderMaterialIssueTest extends TestCase
{
    use ActingAsStaff;

    private function fixture(): array
    {
        $actor = $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(1);
        Staff::whereKey($actor->staff_id)->update([
            'signature_data_uri' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg==',
        ]);

        $unitId = (int) (Unit::where('status', 1)->value('unit_id') ?: 0);
        $this->assertGreaterThan(0, $unitId);

        $categoryId = (int) (\App\Models\Category::where('status', 1)->value('category_id') ?: 0);
        $this->assertGreaterThan(0, $categoryId);

        $product = new \App\Models\Product();
        $product->product_name = 'WO Mat Prod '.uniqid();
        $product->category_id = $categoryId;
        $product->product_unit = json_encode([$unitId]);
        $product->unit_id = $unitId;
        $product->status = 1;
        $product->save();

        $variant = new \App\Models\ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Var '.uniqid();
        $variant->product_variant_sku = 'WOM-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->unit_id = $unitId;
        $variant->status = 1;
        $variant->save();
        $variantId = (int) $variant->product_variant_id;

        $supply = new Supplies();
        $supply->supplies_name = 'WO Mat '.uniqid();
        $supply->supplies_unit = json_encode([$unitId]);
        $supply->supplies_default_unit = $unitId;
        $supply->supplies_alert = 0;
        $supply->status = 1;
        if (Supplies::hasKindColumn()) {
            $supply->supplies_kind = Supplies::KIND_SUPPLY;
        }
        $supply->save();

        $ss = new SuppliesStock();
        $ss->supplies_id = $supply->supplies_id;
        $ss->unit_id = $unitId;
        $ss->warehouse_id = 1;
        $ss->ss_stock = 100;
        $ss->status = 1;
        $ss->save();

        $bom = new Bom();
        $bom->product_id = $variantId;
        $bom->bom_qty = 1;
        $bom->unit_id = $unitId;
        $bom->status = 1;
        $bom->save();

        $bd = new BomDetail();
        $bd->bom_id = $bom->bom_id;
        $bd->supplies_id = $supply->supplies_id;
        $bd->unit_id = $unitId;
        $bd->bom_detail_qty = 2;
        $bd->status = 1;
        $bd->save();

        $pp = new ProductionPlanning();
        $pp->pp_number = uniqid('PP');
        $pp->pp_date = now()->toDateString();
        $pp->warehouse_id = 1;
        $pp->pp_status = 'inprod';
        $pp->status = 1;
        $pp->save();

        $wo = new ProductionWorkOrder();
        $wo->production_planning_id = $pp->production_planning_id;
        $wo->warehouse_id = 1;
        $wo->pic_staff_id = $actor->staff_id;
        $wo->wo_number = uniqid('WO');
        $wo->wo_date = now()->toDateString();
        $wo->status = 1;
        $wo->execution_status = 'inprod';
        $wo->save();

        $item = new ProductionPlanningItem();
        $item->production_planning_id = $pp->production_planning_id;
        $item->production_work_order_id = $wo->production_work_order_id;
        $item->product_variant_id = $variantId;
        $item->product_name = 'WO Mat Product';
        $item->qty = 5;
        $item->unit_id = $unitId;
        $item->unit_label = 'U';
        $item->status = 1;
        $item->save();

        return compact('actor', 'wo', 'supply', 'ss', 'unitId', 'bom');
    }

    public function test_recipe_suggests_bom_qty_and_rejects_overstock(): void
    {
        $fx = $this->fixture();
        $recipe = Execution::materialRecipe($fx['wo']->production_work_order_id);
        $this->assertSame(
            [],
            $recipe['missing_bom'] ?? [],
            'BOM harus ketemu. items='.json_encode($recipe['items'] ?? [])
        );
        $hit = collect($recipe['items'])->firstWhere('supplies_id', $fx['supply']->supplies_id);
        $this->assertNotNull($hit, 'items='.json_encode($recipe['items'] ?? []));
        $this->assertGreaterThan(0, (float) $hit['suggest_qty']);
        $this->assertEqualsWithDelta(100.0, (float) $hit['available'], 0.01);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stok tidak cukup/');
        Execution::requestMaterial($fx['wo']->production_work_order_id, 'material_issue', [
            'request_id' => 'wo-mat-overstock-'.uniqid(),
            'items' => [[
                'supplies_id' => $fx['supply']->supplies_id,
                'unit_id' => $fx['unitId'],
                'qty' => 999,
            ]],
        ]);
    }

    public function test_material_issue_then_qc_deducts_stock(): void
    {
        $fx = $this->fixture();
        $req = Execution::requestMaterial($fx['wo']->production_work_order_id, 'material_issue', [
            'request_id' => 'wo-mat-ok-'.uniqid(),
            'items' => [[
                'supplies_id' => $fx['supply']->supplies_id,
                'unit_id' => $fx['unitId'],
                'qty' => 10,
            ]],
        ]);
        $this->assertSame(1, (int) $req['status']);
        $docId = (int) $req['document_id'];
        $doc = ProductionExecutionDocument::find($docId);
        $this->assertSame('awaiting_qc', $doc->document_status);

        $accQc = Execution::approve($docId, 'qc', ['received' => [0 => 10]]);
        $this->assertSame(1, (int) $accQc['status']);
        $doc->refresh();
        $this->assertSame('awaiting_ops', $doc->document_status);
        $fx['ss']->refresh();
        $this->assertEqualsWithDelta(100.0, (float) $fx['ss']->ss_stock, 0.01, 'Stok belum dipotong sebelum ACC Ops');

        $accOps = Execution::approve($docId, 'ops', []);
        $this->assertSame(1, (int) $accOps['status']);
        $fx['ss']->refresh();
        $this->assertEqualsWithDelta(90.0, (float) $fx['ss']->ss_stock, 0.01);
    }
}
