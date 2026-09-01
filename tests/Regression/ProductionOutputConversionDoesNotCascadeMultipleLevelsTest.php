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
 * ✅ FIXED (2026-08-24, GitHub #19): `ProductionController::accProduction()`'s finished-goods
 * "ladder split" used to roll a produced quantity up ONE level of a product's `product_relations`
 * ladder only, even when a second level existed above that.
 *
 * The old code visibly checked for a further level right after doing the first split:
 *
 *   $cek = $r = ProductRelation::where('pr_unit_id_2', '=', $r->pr_unit_id_1)
 *       ->where('product_variant_id', '=', $value["product_variant_id"]);
 *   if ($cek->count() <= 0) { $ada = -1; }
 *
 * ...but `$ada` was never read anywhere else in the method, and `$r` was reassigned to a Builder
 * (not `->first()`'d) that was never used either — that lookup was dead code. Whatever the second
 * level's count was, nothing further happened: the credited quantity stayed stranded at the
 * first-level unit, never rolling up into the second level even when the quantity was an exact
 * multiple of it.
 *
 * Fixed by `ProductionController::creditProductOutputUpChain()` — a pure helper that walks the
 * WHOLE `product_relations` chain (guarded at 20 hops), mirroring the already-correct
 * ingredient-side "bongkar" direction (`$siapkanStok`/`convertQtyToSmallestUnit()`), just upward
 * instead of downward. The pre-check block right before `accProduction()`'s DB transaction (which
 * warns the user before silently creating a new zero-stock `ProductStock` row) now also walks the
 * full chain via the same helper, so a second/third-level row gets the same "confirm creation"
 * prompt a first-level row already got.
 *
 * Worked example this test reproduces: a 3-level ladder (1 Sak = 2 DOS = 24 Piece). Producing 24
 * Piece, following the ladder all the way, lands as 1 full Sak with 0 left at the DOS or Piece
 * level — `floor(24/12)=2` DOS is itself an exact multiple of the Sak ratio (2), so it keeps
 * rolling up instead of stopping at the DOS-level `ProductStock` row. See `workflows/PRODUCTION_FLOW.md`.
 */
class ProductionOutputConversionDoesNotCascadeMultipleLevelsTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const SAK_UNIT_ID = 25;
    private const WAREHOUSE_ID = 1;

    public function test_producing_an_exact_multiple_of_the_second_ladder_level_now_rolls_all_the_way_up(): void
    {
        $this->actingAsSuperAdminStaff();
        // pegasus_testing has more than one active main-type warehouse (see memory
        // pegasus-testing-db-multiwarehouse-drift) -- without this, accProduction()'s implicit
        // default-warehouse pick could land on a DIFFERENT one than self::WAREHOUSE_ID below,
        // leaving this test's own fixture rows untouched and silently "passing" the 0-remainder
        // assertions for the wrong reason.
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

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
            0,
            $dosStock->ps_stock,
            'FIXED: 2 DOS is itself an exact multiple of the Sak ratio, so it keeps rolling up instead of stalling here'
        );
        $this->assertSame(
            1,
            $sakStock->ps_stock,
            'FIXED: the second ladder level is now credited — the chain walk reaches Sak and lands the full 1 Sak there'
        );

        // Only one "Hasil Produksi" log is written (Sak level, qty 1) — the DOS/Piece levels ended
        // up with a 0 remainder at every hop, so creditProductOutputUpChain()'s caller skips
        // logging (and touching) them entirely; confirms the cascade genuinely reaches the top
        // rather than stopping partway and rounding to zero.
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::SAK_UNIT_ID,
            'log_jumlah' => 1,
        ]);
        $this->assertSame(
            0,
            DB::table('log_stocks')
                ->where('log_item_id', $variant->product_variant_id)
                ->where('unit_id', self::DOS_UNIT_ID)
                ->count(),
            'no log_stocks row is written for the DOS level — it never actually gained any stock (0 remainder)'
        );
    }
}
