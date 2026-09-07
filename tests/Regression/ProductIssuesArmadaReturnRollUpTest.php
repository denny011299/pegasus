<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductIssues;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #155: approving a "Produk Bermasalah" (Product Issue) return from Armada credited the
 * returned qty flat onto the returned unit with no roll-up at all, unlike every other stock-IN
 * path (Production output, PO receipt, Sales Order revert). Reported case: 1 Dos + 4 Piece on
 * hand (1 Dos = 12 Piece), then 8 Piece returned — landed as "1 Dos 12 Piece" instead of rolling
 * up to "2 Dos 0 Piece".
 *
 * Fixed by routing `StockController::accProductIssues()`'s "Return from customer" branch through
 * `ProductUnitStock::addQty($rollUp: true)` — same mechanism as `ProductionController::
 * accProduction()` (GH #19/#151) — gated to only roll up when the active warehouse is the main
 * warehouse, per the issue's own ask ("teruntuk active warehouse nya adalah gudang utama").
 */
class ProductIssuesArmadaReturnRollUpTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1; // main warehouse in the test seed

    private function insertProductIssue(array $items): int
    {
        $response = $this->post('/insertProductIssues', [
            'tipe_return' => 2,
            'pi_type' => 1,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'GH #155 regression test',
            'items' => json_encode($items),
        ]);
        $response->assertStatus(200);

        return (int) ProductIssues::orderByDesc('pi_id')->value('pi_id');
    }

    public function test_armada_return_rolls_up_together_with_stock_already_on_hand_in_main_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'GH155 Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'GH155 Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID, self::DOS_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'GH155 Regression Variant';
        $variant->product_variant_sku = 'WF-GH155-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 4; // 1 Dos + 4 Piece already on hand, as in the bug report
        $pieceStock->status = 1;
        $pieceStock->save();

        $dosStock = new ProductStock();
        $dosStock->product_id = $product->product_id;
        $dosStock->product_variant_id = $variant->product_variant_id;
        $dosStock->unit_id = self::DOS_UNIT_ID;
        $dosStock->warehouse_id = self::WAREHOUSE_ID;
        $dosStock->ps_stock = 1;
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

        // 8 Piece returned from Armada -- alone not a multiple of 12, but 4 (existing) + 8
        // (returned) = 12 = exactly 1 Dos.
        $piId = $this->insertProductIssue([[
            'product_variant_id' => $variant->product_variant_id,
            'pr_name' => 'GH155 Regression Product',
            'unit_id' => self::PIECE_UNIT_ID,
            'pid_qty' => 8,
        ]]);

        $this->post('/accProductIssues', ['pi_id' => $piId])->assertStatus(200);

        $pi = ProductIssues::findOrFail($piId);
        $this->assertSame(2, (int) $pi->status);

        $pieceStock->refresh();
        $dosStock->refresh();

        $this->assertSame(0, $pieceStock->ps_stock, 'BUG WOULD BE: stuck at 12 Piece, never rolled up');
        $this->assertSame(2, $dosStock->ps_stock, 'existing 1 Dos + folded 12 Piece = 2 Dos');
    }
}
