<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\Support\ResolvesTestWarehouses;
use Tests\TestCase;

/**
 * GitHub #116: Pengiriman had no early stock-availability check while building the "Daftar
 * Produk" list in the Tambah/Update Pengiriman modal — the user only found out stock was
 * short at ACC, after already filling in the whole document. This mirrors Produksi's
 * add-time check (GitHub #101/#105, see ProductionCheckStockOnAddTest) but scoped to ONE
 * line per call, not the whole candidate list: POST /checkSalesOrderStock simulates
 * SalesOrderStock::buildPlan() read-only against just the single row being added (or whose
 * gudang eceran dropdown was just changed) — the frontend only calls this once that row's
 * warehouse is already known (main warehouse, staff's own active retail warehouse, or the
 * row's freshly-picked gudang eceran). A row using the eceran unit with no warehouse picked
 * yet never calls this at all — it's added straight to the list, and "wajib pilih gudang
 * eceran" is enforced client-side, focused on that row, only at final submit.
 *
 * Checking one row per call (instead of the whole array, as an earlier version of this fix
 * did) also matters functionally: re-simulating every earlier row on every new add would
 * make an already-known shortage on row 1 pop the error again every time row 2, 3, ... are
 * added, even though nothing about row 1 changed.
 *
 * This is a UI early-warning only — it does NOT touch insertSalesOrder()'s behavior, which
 * stays intentionally unblocked by current stock (GitHub #99, see
 * SalesOrderCreationIsNotBlockedByCurrentStockTest): stock can still arrive between filing
 * and ACC, and the real check+deduction stays at accSO().
 */
class SalesOrderCheckStockOnAddTest extends TestCase
{
    use ActingAsStaff;
    use ResolvesTestWarehouses;

    private const UNIT_ID = 9; // Piece
    private const DOS_UNIT_ID = 7; // Dos — placeholder upper unit, never stocked or ordered here
    private const WAREHOUSE_ID = 1;

    /** @return array{variant: ProductVariant, productStock: ProductStock} */
    private function createFixture(int $startingStock): array
    {
        $category = new Category();
        $category->category_name = 'SO Check Stock Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Check Stock Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Check Stock Test Variant';
        $variant->product_variant_sku = 'RG-SOCHECK-'.uniqid();
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

    private function productLine(ProductVariant $variant, int $qty): array
    {
        return [
            'product_variant_id' => $variant->product_variant_id,
            'pr_name' => 'SO Check Stock Test Product',
            'product_name' => 'SO Check Stock Test Product',
            'product_variant_name' => $variant->product_variant_name,
            'product_variant_sku' => $variant->product_variant_sku,
            'unit_id' => self::UNIT_ID,
            'product_variant_price' => 1000,
            'so_qty' => $qty,
            'so_subtotal' => $qty * 1000,
        ];
    }

    public function test_check_refuses_when_stock_is_short(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture(startingStock: 2);

        $response = $this->post('/checkSalesOrderStock', [
            'products' => json_encode([$this->productLine($fx['variant'], 5)]),
        ]);

        $response->assertStatus(200);
        $payload = $response->json();
        $this->assertNotSame(1, $payload['status'], 'add-row check must refuse when candidate list exceeds current stock');
        $this->assertNotEmpty($payload['message'] ?? null);

        $fx['productStock']->refresh();
        $this->assertSame(2, (int) $fx['productStock']->ps_stock, 'the check is read-only and must not touch stock');
    }

    public function test_check_passes_when_stock_is_sufficient(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture(startingStock: 10);

        $response = $this->post('/checkSalesOrderStock', [
            'products' => json_encode([$this->productLine($fx['variant'], 5)]),
        ]);

        $response->assertStatus(200);
        $payload = $response->json();
        $this->assertSame(1, $payload['status']);

        $fx['productStock']->refresh();
        $this->assertSame(10, (int) $fx['productStock']->ps_stock, 'the check is read-only and must not touch stock');
    }

    /**
     * A retail-unit line dropped into the modal before the footer's "gudang eceran" select is
     * filled in must NOT trip a "Gudang eceran wajib" error on add-row — that gate stays at
     * final submit (insertSalesOrder()/updateSalesOrder()) only. The add-time check simply
     * skips such a line (no warehouse to check stock against yet) instead of failing it.
     */
    public function test_check_does_not_demand_a_retail_warehouse_yet_on_add(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'SO Check Stock Retail Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Check Stock Retail Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Check Stock Retail Test Variant';
        $variant->product_variant_sku = 'RG-SOCHECKRETAIL-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->retail_unit = self::UNIT_ID;
        $variant->status = 1;
        $variant->save();

        $line = $this->productLine($variant, 5);
        // No warehouse_id on the line and no retail_warehouse_id posted — the footer's gudang
        // eceran select hasn't been touched yet, same as right after clicking "+ Tambah".
        $response = $this->post('/checkSalesOrderStock', [
            'products' => json_encode([$line]),
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('status'), 'add-row check must not demand a retail warehouse before final submit');
    }

    /**
     * A row picking its gudang eceran (the frontend's .so-retail-warehouse dropdown) sends a
     * single line with that warehouse_id resolved. This checks stock against exactly the
     * warehouse picked, not the main one — a retail-unit line has no stock at all in the main
     * warehouse (see SalesOrderRetailAndUnitConversionFlowTest), so it must not be flagged
     * short just because the check happened to look at the wrong warehouse.
     */
    public function test_check_scopes_a_retail_row_to_its_own_picked_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'SO Check Stock Row Warehouse Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'SO Check Stock Row Warehouse Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'SO Check Stock Row Warehouse Test Variant';
        $variant->product_variant_sku = 'RG-SOCHECKROWWH-'.uniqid();
        $variant->product_variant_price = 1000;
        $variant->retail_unit = self::UNIT_ID;
        $variant->status = 1;
        $variant->save();

        $retailWarehouseId = $this->resolveActiveRetailWarehouseId('Pengiriman');
        $this->assignWarehousesToActingStaff(self::WAREHOUSE_ID, $retailWarehouseId);
        $retailStock = new ProductStock();
        $retailStock->product_id = $product->product_id;
        $retailStock->product_variant_id = $variant->product_variant_id;
        $retailStock->unit_id = self::UNIT_ID;
        $retailStock->warehouse_id = $retailWarehouseId;
        $retailStock->ps_stock = 10;
        $retailStock->status = 1;
        $retailStock->save();
        // Deliberately no ProductStock row at all for the main warehouse.

        $line = [
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::UNIT_ID,
            'so_qty' => 5,
            'warehouse_id' => $retailWarehouseId,
        ];

        $response = $this->post('/checkSalesOrderStock', [
            'products' => json_encode([$line]),
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('status'), 'must check the row against its own picked warehouse, not the main one');
    }

    /**
     * The core bug report behind this follow-up: adding a second row must not re-surface an
     * already-known shortage on the first row. Since each add now only sends the one row being
     * added, a shortage on an earlier row (already reported once) simply never appears again in
     * a later call it wasn't included in.
     */
    public function test_adding_a_second_row_does_not_re_flag_an_earlier_rows_shortage(): void
    {
        $this->actingAsSuperAdminStaff();

        $fxShort = $this->createFixture(startingStock: 0);
        $fxOk = $this->createFixture(startingStock: 10);

        // Row 1 (short) is checked and correctly flagged once.
        $firstCheck = $this->post('/checkSalesOrderStock', [
            'products' => json_encode([$this->productLine($fxShort['variant'], 5)]),
        ]);
        $firstCheck->assertStatus(200);
        $this->assertNotSame(1, $firstCheck->json('status'));

        // Row 2 (fine) is added afterwards — the request for row 2 alone must not re-mention
        // row 1's shortage at all.
        $secondCheck = $this->post('/checkSalesOrderStock', [
            'products' => json_encode([$this->productLine($fxOk['variant'], 5)]),
        ]);
        $secondCheck->assertStatus(200);
        $this->assertSame(1, $secondCheck->json('status'));
    }

    public function test_check_still_lets_a_short_document_be_created_per_github_99(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture(startingStock: 0);

        // The add-time check refuses the row...
        $checkResponse = $this->post('/checkSalesOrderStock', [
            'products' => json_encode([$this->productLine($fx['variant'], 5)]),
        ]);
        $checkResponse->assertStatus(200);
        $this->assertNotSame(1, $checkResponse->json('status'));

        // ...but insertSalesOrder() itself (final submit, bypassing the popup's per-row gate,
        // e.g. an already-built document reopened later) must stay unblocked — GitHub #99.
        $response = $this->post('/insertSalesOrder', [
            'so_customer' => (int) DB::table('customers')->where('status', 1)->value('customer_id'),
            'so_date' => now()->toDateString(),
            'so_total' => 5 * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([$this->productLine($fx['variant'], 5)]),
        ]);
        $response->assertStatus(200);
        $this->assertSame('1', trim($response->getContent(), '"'));
    }
}
