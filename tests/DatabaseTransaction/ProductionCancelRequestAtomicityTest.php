<?php

namespace Tests\DatabaseTransaction;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\ProductionDetails;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PRODUCTION_FLOW.md's "Cancel-request sub-flow" section for the
 * fully-traced flow this documents. `accDeleteProduction` (approving a request to cancel an
 * already-approved production) has NO `DB::transaction()` anywhere in its ~300-line body —
 * confirmed by grep, zero `DB::beginTransaction()`/`DB::transaction()` calls. This is the exact
 * same gap `accProduction` had before `main` commit `2d73633` fixed it — nobody applied the same
 * fix here.
 *
 * Trigger: the reversal's product-stock loop has the identical "ladder split" null-guard gap
 * already found and characterized for `accProduction` itself
 * (`tests/Regression/ProductionOutputLadderNullGuardCrashTest.php`) — `$ps_depan->ps_stock -=
 * $kurangDos;` on a `ProductStock` row that doesn't exist at the larger unit. Unlike
 * `accProduction`'s pre-check, this method's own reversibility pre-check (lines ~1085-1135) only
 * verifies the COMBINED total across both unit levels is sufficient — it never confirms the
 * specific larger-unit row the reversal is about to write to actually exists, so a product with
 * enough stock entirely at the smaller unit still passes the pre-check cleanly, then crashes in
 * the reversal loop that follows.
 *
 * The production/detail rows here are seeded directly (bypassing `insertProduction`/
 * `accProduction`) to precisely control the "already approved" starting state this test needs —
 * the point of this test is `accDeleteProduction`'s own behavior, not re-verifying approval.
 *
 * Not fixed here — deferred per this project's "queue bugs, don't fix" policy. This test
 * characterizes the CURRENT behavior on purpose.
 */
class ProductionCancelRequestAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1;

    public function test_a_mid_reversal_crash_leaves_the_production_permanently_cancelled_with_stock_left_inconsistent(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'Cancel Atomicity Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Cancel Atomicity Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Cancel Atomicity Test Variant';
        $variant->product_variant_sku = 'WF-CANCELATOMIC-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        // Only a Piece-level ProductStock row exists — deliberately NO DOS-level row, even though
        // a ladder relation is configured below. 20 pieces in stock is enough to pass the
        // reversal's own "is there enough combined stock" pre-check on its own.
        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 20;
        $pieceStock->status = 1;
        $pieceStock->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID; // larger unit — NO ProductStock row exists here
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $relation->pr_unit_value_2 = 12; // 1 DOS = 12 Piece
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Cancel Atomicity Test Supplies';
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
        $bomDetail->bom_detail_qty = 2;
        $bomDetail->unit_id = self::PIECE_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        // Seed an already-approved production directly (bypassing insertProduction/accProduction)
        // — pd_qty=20 is >= the 12-per-DOS ladder value, triggering the crashing branch.
        $production = new Production();
        $production->production_date = now()->toDateString();
        $production->production_code = 'PRCNCLTST';
        $production->production_desc = 'Cancel atomicity test production';
        $production->production_created_by = 1;
        $production->status = 2;
        $production->save();

        $detail = new ProductionDetails();
        $detail->production_id = $production->production_id;
        $detail->product_variant_id = $variant->product_variant_id;
        $detail->pd_qty = 20;
        $detail->unit_id = self::PIECE_UNIT_ID;
        $detail->bom_id = $bom->bom_id;
        $detail->status = 1;
        $detail->save();

        $this->post('/deleteProduction', [
            'production_id' => $production->production_id,
            'delete_reason' => 'DB transaction atomicity test',
        ])->assertStatus(200);

        $production->refresh();
        $this->assertSame(4, (int) $production->status, 'the cancel request is pending');

        $accResponse = $this->post('/accDeleteProduction', ['production_id' => $production->production_id]);

        // Documents current behavior: an uncaught error, not a clean {status:-1, ...} response.
        $accResponse->assertStatus(500);

        // BUG: despite the crash, the production is already permanently cancelled — status = 3
        // was set (via cancelProduction()) before the stock reversal loop ever ran.
        $production->refresh();
        $this->assertSame(
            3,
            (int) $production->status,
            'BUG: cancelProduction() runs before the stock reversal, so the production is left cancelled despite the crash'
        );

        // BUG: the Piece-level stock is left completely untouched — the crash happens before its
        // own subtraction line is ever reached, since the DOS-level lookup crashes first.
        $pieceStock->refresh();
        $this->assertSame(20, $pieceStock->ps_stock, 'BUG: stock is left exactly as the original approval left it — neither reversed nor consistent with the now-cancelled status');

        // The DOS-level row that crashed the lookup still doesn't exist — nothing created it.
        $this->assertSame(
            0,
            ProductStock::where('product_variant_id', $variant->product_variant_id)->where('unit_id', self::DOS_UNIT_ID)->count()
        );

        // The ingredient restoration loop (which runs after the crashing product-stock loop) never
        // ran either — supplies_stocks is untouched, neither restored nor left as a legitimate
        // "still consumed" state matching a cancelled production.
        $suppliesStock->refresh();
        $this->assertSame(1000, $suppliesStock->ss_stock, 'BUG: ingredient restoration never ran, since the crash happens earlier in the same request');
    }
}
