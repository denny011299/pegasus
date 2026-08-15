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
 * See cdocs/testing/workflows/SALES_ORDER_FLOW.md for the base flow this extends —
 * `updateSalesOrder`'s two different code paths were deliberately deferred there. Uses a fresh,
 * isolated fixture (own product/variant/stock) so exact stock quantities can be controlled
 * precisely — the whole point of the regression test below is a specific stock-timing edge case.
 */
class SalesOrderUpdateFlowTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // Piece
    private const DOS_UNIT_ID = 7; // Dos — see the ProductRelation fixture note in createFixture()
    private const WAREHOUSE_ID = 1;

    /** @return array{variant: ProductVariant, productStock: ProductStock} */
    private function createFixture(int $startingStock): array
    {
        $category = new Category();
        $category->category_name = 'SO Update Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Update Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Update Test Variant';
        $variant->product_variant_sku = 'WF-SOUPDATE-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = $startingStock;
        $productStock->status = 1;
        $productStock->save();

        // accSO()/updateSalesOrder() both require at least one ProductRelation row per variant
        // before they'll touch stock at all ("Mohon masukkan relasi produk" otherwise) — every
        // real product in this app has one (confirmed: zero product_relations rows in the seed
        // data have a null pr_unit_id_1). DOS_UNIT_ID here is a placeholder upper unit this test
        // never stocks or orders — it exists purely so this fixture matches the shape every real
        // product actually has, see tests/Regression/SalesOrderUpdateRejectsNoOpEditOnFullyConsumedStockTest.php.
        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::UNIT_ID;
        $relation->pr_unit_value_2 = 12;
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        return compact('variant', 'productStock');
    }

    private function customerId(): int
    {
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    private function productLine(ProductVariant $variant, int $qty, ?int $sodId = null): array
    {
        $line = [
            'product_variant_id' => $variant->product_variant_id,
            'pr_name' => 'SO Update Test Product',
            'product_name' => 'SO Update Test Product', // insert uses pr_name, update uses product_name
            'product_variant_name' => $variant->product_variant_name,
            'product_variant_sku' => $variant->product_variant_sku,
            'unit_id' => self::UNIT_ID,
            'product_variant_price' => 1000,
            'so_qty' => $qty,
            'so_subtotal' => $qty * 1000,
        ];
        if ($sodId !== null) {
            $line['sod_id'] = $sodId;
        }

        return $line;
    }

    private function insertSalesOrder(ProductVariant $variant, int $qty): int
    {
        $response = $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => $qty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([$this->productLine($variant, $qty)]),
        ]);
        $response->assertStatus(200);

        return (int) SalesOrder::orderByDesc('so_id')->value('so_id');
    }

    public function test_updating_a_still_pending_so_resyncs_details_without_touching_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture(startingStock: 10);
        $soId = $this->insertSalesOrder($fx['variant'], qty: 3);
        $so = SalesOrder::findOrFail($soId);
        $this->assertSame(1, (int) $so->status);

        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();

        $response = $this->post('/updateSalesOrder', [
            'so_id' => $soId,
            'so_number' => $so->so_number,
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_invoice_no' => 'INV-TEST',
            'so_total' => 6 * 1000,
            'products' => json_encode([$this->productLine($fx['variant'], qty: 6, sodId: $detail->sod_id)]),
        ]);
        $response->assertStatus(200);
        $this->assertSame('1', $response->getContent());

        $detail->refresh();
        $this->assertSame(6, (int) $detail->sod_qty, 'pre-approval update must resync the detail row to the new qty');

        $fx['productStock']->refresh();
        $this->assertSame(10, $fx['productStock']->ps_stock, 'pre-approval update must not touch stock at all — accSO is the only mutation point');
    }

    public function test_updating_an_approved_so_with_slack_restores_then_deducts_correctly(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture(startingStock: 10);
        $soId = $this->insertSalesOrder($fx['variant'], qty: 3);
        $this->post('/accSO', ['so_id' => $soId])->assertStatus(200);

        $fx['productStock']->refresh();
        $this->assertSame(7, $fx['productStock']->ps_stock, 'approval deducts 3 from the starting 10');

        $so = SalesOrder::findOrFail($soId);
        $detail = SalesOrderDetail::where('so_id', $soId)->firstOrFail();

        // Reduce qty from 3 to 1 — well within the current 7 available, so the buildPlan check
        // (computed BEFORE the old qty is restored) passes easily either way.
        $response = $this->post('/updateSalesOrder', [
            'so_id' => $soId,
            'so_number' => $so->so_number,
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_invoice_no' => 'INV-TEST',
            'so_total' => 1 * 1000,
            'products' => json_encode([$this->productLine($fx['variant'], qty: 1, sodId: $detail->sod_id)]),
        ]);
        $response->assertStatus(200);
        $this->assertSame('1', $response->getContent());

        $fx['productStock']->refresh();
        $this->assertSame(9, $fx['productStock']->ps_stock, 'restore old 3 (7->10) then deduct new 1 (10->9)');

        $detail->refresh();
        $this->assertSame(1, (int) $detail->sod_qty);
    }
}
