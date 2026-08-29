<?php

namespace Tests\Workflow;

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
 * Added 2026-08-25 with the unit-conversion coverage sweep.
 *
 * `updateSalesOrder()`'s TAHAP 1 gives back the stock of the SO's CURRENT lines before TAHAP 4
 * deducts the new ones. Whether that give-back should roll up the unit ladder depends entirely on
 * whether the line survives the edit:
 *
 * - Line still on the SO → TAHAP 4 deducts it again in the same request, and TAHAP 3's bongkar
 *   would immediately break any roll-up back down. Rolling up there is pure churn plus a pair of
 *   contradictory conversion log rows written a second apart, so it is deliberately NOT done.
 * - Line REMOVED from the SO → nothing deducts it again. The give-back is the final state, exactly
 *   like any other stock restore, so it rolls up — consistent with PO receipt
 *   (`insertPoDeliveryDetail`) and supplier-return cancellation (`deleteProductIssuesDetail`).
 *
 * Both halves are asserted here so the distinction can't be "simplified" away later without a
 * failing test explaining why it exists.
 */
class SalesOrderUpdateRollUpFlowTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE = 9;
    private const DOS = 7;

    /** @return array{0: ProductStock, 1: ProductStock, 2: ProductVariant} */
    private function createFixture(int $pieceStart, int $dosStart): array
    {
        $category = new Category();
        $category->category_name = 'SO RollUp Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO RollUp Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE, self::DOS]);
        $product->unit_id = self::PIECE;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO RollUp Variant';
        $variant->product_variant_sku = 'WF-SOROLL-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->status = 1;
        $variant->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS;
        $relation->pr_unit_id_2 = self::PIECE;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_value_2 = 12; // 1 DOS = 12 Piece
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE;
        $pieceStock->warehouse_id = 1;
        $pieceStock->ps_stock = $pieceStart;
        $pieceStock->status = 1;
        $pieceStock->save();

        $dosStock = new ProductStock();
        $dosStock->product_id = $product->product_id;
        $dosStock->product_variant_id = $variant->product_variant_id;
        $dosStock->unit_id = self::DOS;
        $dosStock->warehouse_id = 1;
        $dosStock->ps_stock = $dosStart;
        $dosStock->status = 1;
        $dosStock->save();

        return [$pieceStock, $dosStock, $variant];
    }

    private function createApprovedSo(ProductVariant $variant, int $qty): SalesOrder
    {
        $so = new SalesOrder();
        $so->so_number = 'SO-WFROLL-'.uniqid();
        $so->so_customer = (int) DB::table('customers')->where('status', 1)->value('customer_id');
        $so->so_date = now()->toDateString();
        $so->so_total = $qty * 1000;
        $so->so_ppn = 0;
        $so->so_discount = 0;
        $so->so_cost = 0;
        $so->so_img = json_encode([]);
        $so->so_invoice_no = 'INV-WFROLL-'.uniqid();
        $so->status = 2;
        $so->save();

        $sod = new SalesOrderDetail();
        $sod->so_id = $so->so_id;
        $sod->product_variant_id = $variant->product_variant_id;
        $sod->sod_nama = 'SO RollUp Product';
        $sod->sod_variant = $variant->product_variant_name;
        $sod->sod_sku = $variant->product_variant_sku;
        $sod->unit_id = self::PIECE;
        $sod->sod_harga = 1000;
        $sod->sod_qty = $qty;
        $sod->sod_subtotal = $qty * 1000;
        $sod->status = 1;
        $sod->save();

        return $so;
    }

    /**
     * A second, unrelated product line added to an SO alongside the ladder product under test, and
     * always kept unchanged across the edit -- SalesOrderStock::buildPlan() rejects an update that
     * would leave an SO with zero lines ("Tidak ada item pengiriman"), so a test of "the ladder
     * product's line gets removed entirely" needs a second line to keep the SO non-empty. Simple
     * product, no unit ladder of its own, so it can't interfere with the DOS/Piece assertions.
     *
     * @return array{0: ProductStock, 1: ProductVariant}
     */
    private function createAnchorProduct(SalesOrder $so, int $qty): array
    {
        $category = Category::first() ?? tap(new Category(), function ($c) {
            $c->category_name = 'SO RollUp Anchor Category';
            $c->status = 1;
            $c->save();
        });

        $product = new Product();
        $product->product_name = 'SO RollUp Anchor Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE]);
        $product->unit_id = self::PIECE;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO RollUp Anchor Variant';
        $variant->product_variant_sku = 'WF-SOROLL-ANCHOR-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->status = 1;
        $variant->save();

        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = self::PIECE;
        $stock->warehouse_id = 1;
        $stock->ps_stock = 100;
        $stock->status = 1;
        $stock->save();

        $sod = new SalesOrderDetail();
        $sod->so_id = $so->so_id;
        $sod->product_variant_id = $variant->product_variant_id;
        $sod->sod_nama = 'SO RollUp Anchor Product';
        $sod->sod_variant = $variant->product_variant_name;
        $sod->sod_sku = $variant->product_variant_sku;
        $sod->unit_id = self::PIECE;
        $sod->sod_harga = 1000;
        $sod->sod_qty = $qty;
        $sod->sod_subtotal = $qty * 1000;
        $sod->status = 1;
        $sod->save();

        return [$stock, $variant];
    }

    public function test_removing_a_line_rolls_its_returned_stock_up_the_ladder(): void
    {
        $this->actingAsSuperAdminStaff();

        // Start with 0 Piece / 0 DOS on hand; the SO has consumed 24 Piece.
        [$pieceStock, $dosStock, $variant] = $this->createFixture(0, 0);
        $so = $this->createApprovedSo($variant, 24);
        // Kept unchanged across the edit -- see createAnchorProduct()'s doc for why this is here.
        [, $anchorVariant] = $this->createAnchorProduct($so, 5);
        $anchorSodId = SalesOrderDetail::where('so_id', $so->so_id)
            ->where('product_variant_id', $anchorVariant->product_variant_id)
            ->value('sod_id');

        // Edit the SO down to just the anchor line — the ladder product's line is removed entirely.
        $response = $this->post('/updateSalesOrder', [
            'so_id' => $so->so_id,
            'so_number' => $so->so_number,
            'so_invoice_no' => $so->so_invoice_no,
            'so_customer' => $so->so_customer,
            'so_date' => now()->toDateString(),
            'so_total' => 5 * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'sod_id' => $anchorSodId,
                'product_variant_id' => $anchorVariant->product_variant_id,
                'product_name' => 'SO RollUp Anchor Product',
                'pr_name' => 'SO RollUp Anchor Product',
                'product_variant_name' => $anchorVariant->product_variant_name,
                'product_variant_sku' => $anchorVariant->product_variant_sku,
                'unit_id' => self::PIECE,
                'product_variant_price' => 1000,
                'so_qty' => 5,
                'so_subtotal' => 5 * 1000,
            ]]),
        ]);
        $response->assertStatus(200);

        $pieceStock->refresh();
        $dosStock->refresh();

        // 24 returned Piece = exactly 2 DOS, nothing left at the Piece level.
        $this->assertSame(2, $dosStock->ps_stock, 'the returned qty must roll up into whole DOS');
        $this->assertSame(0, $pieceStock->ps_stock, 'no remainder at the Piece level');
        $this->assertSame(
            24,
            ($dosStock->ps_stock * 12) + $pieceStock->ps_stock,
            'nothing may be created or lost by the roll-up'
        );
    }

    public function test_a_line_that_survives_the_edit_is_not_rolled_up_and_back_down(): void
    {
        $this->actingAsSuperAdminStaff();

        // 0 Piece / 3 DOS on hand; SO consumed 24 Piece.
        [$pieceStock, $dosStock, $variant] = $this->createFixture(0, 3);
        $so = $this->createApprovedSo($variant, 24);
        $sodId = SalesOrderDetail::where('so_id', $so->so_id)->value('sod_id');

        // Same line kept, quantity reduced to 6 Piece.
        $response = $this->post('/updateSalesOrder', [
            'so_id' => $so->so_id,
            'so_number' => $so->so_number,
            'so_invoice_no' => $so->so_invoice_no,
            'so_customer' => $so->so_customer,
            'so_date' => now()->toDateString(),
            'so_total' => 6 * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'sod_id' => $sodId,
                'product_variant_id' => $variant->product_variant_id,
                'product_name' => 'SO RollUp Product',
                'pr_name' => 'SO RollUp Product',
                'product_variant_name' => $variant->product_variant_name,
                'product_variant_sku' => $variant->product_variant_sku,
                'unit_id' => self::PIECE,
                'product_variant_price' => 1000,
                'so_qty' => 6,
                'so_subtotal' => 6 * 1000,
            ]]),
        ]);
        $response->assertStatus(200);

        $pieceStock->refresh();
        $dosStock->refresh();

        // Revert 24 Piece (0 -> 24), deduct the new 6 -> 18 Piece. DOS untouched at 3: the
        // surviving line was NOT rolled up, so no DOS was created and none broken back down.
        $this->assertSame(3, $dosStock->ps_stock, 'a surviving line must not churn the DOS level');
        $this->assertSame(18, $pieceStock->ps_stock, '24 returned minus the new 6 stays at Piece');
    }
}
