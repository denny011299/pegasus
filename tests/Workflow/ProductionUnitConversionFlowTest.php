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

    /**
     * The two mechanisms above were each proven in isolation with their own single-ingredient BOM.
     * This puts BOTH ingredient types on the SAME bom_id for ONE production item, to confirm the
     * per-supplies_id aggregation (ProductionController.php's $aggregatedRequirements, keyed by
     * supplies_id) really does keep them independent rather than one bleeding into the other's
     * math or stock ladder. Reuses the exact same numbers as the two isolated tests above
     * (pd_qty=30, 12-per-dos product ladder, 10 Liter/unit with a 1 Drum = 200 Liter supplies
     * ladder) specifically so the expected end-state is provably identical to running each
     * mechanism alone — proving no cross-talk, not just "it didn't crash".
     */
    public function test_bom_mixing_a_bongkar_ingredient_and_a_dos_pack_ingredient_in_the_same_production(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture();
        $liter = 3;
        $drum = 5;
        $dos = 7;

        // Output-side ladder needs its larger-unit ProductStock row pre-provisioned, same
        // null-guard gap documented in the dos/pack-only test above.
        $dosProductStock = new ProductStock();
        $dosProductStock->product_id = $fx['productStock']->product_id;
        $dosProductStock->product_variant_id = $fx['variant']->product_variant_id;
        $dosProductStock->unit_id = $dos;
        $dosProductStock->warehouse_id = self::WAREHOUSE_ID;
        $dosProductStock->ps_stock = 0;
        $dosProductStock->status = 1;
        $dosProductStock->save();

        $productRelation = new ProductRelation();
        $productRelation->product_variant_id = $fx['variant']->product_variant_id;
        $productRelation->pr_unit_id_1 = $dos;
        $productRelation->pr_unit_value_1 = 1;
        $productRelation->pr_unit_id_2 = self::OUTPUT_UNIT_ID;
        $productRelation->pr_unit_value_2 = 12;
        $productRelation->pr_default = 0;
        $productRelation->status = 1;
        $productRelation->save();

        // Ingredient 1: bongkar-needing, deliberately NOT matching /dos|pack/i.
        $bongkarSupplies = new Supplies();
        $bongkarSupplies->supplies_name = 'Mixed BOM Bongkar Ingredient';
        $bongkarSupplies->supplies_unit = json_encode([$liter, $drum]);
        $bongkarSupplies->supplies_default_unit = $liter;
        $bongkarSupplies->status = 1;
        $bongkarSupplies->save();

        $literStock = new SuppliesStock();
        $literStock->supplies_id = $bongkarSupplies->supplies_id;
        $literStock->unit_id = $liter;
        $literStock->warehouse_id = self::WAREHOUSE_ID;
        $literStock->ss_stock = 50;
        $literStock->status = 1;
        $literStock->save();

        $drumStock = new SuppliesStock();
        $drumStock->supplies_id = $bongkarSupplies->supplies_id;
        $drumStock->unit_id = $drum;
        $drumStock->warehouse_id = self::WAREHOUSE_ID;
        $drumStock->ss_stock = 5;
        $drumStock->status = 1;
        $drumStock->save();

        $suppliesRelation = new SuppliesRelation();
        $suppliesRelation->supplies_id = $bongkarSupplies->supplies_id;
        $suppliesRelation->su_id_1 = $drum;
        $suppliesRelation->su_id_2 = $liter;
        $suppliesRelation->sr_value_1 = 1;
        $suppliesRelation->sr_value_2 = 200; // 1 Drum = 200 Liter
        $suppliesRelation->status = 1;
        $suppliesRelation->save();

        // Ingredient 2: dos/pack special-case, matching /dos|pack/i on purpose.
        $dosSupplies = new Supplies();
        $dosSupplies->supplies_name = 'Mixed BOM Dos Kemasan';
        $dosSupplies->supplies_unit = json_encode([$dos]);
        $dosSupplies->supplies_default_unit = $dos;
        $dosSupplies->status = 1;
        $dosSupplies->save();

        $dosSuppliesStock = new SuppliesStock();
        $dosSuppliesStock->supplies_id = $dosSupplies->supplies_id;
        $dosSuppliesStock->unit_id = $dos;
        $dosSuppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $dosSuppliesStock->ss_stock = 10;
        $dosSuppliesStock->status = 1;
        $dosSuppliesStock->save();

        // Both BomDetail rows live on the SAME bom_id — this is the point of the test.
        $bongkarBomDetail = new BomDetail();
        $bongkarBomDetail->bom_id = $fx['bom']->bom_id;
        $bongkarBomDetail->supplies_id = $bongkarSupplies->supplies_id;
        $bongkarBomDetail->bom_detail_qty = 10; // Liter needed per 1 Piece produced
        $bongkarBomDetail->unit_id = $liter;
        $bongkarBomDetail->status = 1;
        $bongkarBomDetail->save();

        $dosBomDetail = new BomDetail();
        $dosBomDetail->bom_id = $fx['bom']->bom_id;
        $dosBomDetail->supplies_id = $dosSupplies->supplies_id;
        $dosBomDetail->bom_detail_qty = 1; // 1 box per full dos of output
        $dosBomDetail->unit_id = $dos;
        $dosBomDetail->status = 1;
        $dosBomDetail->save();

        $pdQty = 30; // not a multiple of 12 — same as the isolated dos/pack test

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Mixed bongkar + dos/pack BOM test',
            'detail' => json_encode([[
                'bom_id' => $fx['bom']->bom_id,
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::OUTPUT_UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                ['supplies_id' => $bongkarSupplies->supplies_id, 'bom_detail_qty' => 10, 'unit_id' => $liter],
                ['supplies_id' => $dosSupplies->supplies_id, 'bom_detail_qty' => 1, 'unit_id' => $dos],
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status);

        // Bongkar ingredient: identical end-state to the isolated bongkar test (300 Liter needed
        // total = 10 * batchCount(30), same as bom_detail_qty=300 there with batchCount=1).
        $literStock->refresh();
        $drumStock->refresh();
        $this->assertSame(150, $literStock->ss_stock, 'bongkar math unaffected by the sibling dos/pack ingredient on the same BOM');
        $this->assertSame(3, $drumStock->ss_stock, '2 of the 5 Drums broken down, same as running the mechanism alone');

        // Dos/pack ingredient: identical end-state to the isolated dos/pack test.
        $dosSuppliesStock->refresh();
        $this->assertSame(8, $dosSuppliesStock->ss_stock, 'floor(30/12)=2 boxes consumed, unaffected by the sibling bongkar ingredient');

        // Output side: identical split to the isolated dos/pack test.
        $dosProductStock->refresh();
        $fx['productStock']->refresh();
        $this->assertSame(2, $dosProductStock->ps_stock);
        $this->assertSame(6, $fx['productStock']->ps_stock);

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $bongkarSupplies->supplies_id,
            'unit_id' => $drum,
            'log_jumlah' => 2,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 1,
            'log_item_id' => $bongkarSupplies->supplies_id,
            'unit_id' => $liter,
            'log_jumlah' => 400,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $bongkarSupplies->supplies_id,
            'unit_id' => $liter,
            'log_jumlah' => 300,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $dosSupplies->supplies_id,
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

    /**
     * Every test above (and the original pilot) records production output in the product's own
     * SMALLEST unit (Piece) and lets the ladder split it upward. This exercises the other
     * direction: recording production directly in an already-"converted" (non-base) unit — the
     * ladder logic (`ProductionController.php:956-1004`) keys off whatever unit the user actually
     * submitted (`$unitIdInputUser`), not a hardcoded "smallest" unit, so it applies exactly the
     * same one-level split relative to THAT unit's own next-level-up relation.
     *
     * Real unit id used here: 25 = Sak (one level above DOS in this fixture's own ladder — no
     * relation to Piece is defined at all, so DOS is this BOM's own smallest recognized unit).
     */
    public function test_producing_directly_in_an_already_converted_unit_still_applies_the_output_ladder_one_level_up(): void
    {
        $this->actingAsSuperAdminStaff();

        $dos = 7;
        $sak = 25;

        $category = new Category();
        $category->category_name = 'Output Ladder From Intermediate Unit Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Output Ladder From Intermediate Unit Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$dos]);
        $product->unit_id = $dos;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Output Ladder From Intermediate Unit Variant';
        $variant->product_variant_sku = 'WF-UCONV-INTERMEDIATE-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $dosProductStock = new ProductStock();
        $dosProductStock->product_id = $product->product_id;
        $dosProductStock->product_variant_id = $variant->product_variant_id;
        $dosProductStock->unit_id = $dos;
        $dosProductStock->warehouse_id = self::WAREHOUSE_ID;
        $dosProductStock->ps_stock = 0;
        $dosProductStock->status = 1;
        $dosProductStock->save();

        $sakProductStock = new ProductStock();
        $sakProductStock->product_id = $product->product_id;
        $sakProductStock->product_variant_id = $variant->product_variant_id;
        $sakProductStock->unit_id = $sak;
        $sakProductStock->warehouse_id = self::WAREHOUSE_ID;
        $sakProductStock->ps_stock = 0;
        $sakProductStock->status = 1;
        $sakProductStock->save();

        $productRelation = new ProductRelation();
        $productRelation->product_variant_id = $variant->product_variant_id;
        $productRelation->pr_unit_id_1 = $sak; // larger unit
        $productRelation->pr_unit_value_1 = 1;
        $productRelation->pr_unit_id_2 = $dos; // smaller unit — this BOM's own recorded unit
        $productRelation->pr_unit_value_2 = 2; // 1 Sak = 2 DOS
        $productRelation->pr_default = 0;
        $productRelation->status = 1;
        $productRelation->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Output Ladder Intermediate Unit Ingredient';
        $supplies->supplies_unit = json_encode([$dos]);
        $supplies->supplies_default_unit = $dos;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = $dos;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = 1000;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
        $bom->unit_id = $dos;
        $bom->status = 1;
        $bom->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 1;
        $bomDetail->unit_id = $dos;
        $bomDetail->status = 1;
        $bomDetail->save();

        $pdQty = 5; // recorded directly in DOS: 5 >= the Sak ratio of 2, with a remainder

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Output ladder from intermediate unit test',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => $dos,
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

        $dosProductStock->refresh();
        $sakProductStock->refresh();
        $this->assertSame(2, $sakProductStock->ps_stock, 'floor(5/2)=2 Sak credited, even though production was recorded directly in DOS, not the base unit');
        $this->assertSame(1, $dosProductStock->ps_stock, '5 mod 2 = 1 DOS remainder stays at the recorded unit');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => $sak,
            'log_jumlah' => 2,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => $dos,
            'log_jumlah' => 1,
        ]);
    }
}
