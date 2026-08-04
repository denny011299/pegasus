<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductIssuesDetail;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-05): identical bug shape to
 * `ReturnSuppliesBongkarFailsOnStockRowInsertionOrderTest`, found in this same method's sibling
 * closure — `ProductIssuesDetail::stockCheck()`'s `tipe_return == 2` ("Retur Armada") branch had
 * its own `$siapkanStokProd` bongkar closure with the identical `$units[$targetKey + 1]`
 * array-position bug (using `ProductStock`/`ProductRelation` instead of
 * `SuppliesStock`/`SuppliesRelation`). Not previously flagged in KNOWN_ISSUES.md — found and fixed
 * in the same pass as the documented Return Supplies bug. `stockCheck()` is exercised directly here
 * (not through an HTTP route) since it's the exact unit both bugs live in.
 *
 * Real unit ids used (same ones as ProductionUnitConversionFlowTest): 9 = Piece, 7 = Dos.
 */
class ProductIssuesArmadaBongkarFailsOnStockRowInsertionOrderTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1;

    public function test_bongkar_succeeds_even_when_the_smaller_units_stock_row_was_created_first(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'Armada Bongkar Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Armada Bongkar Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Armada Bongkar Regression Variant';
        $variant->product_variant_sku = 'REG-ARMADABONGKAR-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID; // larger unit
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::PIECE_UNIT_ID; // smaller unit
        $relation->pr_unit_value_2 = 12; // 1 Dos = 12 Piece
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        // Smaller unit's row created FIRST (lower ps_id) — the reproducing order.
        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 5;
        $pieceStock->status = 1;
        $pieceStock->save();

        $dosStock = new ProductStock();
        $dosStock->product_id = $product->product_id;
        $dosStock->product_variant_id = $variant->product_variant_id;
        $dosStock->unit_id = self::DOS_UNIT_ID;
        $dosStock->warehouse_id = self::WAREHOUSE_ID;
        $dosStock->ps_stock = 3; // 3 Dos = 36 Piece-equivalent, plenty combined with the 5 Piece
        $dosStock->status = 1;
        $dosStock->save();

        $returnQty = 20; // more than the 5 Piece alone; 2 Dos would easily cover it if reached

        $result = (new ProductIssuesDetail())->stockCheck([
            'tipe_return' => 2,
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'pid_qty' => $returnQty,
        ]);

        $this->assertSame(1, $result, 'the bongkar must succeed regardless of which stock row was created first');

        $pieceStock->refresh();
        $dosStock->refresh();
        // targetFisikMinimal = 20 + 1 = 21; 2 Dos broken down (5 + 24 = 29) clears it, leaving 1 Dos.
        $this->assertSame(29, $pieceStock->ps_stock, '2 Dos broken down (5 + 24 = 29) to clear the 21-piece minimum');
        $this->assertSame(1, $dosStock->ps_stock, '2 of the 3 Dos were broken down to cover the shortfall');
    }
}
