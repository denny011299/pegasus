<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-25): third and last instance of the "bongkar resolves the larger unit by ARRAY
 * POSITION instead of by relation" bug.
 *
 * The same bug shape was found and fixed twice before — 2026-08-05 in
 * `ProductIssuesDetail::stockCheck()`'s two closures (see
 * `ReturnSuppliesBongkarFailsOnStockRowInsertionOrderTest`) and 2026-08-06 in
 * `StockController::deleteProductIssue()`, whose fixed version even carries the comment "cari unit
 * atas via relasi, tidak bergantung index". `CustomerController::updateSalesOrder()` was missed by
 * both passes and kept using `$units[$targetKey + 1]` on a collection ordered by `ps_id DESC`.
 *
 * Why that is worse here than in the earlier two: this closure DOES query the correct relation
 * (`$sr`, matching `pr_unit_id_2` to the current unit) but then never uses `$sr->pr_unit_id_1` to
 * decide which row to decrement. It decrements whichever row sat at `$targetKey + 1` while
 * crediting the current unit at `$sr->pr_unit_value_2`. So when `ps_id` order happened not to match
 * the ladder order, it did not merely fail to find stock — it broke down the WRONG unit at ANOTHER
 * unit's ratio, silently corrupting both rows.
 *
 * This test pins the plain 2-level case with the smaller unit's `ProductStock` row created FIRST
 * (the reproducing order — equally plausible in real provisioning). Before the fix,
 * `$targetKey + 1` ran off the end of the array and bongkar returned false immediately, so the
 * edit was rejected as "insufficient stock" even though combined physical stock was ample.
 */
class SalesOrderUpdateBongkarFailsOnStockRowInsertionOrderTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE = 9;
    private const DOS = 7;

    public function test_editing_an_approved_so_bongkars_correctly_when_the_smaller_units_row_was_created_first(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'SO Bongkar Order Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Bongkar Order Regression Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE, self::DOS]);
        $product->unit_id = self::PIECE;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Bongkar Order Regression Variant';
        $variant->product_variant_sku = 'REG-SOBONGKAR-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->status = 1;
        $variant->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS;   // larger unit
        $relation->pr_unit_id_2 = self::PIECE; // smaller unit
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_value_2 = 12;       // 1 DOS = 12 Piece
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        // Smaller unit's row created FIRST (lower ps_id) — the reproducing order. With
        // `orderBy('ps_id','desc')` this lands the Piece row at the LAST index, so the old
        // `$targetKey + 1` ran off the end of the array and never found the DOS row.
        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE;
        $pieceStock->warehouse_id = 1;
        $pieceStock->ps_stock = 5; // not enough alone
        $pieceStock->status = 1;
        $pieceStock->save();

        $dosStock = new ProductStock();
        $dosStock->product_id = $product->product_id;
        $dosStock->product_variant_id = $variant->product_variant_id;
        $dosStock->unit_id = self::DOS;
        $dosStock->warehouse_id = 1;
        $dosStock->ps_stock = 4; // 4 DOS = 48 Piece — ample combined with the 5 Piece
        $dosStock->status = 1;
        $dosStock->save();

        // An already-approved SO for 5 Piece (matching what was deducted from the Piece row).
        $so = new SalesOrder();
        $so->so_number = 'SO-REG-BONGKAR-'.uniqid();
        $so->so_customer = (int) DB::table('customers')->where('status', 1)->value('customer_id');
        $so->so_date = now()->toDateString();
        $so->so_total = 5 * 1000;
        $so->so_ppn = 0;
        $so->so_discount = 0;
        $so->so_cost = 0;
        $so->so_img = json_encode([]);
        $so->so_invoice_no = 'INV-REG-BONGKAR-'.uniqid();
        $so->status = 2;
        $so->save();

        $sod = new SalesOrderDetail();
        $sod->so_id = $so->so_id;
        $sod->product_variant_id = $variant->product_variant_id;
        $sod->sod_nama = 'SO Bongkar Order Regression Product';
        $sod->sod_variant = $variant->product_variant_name;
        $sod->sod_sku = $variant->product_variant_sku;
        $sod->unit_id = self::PIECE;
        $sod->sod_harga = 1000;
        $sod->sod_qty = 5;
        $sod->sod_subtotal = 5 * 1000;
        $sod->status = 1;
        $sod->save();

        // Edit up to 20 Piece. Revert puts back 5 (Piece row -> 10), still short of 20, so bongkar
        // must break open DOS rows to cover it.
        $newQty = 20;
        $response = $this->post('/updateSalesOrder', [
            'so_id' => $so->so_id,
            'so_number' => $so->so_number,
            'so_invoice_no' => $so->so_invoice_no,
            'so_customer' => $so->so_customer,
            'so_date' => now()->toDateString(),
            'so_total' => $newQty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'sod_id' => $sod->sod_id,
                'product_variant_id' => $variant->product_variant_id,
                'product_name' => 'SO Bongkar Order Regression Product',
                'pr_name' => 'SO Bongkar Order Regression Product',
                'product_variant_name' => $variant->product_variant_name,
                'product_variant_sku' => $variant->product_variant_sku,
                'unit_id' => self::PIECE,
                'product_variant_price' => 1000,
                'so_qty' => $newQty,
                'so_subtotal' => $newQty * 1000,
            ]]),
        ]);

        $response->assertStatus(200);
        $this->assertSame(
            '1',
            trim($response->getContent()),
            'BUG WOULD BE: rejected as insufficient stock because bongkar never found the DOS row'
        );

        $pieceStock->refresh();
        $dosStock->refresh();

        // Revert 5 -> Piece 10. Need 20, so one DOS is broken open: DOS 4 -> 3, Piece 10 + 12 = 22.
        // Then deduct the new 20: Piece 22 - 20 = 2.
        $this->assertSame(3, $dosStock->ps_stock, 'exactly one DOS must be broken open, from the DOS row itself');
        $this->assertSame(2, $pieceStock->ps_stock, 'Piece = 5 (revert) + 5 (existing) + 12 (bongkar) - 20 (new qty)');

        // Total physical stock is conserved: started 4 DOS + 5 Piece = 53 Piece-equivalent,
        // with 5 already consumed by the original SO. After editing to 20: 53 - 20 + 5 = 38.
        $this->assertSame(
            38,
            ($dosStock->ps_stock * 12) + $pieceStock->ps_stock,
            'no stock may be created or destroyed by the bongkar itself'
        );
    }
}
