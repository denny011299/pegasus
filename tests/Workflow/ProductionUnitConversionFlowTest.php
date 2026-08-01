<?php

namespace Tests\Workflow;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PRODUCTION_FLOW.md's "Unit conversion 'bongkar' and 'dos/pack'"
 * section for the full trace this asserts against. `ProductionFlowTest.php` deliberately used a
 * single-ingredient, same-unit BOM to avoid this exact machinery — every real BOM in the seed
 * snapshot hits at least one of these two branches, so it needed its own dedicated fixture.
 *
 * Real unit ids used (from the committed seed snapshot's `units` table, none renamed here):
 * 3 = Liter, 5 = Drum, 7 = DOS, 9 = Piece.
 */
class ProductionUnitConversionFlowTest extends TestCase
{
    use ActingAsStaff;

    private const OUTPUT_UNIT_ID = 9; // Piece
    private const WAREHOUSE_ID = 1;

    /** @return array{variant: ProductVariant, productStock: ProductStock, bom: Bom} */
    private function createProductFixture(): array
    {
        $category = new Category();
        $category->category_name = 'Unit Conversion Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Unit Conversion Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::OUTPUT_UNIT_ID]);
        $product->unit_id = self::OUTPUT_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Unit Conversion Test Variant';
        $variant->product_variant_sku = 'WF-UCONV-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::OUTPUT_UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = 0;
        $productStock->status = 1;
        $productStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id; // stores a product_variant_id, see PRODUCTION_FLOW.md
        $bom->bom_qty = 1;
        $bom->unit_id = self::OUTPUT_UNIT_ID;
        $bom->status = 1;
        $bom->save();

        return compact('variant', 'productStock', 'bom');
    }

    public function test_ingredient_stocked_in_a_larger_unit_is_broken_down_via_supplies_relations(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture();

        $liter = 3;
        $drum = 5;

        $supplies = new Supplies();
        $supplies->supplies_name = 'Bongkar Test Ingredient'; // deliberately NOT matching /dos|pack/i
        $supplies->supplies_unit = json_encode([$liter, $drum]);
        $supplies->supplies_default_unit = $liter;
        $supplies->status = 1;
        $supplies->save();

        $literStock = new SuppliesStock();
        $literStock->supplies_id = $supplies->supplies_id;
        $literStock->unit_id = $liter;
        $literStock->warehouse_id = self::WAREHOUSE_ID;
        $literStock->ss_stock = 50;
        $literStock->status = 1;
        $literStock->save();

        $drumStock = new SuppliesStock();
        $drumStock->supplies_id = $supplies->supplies_id;
        $drumStock->unit_id = $drum;
        $drumStock->warehouse_id = self::WAREHOUSE_ID;
        $drumStock->ss_stock = 5;
        $drumStock->status = 1;
        $drumStock->save();

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = $drum;   // larger unit
        $relation->su_id_2 = $liter;  // smaller unit
        $relation->sr_value_1 = 1;
        $relation->sr_value_2 = 200; // 1 Drum = 200 Liter
        $relation->status = 1;
        $relation->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $fx['bom']->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 300; // Liter needed for this one production batch
        $bomDetail->unit_id = $liter;
        $bomDetail->status = 1;
        $bomDetail->save();

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Bongkar unit-conversion test',
            'detail' => json_encode([[
                'bom_id' => $fx['bom']->bom_id,
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pd_qty' => 1,
                'unit_id' => self::OUTPUT_UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'bom_detail_qty' => 300,
                'unit_id' => $liter,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status);

        $literStock->refresh();
        $drumStock->refresh();
        $this->assertSame(150, $literStock->ss_stock, 'Liter row ends at 450 (50 + 2 Drum broken down) minus the 300 requirement');
        $this->assertSame(3, $drumStock->ss_stock, '2 of the 5 Drums were broken down to cover the Liter shortfall');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $supplies->supplies_id,
            'unit_id' => $drum,
            'log_jumlah' => 2,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 1,
            'log_item_id' => $supplies->supplies_id,
            'unit_id' => $liter,
            'log_jumlah' => 400,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $supplies->supplies_id,
            'unit_id' => $liter,
            'log_jumlah' => 300,
        ]);
    }

    public function test_dos_pack_ingredient_floors_leftover_units_with_zero_packaging(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture();
        $dos = 7; // real "DOS" unit from the seed snapshot

        // The output-side ladder (below) needs a ProductStock row at the "front"/larger unit too —
        // accProduction's finished-goods logic (ProductionController.php:846-851) looks it up
        // unconditionally once a product_relations row makes the ladder apply, and crashes with
        // "assign on null" if it's missing (same crash shape as the CashAdmin *_type bug).
        $dosProductStock = new ProductStock();
        $dosProductStock->product_id = $fx['productStock']->product_id;
        $dosProductStock->product_variant_id = $fx['variant']->product_variant_id;
        $dosProductStock->unit_id = $dos;
        $dosProductStock->warehouse_id = self::WAREHOUSE_ID;
        $dosProductStock->ps_stock = 0;
        $dosProductStock->status = 1;
        $dosProductStock->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Dos Kemasan Test'; // matches /dos|pack/i on purpose
        $supplies->supplies_unit = json_encode([$dos]);
        $supplies->supplies_default_unit = $dos;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = $dos;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = 10;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        // "12 pieces fill one packaging unit" — read from the FINISHED PRODUCT's own
        // product_relations ladder, not from anything on the supplies side. See flow doc.
        $productRelation = new ProductRelation();
        $productRelation->product_variant_id = $fx['variant']->product_variant_id;
        $productRelation->pr_unit_id_1 = $dos;
        $productRelation->pr_unit_value_1 = 1;
        $productRelation->pr_unit_id_2 = self::OUTPUT_UNIT_ID;
        $productRelation->pr_unit_value_2 = 12;
        $productRelation->pr_default = 0;
        $productRelation->status = 1;
        $productRelation->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $fx['bom']->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 1; // 1 box of packaging per full "dos" of output
        $bomDetail->unit_id = $dos;
        $bomDetail->status = 1;
        $bomDetail->save();

        $pdQty = 30; // not a multiple of 12 — this is the point of the test

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Dos/pack floor-division test',
            'detail' => json_encode([[
                'bom_id' => $fx['bom']->bom_id,
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::OUTPUT_UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'bom_detail_qty' => 1,
                'unit_id' => $dos,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status);

        // Found while building this test: the finished-goods side uses the SAME
        // product_relations ladder to decide WHERE produced stock lands, not just whether packaging
        // is consumed. 30 pieces produced, 12-per-box ladder -> floor(30/12)=2 goes to the DOS-level
        // ProductStock row and only the remainder (6) lands on the Piece-level row. Lossless overall
        // (2*12 + 6 = 30), just split across two rows instead of all landing on the input unit.
        $dosProductStock->refresh();
        $fx['productStock']->refresh();
        $this->assertSame(2, $dosProductStock->ps_stock, 'floor(30/12)=2 full boxes of finished goods land on the DOS-level stock row');
        $this->assertSame(6, $fx['productStock']->ps_stock, 'the 30%12=6 leftover pieces land on the Piece-level row, not all 30');

        $suppliesStock->refresh();
        $this->assertSame(
            8,
            $suppliesStock->ss_stock,
            'floor(30/12)=2 boxes of packaging consumed (10-2=8) — the 6 leftover pieces get ZERO packaging deducted, not a partial box'
        );

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $supplies->supplies_id,
            'unit_id' => $dos,
            'log_jumlah' => 2,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $fx['variant']->product_variant_id,
            'unit_id' => $dos,
            'log_jumlah' => 2,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $fx['variant']->product_variant_id,
            'unit_id' => self::OUTPUT_UNIT_ID,
            'log_jumlah' => 6,
        ]);
    }
}
