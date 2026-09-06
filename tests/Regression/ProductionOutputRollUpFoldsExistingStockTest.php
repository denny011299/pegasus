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
 * GitHub #151 (2026-09-06): `ProductionController::accProduction()`'s finished-goods roll-up
 * (GitHub #19, `ProductUnitStock::addQty($rollUp: true)`) only rolled the qty THIS production
 * output brought in — stock already sitting at the produced unit was ignored. Producing 6 Piece
 * when 6 Piece already existed (1 DOS = 12 Piece) landed as 12 Piece / 0 DOS forever, because 6
 * alone is not a multiple of 12 — only a single production that was ITSELF an exact multiple of
 * the ratio ever rolled up. Confirmed as the literal bug report: "Setelah ada produksi Pieces
 * stok tidak roll up jadi dos".
 *
 * This was a written-up, PM-accepted "known risk" for every stock-IN roll-up site (see
 * `UnitRollUp.php`'s class docblock) — reported here as a real bug and fixed for all of them:
 * `UnitRollUp::planFolded()`/`planProductFolded()`/`planSuppliesFolded()` now fold stock already
 * at the start unit into the roll-up decision, used by `ProductUnitStock::addQty()` (Production,
 * Sales Order revert/cancellation, customer product returns),
 * `PurchaseOrderDeliveryDetail::insertPoDeliveryDetail()` (PO receipt — see
 * `PurchaseOrderReceiptRollUpFlowTest`), and `ProductIssuesDetail::deleteProductIssuesDetail()`
 * (Return Supplies cancellation — see `ReturnSuppliesDeleteRollUpFoldsExistingStockTest`).
 */
class ProductionOutputRollUpFoldsExistingStockTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1;

    public function test_producing_pieces_rolls_up_together_with_stock_already_on_hand(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'Output Fold Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Output Fold Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID, self::DOS_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Output Fold Regression Variant';
        $variant->product_variant_sku = 'WF-FOLD-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 6; // pre-existing, below the 12-Piece DOS ratio on its own
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

        $relationDos = new ProductRelation();
        $relationDos->product_variant_id = $variant->product_variant_id;
        $relationDos->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relationDos->pr_unit_value_1 = 1;
        $relationDos->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $relationDos->pr_unit_value_2 = 12;
        $relationDos->pr_default = 0;
        $relationDos->status = 1;
        $relationDos->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Output Fold Regression Ingredient';
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

        // 6 more Piece produced -- not a multiple of 12 by itself, but 6 (existing) + 6 (new) = 12
        // = exactly 1 DOS.
        $pdQty = 6;

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Output fold regression test',
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

        $this->assertSame(0, $pieceStock->ps_stock, 'BUG WOULD BE: stuck at 12 Piece, never rolled up');
        $this->assertSame(1, $dosStock->ps_stock, 'existing 6 + produced 6 = 12 = exactly 1 DOS');

        // History requested by the user (2026-09-07): 3 separate legs, not one netted delta --
        // "inward 6 Piece" (the real event), "outward 12 Piece" (existing 6 + produced 6, the FULL
        // amount that moved up -- not just this call's own 6), then "inward 1 DOS" (the result).
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'log_jumlah' => 6,
            'log_notes' => 'Hasil produksi ' . $production->production_code,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'log_jumlah' => 12, // existing 6 + produced 6, the FULL amount converted -- not the delta
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'log_jumlah' => 1,
        ]);

        // Exactly 3 log_stocks rows for this production -- the 3 legs above, nothing netted away
        // and nothing duplicated.
        $this->assertSame(3, \Illuminate\Support\Facades\DB::table('log_stocks')
            ->where('log_item_id', $variant->product_variant_id)
            ->count());
    }
}
