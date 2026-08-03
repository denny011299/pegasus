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
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `ProductionController::accProduction()`'s finished-goods "ladder split"
 * (`ProductionController.php:956-1004`) only ever rolls a produced quantity up ONE level of a
 * product's `product_relations` ladder, even when a second level exists above that.
 *
 * The code visibly checks for a further level right after doing the first split:
 *
 *   $cek = $r = ProductRelation::where('pr_unit_id_2', '=', $r->pr_unit_id_1)
 *       ->where('product_variant_id', '=', $value["product_variant_id"]);
 *   if ($cek->count() <= 0) { $ada = -1; }
 *
 * ...but `$ada` is never read anywhere else in the method, and `$r` is reassigned to a Builder
 * (not `->first()`'d) that's never used either — this lookup is dead code. Whatever the second
 * level's count is, nothing further happens: the credited quantity stays stranded at the
 * first-level unit, never rolling up into the second level even when the quantity is an exact
 * multiple of it.
 *
 * Worked example this test reproduces: a 3-level ladder (1 Sak = 2 DOS = 24 Piece). Producing 24
 * Piece should, if the ladder were followed all the way, land as 1 full Sak with 0 left at the DOS
 * or Piece level. Instead: `floor(24/12)=2` lands on the DOS-level `ProductStock` row and stays
 * there — the Sak-level row is never touched, even though 2 DOS is an EXACT multiple of the
 * Sak ratio (2). This mirrors the same "one level only" shape as the ingredient-side "bongkar"
 * mechanism, which — unlike this one — recurses correctly via `$siapkanStok`'s own recursive call
 * (see `ProductionUnitConversionFlowTest`). See `workflows/PRODUCTION_FLOW.md`.
 */
class ProductionOutputConversionDoesNotCascadeMultipleLevelsTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const SAK_UNIT_ID = 25;
    private const WAREHOUSE_ID = 1;

    public function test_producing_an_exact_multiple_of_the_second_ladder_level_still_stalls_at_the_first_level(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'Output Cascade Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Output Cascade Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Output Cascade Regression Variant';
        $variant->product_variant_sku = 'WF-CASCADE-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 0;
        $pieceStock->status = 1;
        $pieceStock->save();

        $dosStock = new ProductStock();
        $dosStock->product_id = $product->product_id;
        $dosStock->product_variant_id = $variant->product_variant_id;
        $dosStock->unit_id = self::DOS_UNIT_ID;
        $dosStock->warehouse_id = self::WAREHOUSE_ID;
        $dosStock->ps_stock = 0;
        $dosStock->status = 1;
        $dosStock->save();

        $sakStock = new ProductStock();
        $sakStock->product_id = $product->product_id;
        $sakStock->product_variant_id = $variant->product_variant_id;
        $sakStock->unit_id = self::SAK_UNIT_ID;
        $sakStock->warehouse_id = self::WAREHOUSE_ID;
        $sakStock->ps_stock = 0;
        $sakStock->status = 1;
        $sakStock->save();

        // Level 1: 1 DOS = 12 Piece
        $relationDos = new ProductRelation();
        $relationDos->product_variant_id = $variant->product_variant_id;
        $relationDos->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relationDos->pr_unit_value_1 = 1;
        $relationDos->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $relationDos->pr_unit_value_2 = 12;
        $relationDos->pr_default = 0;
        $relationDos->status = 1;
        $relationDos->save();

        // Level 2: 1 Sak = 2 DOS (= 24 Piece)
        $relationSak = new ProductRelation();
        $relationSak->product_variant_id = $variant->product_variant_id;
        $relationSak->pr_unit_id_1 = self::SAK_UNIT_ID;
        $relationSak->pr_unit_value_1 = 1;
        $relationSak->pr_unit_id_2 = self::DOS_UNIT_ID;
        $relationSak->pr_unit_value_2 = 2;
        $relationSak->pr_default = 0;
        $relationSak->status = 1;
        $relationSak->save();

        // A plain, plentiful ingredient — no bongkar/dos-pack machinery, keeps this test isolated
        // to the output side only.
        $supplies = new Supplies();
        $supplies->supplies_name = 'Output Cascade Regression Ingredient';
        $supplies->supplies_unit = json_encode([self::PIECE_UNIT_ID]);
        $supplies->supplies_default_unit = self::PIECE_UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::PIECE_UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = 1000;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
        $bom->unit_id = self::PIECE_UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 1;
        $bomDetail->unit_id = self::PIECE_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        $pdQty = 24; // exactly 2 DOS, exactly 1 Sak if the ladder were followed all the way

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Output cascade regression test',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::PIECE_UNIT_ID,
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

        $pieceStock->refresh();
        $dosStock->refresh();
        $sakStock->refresh();

        $this->assertSame(0, $pieceStock->ps_stock, 'no remainder at the Piece level — 24 is an exact multiple of 12');
        $this->assertSame(
            2,
            $dosStock->ps_stock,
            'BUG: stops at the first ladder level — floor(24/12)=2 lands here and stays, even though 2 DOS is itself an exact multiple of the Sak ratio'
        );
        $this->assertSame(
            0,
            $sakStock->ps_stock,
            'BUG: the second ladder level is never credited at all — the dead $cek/$ada check never actually rolls anything up into it'
        );

        // Only one "Hasil Produksi" log is written (DOS level) — no Sak-level log exists at all,
        // confirming the cascade genuinely never runs rather than running and rounding to zero.
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'log_jumlah' => 2,
        ]);
        $this->assertSame(
            0,
            DB::table('log_stocks')
                ->where('log_item_id', $variant->product_variant_id)
                ->where('unit_id', self::SAK_UNIT_ID)
                ->count(),
            'no log_stocks row was ever written for the Sak level'
        );
    }
}
