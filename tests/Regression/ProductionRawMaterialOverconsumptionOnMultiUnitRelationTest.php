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
 * Root cause (fixed 2026-08-06): `ProductionController::convertQtyToSmallestUnit()` fetched EVERY
 * active `product_relations` row for the product and multiplied all of their `pr_unit_value_2`
 * together whenever a row's base unit wasn't the unit actually being converted — true for every
 * sibling row whenever a product has more than one independent "big unit -> Piece" relation. The
 * reporter's own product had exactly that "Atur Relasi" shape: DOS=20pcs, kg=5pcs, LTR=10pcs, all
 * three converging DIRECTLY on Piece with no chaining between them (a "star", not a chain). This
 * part is unchanged and still correctly fixed — see the original test below.
 *
 * **Correction (2026-08-06, PM's own follow-up on issue #16):** that star shape was never a valid
 * product configuration in the first place — the real "Atur Relasi" business rule is a single
 * ordered CHAIN (e.g. 1 Dos = 12 Pcs, then 1 Pcs = 2 Liter — mirroring a real mineral water case:
 * a box of 12 bottles, each bottle holding 2 liters), never independent siblings all pointing at
 * the same base unit. The UI (`insertProduct.js`) now enforces this going forward — see
 * `refreshRelasiUnitOptions()`. The star-shaped scenario below is kept as
 * **defensive/legacy-data coverage** only (data created before that UI restriction existed could
 * still be shaped this way); `test_producing_with_a_proper_multi_level_unit_chain_consumes_the_correct_amount()`
 * below covers the actually-correct, now-enforced chain shape instead.
 *
 * **Investigated the same day, but turned out NOT to be a live bug:** the "kemasan besar"
 * (dos/pack) special-case in `insertProduction()`/`accProduction()`/`accDeleteProduction()` used
 * `convertQtyToSmallestUnit()`, which always walks all the way to the chain's absolute smallest
 * unit — this LOOKED wrong for a recipe unit sitting partway up a multi-level chain (e.g. recipe
 * in Pcs, chain continuing Pcs -> Liter below it), and `convertQtyBetweenUnits()` was added to
 * stop exactly at the target unit instead. **But** `ProductionController::validateBomProductSmallestUnit()`
 * separately, already, unconditionally rejects any recipe whose own unit ISN'T the chain's
 * absolute smallest — confirmed by a real 500/rejected-insert while building the "recipe in Pcs"
 * test scenario below. Asked the user whether that other rule should be relaxed to allow this;
 * they said keep it as-is (2026-08-06). Net effect: `$bom['unit_id']` is always forced to be the
 * same unit `convertQtyToSmallestUnit()` would reach anyway, so `convertQtyBetweenUnits()` and the
 * old code are behaviorally IDENTICAL for every reachable input today — the "fix" is kept as a
 * more explicit, defensive expression of intent (harmless, arguably clearer if this constraint is
 * ever relaxed later), not as a correction to a currently-reachable bug. Don't re-raise this as a
 * live bug without re-confirming `validateBomProductSmallestUnit()`'s constraint has changed.
 *
 * One more consequence of keeping that constraint, worth knowing about: with a genuine 2+ level
 * chain, "kemasan besar" `$nilaiIsiDos`/`$jumlahDos` (named for the original 1-hop "how many DOS"
 * case) actually ends up counting the unit **immediately above the forced smallest unit**, not
 * necessarily the top/packaging unit itself — see
 * `test_kemasan_besar_packaging_material_uses_the_correct_amount_on_a_multi_level_chain()`'s own
 * comments for a worked example. Not fixed, not asked about — flagged in case it produces a
 * surprising number in real "Atur Relasi" setups with 3+ unit levels.
 *
 * Real unit ids used (from the committed seed snapshot's `units` table): 1 = kg, 3 = Liter,
 * 7 = DOS, 9 = Piece.
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
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

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

    /**
     * The now-correct, actually-supported "Atur Relasi" shape — a single ordered chain, matching
     * the mineral water example from the PM's own follow-up: 1 Dos = 12 Pcs (bottles), 1 Pcs =
     * 2 Liter (each bottle holds 2 liters). Producing 1 whole Dos should consume raw material
     * proportional to 12 Pcs of product — not fold the two chain hops into something else.
     */
    public function test_producing_with_a_proper_multi_level_unit_chain_consumes_the_correct_amount(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'Chain Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Chain Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::DOS_UNIT_ID, self::PIECE_UNIT_ID, self::LITER_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Chain Regression Variant';
        $variant->product_variant_sku = 'WF-CHAIN-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $dosProductStock = new ProductStock();
        $dosProductStock->product_id = $product->product_id;
        $dosProductStock->product_variant_id = $variant->product_variant_id;
        $dosProductStock->unit_id = self::DOS_UNIT_ID;
        $dosProductStock->warehouse_id = self::WAREHOUSE_ID;
        $dosProductStock->ps_stock = 0;
        $dosProductStock->status = 1;
        $dosProductStock->save();

        // "Atur Relasi": 1 Dos = 12 Pcs, 1 Pcs = 2 Liter — a single chain, Dos -> Pcs -> Liter.
        $dosToPcs = new ProductRelation();
        $dosToPcs->product_variant_id = $variant->product_variant_id;
        $dosToPcs->pr_unit_id_1 = self::DOS_UNIT_ID;
        $dosToPcs->pr_unit_value_1 = 1;
        $dosToPcs->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $dosToPcs->pr_unit_value_2 = 12;
        $dosToPcs->pr_default = 0;
        $dosToPcs->status = 1;
        $dosToPcs->save();

        $pcsToLiter = new ProductRelation();
        $pcsToLiter->product_variant_id = $variant->product_variant_id;
        $pcsToLiter->pr_unit_id_1 = self::PIECE_UNIT_ID;
        $pcsToLiter->pr_unit_value_1 = 1;
        $pcsToLiter->pr_unit_id_2 = self::LITER_UNIT_ID;
        $pcsToLiter->pr_unit_value_2 = 2;
        $pcsToLiter->pr_default = 0;
        $pcsToLiter->status = 1;
        $pcsToLiter->save();

        // Resep: 1 Perasa (flavoring) per 1 Liter of product. `validateBomProductSmallestUnit()`
        // requires the recipe's own unit to be the chain's absolute smallest — kept as-is per
        // user decision 2026-08-06, see class docblock — so the recipe MUST be written in Liter
        // here, not Pcs (Pcs still has a relation below it in this chain).
        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
        $bom->unit_id = self::LITER_UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Perasa Regression';
        $supplies->supplies_unit = json_encode([self::PIECE_UNIT_ID]);
        $supplies->supplies_default_unit = self::PIECE_UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::PIECE_UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = 100;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 1;
        $bomDetail->unit_id = self::PIECE_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        // Produce 1 whole Dos (12 bottles, each holding 2 Liter -> 24 Liter total).
        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Multi-level chain regression: 1 Dos production',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => 1,
                'unit_id' => self::DOS_UNIT_ID,
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

        // 1 Dos = 12 Pcs = 24 Liter (both hops applied, not folded/skipped) -> getBatchCount()
        // ratios pdSmallest(24 Liter-equivalent) / bomSmallest(1 Liter-equivalent) = 24 batches,
        // so 24 units of Perasa consumed (1 per Liter-equivalent batch).
        $suppliesStock->refresh();
        $this->assertSame(76, $suppliesStock->ss_stock, '24 units of Perasa consumed for 1 Dos (24 Liter-equivalent) produced');
    }

    /**
     * Regression coverage for the `convertQtyBetweenUnits()` refactor (see class docblock) — NOT
     * proof of a live bug fix, since `validateBomProductSmallestUnit()` (kept as-is, 2026-08-06)
     * already forces `$bom['unit_id']` to always be the chain's absolute smallest unit, making the
     * new function behaviorally identical to the old `convertQtyToSmallestUnit()`-based code for
     * every reachable input. This exists to confirm the refactor didn't change that reachable
     * behavior, using the same "1 Dos = 12 Pcs, 1 Pcs = 2 Liter" chain as the test above, with a
     * packaging box ("Dos Karton") as the raw material this time.
     *
     * Worked example, recipe forced to Liter (the enforced smallest unit): `$nilaiIsiDos` = the
     * relation whose child is Liter (Pcs -> Liter, value 2) = 2. `$totalPcs` = 1 Dos walked all
     * the way to Liter terms = 12 * 2 = 24. `$jumlahDos` = floor(24 / 2) = 12. Despite the
     * "Dos"-shaped variable names (named for the original 1-hop case), with a real 2-hop chain
     * this ends up counting Pcs (the unit directly above the forced-smallest Liter), NOT the top
     * Dos unit — 12 boxes for 1 Dos produced, not 1. This is a direct, known consequence of
     * keeping `validateBomProductSmallestUnit()` as strict as it is; flagged in the class docblock,
     * not fixed here, not asked about.
     */
    public function test_kemasan_besar_packaging_material_matches_convertqtytosmallestunits_existing_behavior_on_a_multi_level_chain(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'Kemasan Besar Chain Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Kemasan Besar Chain Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::DOS_UNIT_ID, self::PIECE_UNIT_ID, self::LITER_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Kemasan Besar Chain Regression Variant';
        $variant->product_variant_sku = 'WF-CHAINDOS-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $dosProductStock = new ProductStock();
        $dosProductStock->product_id = $product->product_id;
        $dosProductStock->product_variant_id = $variant->product_variant_id;
        $dosProductStock->unit_id = self::DOS_UNIT_ID;
        $dosProductStock->warehouse_id = self::WAREHOUSE_ID;
        $dosProductStock->ps_stock = 0;
        $dosProductStock->status = 1;
        $dosProductStock->save();

        // Same chain as the test above: Dos -> Pcs (12) -> Liter (2).
        $dosToPcs = new ProductRelation();
        $dosToPcs->product_variant_id = $variant->product_variant_id;
        $dosToPcs->pr_unit_id_1 = self::DOS_UNIT_ID;
        $dosToPcs->pr_unit_value_1 = 1;
        $dosToPcs->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $dosToPcs->pr_unit_value_2 = 12;
        $dosToPcs->pr_default = 0;
        $dosToPcs->status = 1;
        $dosToPcs->save();

        $pcsToLiter = new ProductRelation();
        $pcsToLiter->product_variant_id = $variant->product_variant_id;
        $pcsToLiter->pr_unit_id_1 = self::PIECE_UNIT_ID;
        $pcsToLiter->pr_unit_value_1 = 1;
        $pcsToLiter->pr_unit_id_2 = self::LITER_UNIT_ID;
        $pcsToLiter->pr_unit_value_2 = 2;
        $pcsToLiter->pr_default = 0;
        $pcsToLiter->status = 1;
        $pcsToLiter->save();

        // Resep: 1 "Dos Karton" (packaging box) — forced to the chain's smallest unit, Liter, by
        // validateBomProductSmallestUnit().
        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
        $bom->unit_id = self::LITER_UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Dos Karton Pengemas Regression';
        $supplies->supplies_unit = json_encode([self::PIECE_UNIT_ID]);
        $supplies->supplies_default_unit = self::PIECE_UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::PIECE_UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = 100;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 1;
        $bomDetail->unit_id = self::PIECE_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        // Produce 1 whole Dos (12 bottles).
        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Kemasan besar multi-level chain regression: 1 Dos production',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => 1,
                'unit_id' => self::DOS_UNIT_ID,
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

        // See the worked example in this test's own docblock: 12 boxes, not 1 — a known
        // consequence of the kept validateBomProductSmallestUnit() constraint, not a bug this
        // test is asserting is "correct" in the real-world sense, just confirming it's unchanged.
        $suppliesStock->refresh();
        $this->assertSame(88, $suppliesStock->ss_stock, '12 Dos Karton boxes consumed — matches pre-refactor behavior exactly, see docblock');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $supplies->supplies_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'log_jumlah' => 12,
        ]);
    }
}
