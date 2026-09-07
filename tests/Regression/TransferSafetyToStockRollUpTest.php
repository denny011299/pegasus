<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\Warehouse;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #158 (2026-09-07): `StockController::transferSafetyToStock()` moved qty from
 * `ps_safety_stock` into `ps_stock` on the same unit row with a flat
 * `round($row->ps_stock + $qty, 4)` -- no roll-up at all. If the push crossed a unit-ratio
 * boundary (e.g. 12 Piece = 1 Dos), the qty stayed stuck at the small unit forever, same failure
 * mode as #155. Fixed by routing the credit through `ProductUnitStock::addQty($rollUp: true)`,
 * gated to the active warehouse being the main warehouse (same policy as every other roll-up
 * site).
 */
class TransferSafetyToStockRollUpTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1; // main warehouse in the test seed

    public function test_transferring_safety_stock_rolls_up_together_with_stock_already_on_hand(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'GH158 Safety Stock RollUp Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'GH158 Safety Stock RollUp Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID, self::DOS_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'GH158 Safety Stock RollUp Variant';
        $variant->product_variant_sku = 'WF-GH158-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        // 6 pcs already on hand + 6 pcs sitting in Safety Stock, below the 12-pcs DOS ratio on its
        // own either way.
        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 6;
        $pieceStock->ps_safety_stock = 6;
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

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $relation->pr_unit_value_2 = 12;
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        // Transfer all 6 Safety Stock pcs into normal stock -- 6 (existing) + 6 (transferred) = 12
        // = exactly 1 DOS.
        $response = $this->post('/transferSafetyToStock', [
            'product_variant_id' => $variant->product_variant_id,
            'warehouse_id' => self::WAREHOUSE_ID,
            'items' => json_encode([
                ['unit_id' => self::PIECE_UNIT_ID, 'qty' => 6],
            ]),
        ]);
        $response->assertOk();
        $response->assertJson(['status' => 1]);

        $pieceStock->refresh();
        $dosStock->refresh();

        $this->assertSame(0.0, (float) $pieceStock->ps_safety_stock, 'safety stock harus habis ditransfer');
        $this->assertSame(0, (int) $pieceStock->ps_stock, 'BUG WOULD BE: stuck at 12 Piece, never rolled up');
        $this->assertSame(1, (int) $dosStock->ps_stock, 'existing 6 + transferred 6 = 12 = exactly 1 DOS');
    }
}
