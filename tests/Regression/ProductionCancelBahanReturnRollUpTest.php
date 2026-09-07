<?php

namespace Tests\Regression;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #159 (2026-09-07): when an already-approved production is cancelled
 * (`ProductionController::accDeleteProduction()`), the consumed bahan is returned to stock via a
 * hand-rolled one-level conversion through `SuppliesRelation` directly, bypassing `UnitRollUp`
 * entirely -- same bug class as #151: stock already sitting at the target unit BEFORE this credit
 * was never folded into the roll-up decision. Returning 6 Piece (1 DOS = 12 Piece) when 6 Piece
 * were already left sitting there landed as 12 Piece / 0 DOS forever instead of rolling up to 1
 * DOS. Fixed via `UnitRollUp::planSuppliesFolded()`.
 */
class ProductionCancelBahanReturnRollUpTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1;
    private const BOM_DETAIL_QTY = 6;

    public function test_cancelling_a_production_returns_bahan_rolled_up_together_with_stock_already_on_hand(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'GH159 Cancel Bahan RollUp Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'GH159 Cancel Bahan RollUp Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'GH159 Cancel Bahan RollUp Variant';
        $variant->product_variant_sku = 'WF-GH159-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::PIECE_UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = 0;
        $productStock->status = 1;
        $productStock->save();

        // Bahan dengan tangga satuan 1 DOS = 12 Piece. Nama sengaja TIDAK mengandung "dos"/"pack"
        // supaya accDeleteProduction()'s isKemasanBesar regex tidak nyala (jalur konsumsi biasa).
        $supplies = new Supplies();
        $supplies->supplies_name = 'GH159 Cancel Bahan RollUp Ingredient';
        $supplies->supplies_unit = json_encode([self::PIECE_UNIT_ID, self::DOS_UNIT_ID]);
        $supplies->supplies_default_unit = self::PIECE_UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        // 12 Piece di awal -- cukup untuk konsumsi 6 Piece tanpa perlu bongkar, menyisakan
        // TEPAT 6 Piece yang masih ada saat pembatalan nanti terjadi.
        $pieceStock = new SuppliesStock();
        $pieceStock->supplies_id = $supplies->supplies_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ss_stock = 12;
        $pieceStock->status = 1;
        $pieceStock->save();

        $dosStock = new SuppliesStock();
        $dosStock->supplies_id = $supplies->supplies_id;
        $dosStock->unit_id = self::DOS_UNIT_ID;
        $dosStock->warehouse_id = self::WAREHOUSE_ID;
        $dosStock->ss_stock = 0;
        $dosStock->status = 1;
        $dosStock->save();

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = self::DOS_UNIT_ID;
        $relation->su_id_2 = self::PIECE_UNIT_ID;
        $relation->sr_value_1 = 1;
        $relation->sr_value_2 = 12;
        $relation->status = 1;
        $relation->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
        $bom->unit_id = self::PIECE_UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = self::BOM_DETAIL_QTY;
        $bomDetail->unit_id = self::PIECE_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        // Produksi 1 batch -> konsumsi 6 Piece, menyisakan 6 Piece di stok.
        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'GH159 cancel bahan rollup test',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => 1,
                'unit_id' => self::PIECE_UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'bom_detail_qty' => self::BOM_DETAIL_QTY,
                'unit_id' => self::PIECE_UNIT_ID,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        $this->post('/accProduction', ['production_id' => $production->production_id])->assertStatus(200);

        $pieceStock->refresh();
        $this->assertSame(6, (int) $pieceStock->ss_stock, 'produksi mengonsumsi 6 Piece, menyisakan 6');

        // Batalkan produksi yang sudah di-ACC: ajukan lalu setujui pembatalan.
        $this->post('/deleteProduction', [
            'production_id' => $production->production_id,
            'delete_reason' => 'GH159 cancel bahan rollup test',
        ])->assertStatus(200);

        $this->post('/accDeleteProduction', ['production_id' => $production->production_id])->assertStatus(200);

        $pieceStock->refresh();
        $dosStock->refresh();

        // 6 Piece yang sudah ada + 6 Piece yang dikembalikan = 12 = tepat 1 DOS.
        $this->assertSame(0, (int) $pieceStock->ss_stock, 'BUG WOULD BE: stuck at 12 Piece, never rolled up');
        $this->assertSame(1, (int) $dosStock->ss_stock, 'existing 6 + returned 6 = 12 = exactly 1 DOS');
    }
}
