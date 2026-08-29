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
 * ✅ FIXED (2026-08-30, GitHub #87, ported from main's `d416aba`). Reported directly by the user
 * against a real SKU (HKCP60P): stock before production was 11 DOS + 20 Piece (1 DOS = 24 Piece
 * there); a production run credited 4 more Piece. Expected result: 20 + 4 = 24 = exactly 1 more
 * DOS, ending at 12 DOS + 0 Piece. Actual result: 11 DOS + 24 Piece — the DOS row was never
 * touched.
 *
 * `App\Support\UnitRollUp::plan()` decided whether to roll a credited quantity up a level using
 * ONLY the newly credited amount, completely ignoring whatever was already sitting in that unit's
 * stock row:
 *
 *   if ($rel === null || ... || $remaining < $rel['ratio'] || ...) { break; } // $remaining was $qty alone
 *
 * For this fixture: qty=4, ratio=24. Since 4 < 24 the loop broke immediately without ever checking
 * whether the EXISTING 20 Piece plus this credit's 4 reaches the ratio. The credit then fell
 * through to a flat `ps_stock += 4` (via `ProductUnitStock::addQty()`), leaving the DOS row
 * untouched forever — this is why the existing
 * `ProductionUnitConversionFlowTest`/`ProductionOutputConversionDoesNotCascadeMultipleLevelsTest`
 * suite never caught it: every one of those fixtures starts the smallest unit's stock at 0, so
 * "existing stock plus new credit" and "new credit alone" always happened to be the same number.
 *
 * Fixed by making `UnitRollUp::plan()` (and every `plan*()` wrapper, including
 * `planProductOutput()` used by `accProduction()`'s pre-check, and `ProductUnitStock::addQty()`'s
 * internal roll-up call used by the actual credit) existing-aware — see
 * `tests/Unit/UnitRollUpTest.php`'s `$existingByUnitId` section for the pure-logic proof.
 */
class ProductionOutputRollUpIgnoredExistingStockTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1;

    public function test_a_below_ratio_production_credit_still_rolls_up_when_combined_with_existing_leftover_stock(): void
    {
        $this->actingAsSuperAdminStaff();
        // Wajib: insertProduction()/accProduction() menolak kalau gudang aktif sesi bukan gudang
        // utama -- lihat memory pegasus-testing-db-multiwarehouse-drift.
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'Existing Stock Roll-Up Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Existing Stock Roll-Up Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Existing Stock Roll-Up Regression Variant';
        $variant->product_variant_sku = 'WF-ROLLUP-EXISTING-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        // Stock BEFORE production: 20 Piece, 11 DOS — this is the whole point of the test.
        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 20;
        $pieceStock->status = 1;
        $pieceStock->save();

        $dosStock = new ProductStock();
        $dosStock->product_id = $product->product_id;
        $dosStock->product_variant_id = $variant->product_variant_id;
        $dosStock->unit_id = self::DOS_UNIT_ID;
        $dosStock->warehouse_id = self::WAREHOUSE_ID;
        $dosStock->ps_stock = 11;
        $dosStock->status = 1;
        $dosStock->save();

        // 1 DOS = 24 Piece, matching the real HKCP60P ladder.
        $relationDos = new ProductRelation();
        $relationDos->product_variant_id = $variant->product_variant_id;
        $relationDos->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relationDos->pr_unit_value_1 = 1;
        $relationDos->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $relationDos->pr_unit_value_2 = 24;
        $relationDos->pr_default = 0;
        $relationDos->status = 1;
        $relationDos->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Existing Stock Roll-Up Regression Ingredient';
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

        $pdQty = 4; // below the 24-Piece ratio on its own — only reaches it combined with the 20 already in stock

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Existing stock roll-up regression test',
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

        $this->assertEquals(0, $pieceStock->ps_stock, '20 existing + 4 produced = 24 = exactly 1 DOS, none left over at the Piece level');
        $this->assertEquals(12, $dosStock->ps_stock, '11 existing + 1 rolled up from the combined Piece total = 12 DOS');

        // The 20 pre-existing Piece leaving this row is logged as a unit conversion (OUT here),
        // not silently vanishing from the ledger.
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'log_jumlah' => 20,
        ]);
        // ... and its reappearance as 1 new DOS is logged as the production result (IN here).
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'log_jumlah' => 1,
        ]);
    }
}
