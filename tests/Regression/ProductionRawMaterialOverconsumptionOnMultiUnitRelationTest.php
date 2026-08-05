<?php

namespace Tests\Regression;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub issue #16: producing "1 Liter" of a product deducted 1000 pieces of a raw material whose
 * recipe only needed 1 piece per 1 piece of product — a 100x raw-material overconsumption.
 *
 * Root cause (fixed on this branch): `ProductionController::convertQtyToSmallestUnit()` fetched
 * EVERY active `product_relations` row for the product and multiplied all of their
 * `pr_unit_value_2` together whenever a row's base unit wasn't the unit actually being converted —
 * true for every sibling row whenever a product has more than one independent "big unit -> Piece"
 * relation. The reporter's own product had exactly that "Atur Relasi" shape: DOS=20pcs, kg=5pcs,
 * LTR=10pcs, all three converging on Piece. Converting "1 Liter" folded all three factors together
 * (20 * 5 * 10 = 1000) instead of using just the matching LTR relation (10), so a recipe needing 1
 * piece of "Botol 600ml" per 1 piece of product consumed 1000 pieces instead of 10.
 *
 * The same duplicated buggy loop existed in three places (`insertProduction()`'s pre-check,
 * `accProduction()`'s real deduction, and `accDeleteProduction()`'s reversal) plus the shared
 * `convertQtyToSmallestUnit()`/`getBatchCount()` pair — all four now delegate to the same fixed,
 * chain-walking conversion (mirrors the already-correct `convertSuppliesQtyToSmallestUnit()`).
 *
 * Real unit ids used (from the committed seed snapshot's `units` table, matching the issue's own
 * screenshots): 1 = kg, 3 = Liter, 7 = DOS, 9 = Piece.
 */
class ProductionRawMaterialOverconsumptionOnMultiUnitRelationTest extends TestCase
{
    use ActingAsStaff;

    private const KG_UNIT_ID = 1;
    private const LITER_UNIT_ID = 3;
    private const DOS_UNIT_ID = 7;
    private const PIECE_UNIT_ID = 9;
    private const WAREHOUSE_ID = 1;

    public function test_producing_in_a_unit_with_multiple_sibling_relations_consumes_the_correct_amount(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'Issue 16 Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Issue 16 Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID, self::LITER_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Issue 16 Regression Variant';
        $variant->product_variant_sku = 'WF-ISSUE16-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        // ProductStock row at the unit production is actually recorded in (Liter) — the
        // finished-goods crediting side keys off this directly since no relation maps Liter up
        // to a still-larger unit (see ProductionController.php:1025: `pr_unit_id_2 == Liter`
        // never matches here, so it takes the plain "add to this row" branch, not the ladder).
        $literProductStock = new ProductStock();
        $literProductStock->product_id = $product->product_id;
        $literProductStock->product_variant_id = $variant->product_variant_id;
        $literProductStock->unit_id = self::LITER_UNIT_ID;
        $literProductStock->warehouse_id = self::WAREHOUSE_ID;
        $literProductStock->ps_stock = 0;
        $literProductStock->status = 1;
        $literProductStock->save();

        // "Atur Relasi" — three INDEPENDENT relations all converging on Piece, matching the real
        // product's configuration from the bug report screenshots.
        foreach ([
            [self::DOS_UNIT_ID, 20],
            [self::KG_UNIT_ID, 5],
            [self::LITER_UNIT_ID, 10],
        ] as [$bigUnitId, $pcsValue]) {
            $relation = new ProductRelation();
            $relation->product_variant_id = $variant->product_variant_id;
            $relation->pr_unit_id_1 = $bigUnitId;
            $relation->pr_unit_value_1 = 1;
            $relation->pr_unit_id_2 = self::PIECE_UNIT_ID;
            $relation->pr_unit_value_2 = $pcsValue;
            $relation->pr_default = 0;
            $relation->status = 1;
            $relation->save();
        }

        // Recipe ("Resep Bahan Mentah"): 1 Piece of product needs 1 Piece of Botol600ml.
        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id; // stores a product_variant_id, see PRODUCTION_FLOW.md
        $bom->bom_qty = 1;
        $bom->unit_id = self::PIECE_UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Botol 600ml';
        $supplies->supplies_unit = json_encode([self::PIECE_UNIT_ID]);
        $supplies->supplies_default_unit = self::PIECE_UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::PIECE_UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = 2000; // plenty — the bug would have consumed 1000 for just 1 Liter
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 1;
        $bomDetail->unit_id = self::PIECE_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        // Production of "1 Liter" — exactly the issue's own reproduction steps.
        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Issue #16 regression: 1 Liter production',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => 1,
                'unit_id' => self::LITER_UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'bom_detail_qty' => 1,
                'unit_id' => self::PIECE_UNIT_ID,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status);

        // 1 Liter = 10 Piece per the LTR relation; the recipe needs 1 pc of Botol600ml per 1 pc
        // of product -> 10 pcs consumed. BEFORE THE FIX this was 1000 (20 * 5 * 10 folded
        // together from the DOS/kg/LTR siblings instead of picking just LTR).
        $suppliesStock->refresh();
        $this->assertSame(1990, $suppliesStock->ss_stock, 'only 10 pieces of Botol600ml consumed for 1 Liter produced, not 1000');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $supplies->supplies_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'log_jumlah' => 10,
        ]);
        $this->assertDatabaseMissing('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $supplies->supplies_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'log_jumlah' => 1000,
        ]);

        $literProductStock->refresh();
        $this->assertSame(1, $literProductStock->ps_stock, 'finished-goods side still credits 1 Liter as recorded — unaffected by the input-side fix');
    }
}
