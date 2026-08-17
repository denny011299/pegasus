<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Peringatan Stok Produk" / Pemesanan Min. rewrite (rubenyw, Aug 7-9 2026, no tests added
 * upstream) — App\Models\StockAlert::getStockAlert()/updateStockAlert()/updateMinOrder(), called
 * through StockController. Page-level 200/403 access is already covered by
 * tests/Smoke/MasterSmokeTest.php; this file covers the actual reorder-point/min-order math and
 * the two mutating endpoints.
 *
 * Real formula (see StockAlert::getStockAlert()):
 *   reorder_point   = ceil(avg_daily * lead_time_days + max(0, safety_stock))
 *   order_threshold = ps_min_order (manual override) ?? ps_alert_stock
 *   minim_order     = max(0, round(order_threshold - current_stock))
 * avg_daily is total sold qty over the trailing 30 days (today - 29 days .. today) / 30, from
 * sales_order_details joined to sales_orders where status = 2 (accepted).
 *
 * Fixture built fresh via Eloquent (not the committed seed snapshot) so the sales-window math is
 * exact and reproducible, following the established pattern in
 * tests/Workflow/ProductionFlowTest.php / SalesOrderRetailAndUnitConversionFlowTest.php.
 *
 * NOTE: product_stocks.ps_min_order had no migration or database/sql/*.sql patch anywhere in the
 * repo before this session — it only existed on the developer's own DB (see
 * database/okeh8644_pegasus.sql). Added
 * database/migrations/2026_08_09_130000_add_ps_min_order_to_product_stocks_table.php (+ matching
 * database/sql/product_stocks_min_order.sql patch) to close that gap; see KNOWN_ISSUES.md.
 */
class StockAlertFlowTest extends TestCase
{
    use ActingAsStaff;

    private const MAIN_WAREHOUSE_ID = 1;
    private const PIECE_UNIT_ID = 9;

    private function customerId(): int
    {
        return (int) DB::table('customers')->where('status', 1)->value('customer_id');
    }

    /** @return array{variant: ProductVariant} */
    private function createProductFixture(int $leadTimeDays = 0, int $safetyStock = 0): array
    {
        $category = new Category();
        $category->category_name = 'Stock Alert Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Stock Alert Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Stock Alert Test Variant';
        $variant->product_variant_sku = 'WF-STALERT-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->unit_id = self::PIECE_UNIT_ID;
        $variant->lead_time_days = $leadTimeDays;
        $variant->safety_stock = $safetyStock;
        $variant->safety_unit_id = self::PIECE_UNIT_ID;
        $variant->status = 1;
        $variant->save();

        return compact('variant');
    }

    private function createProductStock(ProductVariant $variant, int $stock, ?int $alertStock = null, ?int $minOrder = null): ProductStock
    {
        $ps = new ProductStock();
        $ps->product_id = $variant->product_id;
        $ps->product_variant_id = $variant->product_variant_id;
        $ps->unit_id = self::PIECE_UNIT_ID;
        $ps->warehouse_id = self::MAIN_WAREHOUSE_ID;
        $ps->ps_stock = $stock;
        $ps->ps_alert_stock = $alertStock ?? 0;
        $ps->ps_min_order = $minOrder;
        $ps->status = 1;
        $ps->save();

        return $ps;
    }

    /** Creates + accepts a Sales Order today for $qty pieces, so it lands inside the 30-day window. */
    private function sellQty(ProductVariant $variant, int $qty): void
    {
        $response = $this->post('/insertSalesOrder', [
            'so_customer' => $this->customerId(),
            'so_date' => now()->toDateString(),
            'so_total' => $qty * 1000,
            'so_img' => json_encode([]),
            'products' => json_encode([[
                'product_variant_id' => $variant->product_variant_id,
                'pr_name' => 'Stock Alert test product',
                'product_variant_name' => 'Stock Alert Test Variant',
                'product_variant_sku' => 'WF-STALERT-SKU',
                'unit_id' => self::PIECE_UNIT_ID,
                'product_variant_price' => 1000,
                'so_qty' => $qty,
                'so_subtotal' => $qty * 1000,
            ]]),
        ]);
        $response->assertStatus(200);
        $this->assertSame('1', $response->getContent());

        $soId = (int) SalesOrder::orderByDesc('so_id')->value('so_id');
        $this->post('/accSO', ['so_id' => $soId])->assertStatus(200);
    }

    private function getAlertFor(int $variantId): ?object
    {
        $response = $this->get('/getStockAlert?warehouse_id='.self::MAIN_WAREHOUSE_ID);
        $response->assertStatus(200);

        $row = collect($response->json())->first(fn ($r) => (int) $r['product_variant_id'] === $variantId);

        return $row === null ? null : (object) $row;
    }

    public function test_min_order_falls_back_to_alert_threshold_minus_current_stock_when_no_manual_override(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        // lead_time_days=5, safety_stock=10.
        $fx = $this->createProductFixture(leadTimeDays: 5, safetyStock: 10);
        // 100 in stock, sell 60 today -> 40 left.
        $this->createProductStock($fx['variant'], stock: 100, alertStock: 50);
        $this->sellQty($fx['variant'], 60);

        $row = $this->getAlertFor($fx['variant']->product_variant_id);
        $this->assertNotNull($row, 'variant must appear in the stock alert list');

        $this->assertEqualsWithDelta(2.0, $row->avg_daily, 0.0001, '60 sold / 30-day window = 2/day');
        $this->assertSame(20, $row->reorder_point, 'ceil(2*5 + 10) = 20');
        $this->assertEqualsWithDelta(40.0, $row->current_stock, 0.0001);
        $this->assertNull($row->min_order_manual);
        $this->assertFalse($row->min_order_is_manual);
        $this->assertSame(50, $row->min_order, 'threshold falls back to ps_alert_stock when no manual override');
        $this->assertSame(10, $row->minim_order, 'max(0, round(50 - 40)) = 10');
    }

    public function test_manual_min_order_override_replaces_the_alert_threshold(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $fx = $this->createProductFixture();
        // Alert threshold (5) is deliberately far lower than the manual override (80), to prove
        // the manual value wins outright rather than just nudging the result.
        $this->createProductStock($fx['variant'], stock: 40, alertStock: 5, minOrder: 80);

        $row = $this->getAlertFor($fx['variant']->product_variant_id);
        $this->assertNotNull($row);

        $this->assertSame(80, $row->min_order_manual);
        $this->assertTrue($row->min_order_is_manual);
        $this->assertSame(80, $row->min_order);
        $this->assertSame(40, $row->minim_order, 'max(0, round(80 - 40)) = 40, ps_alert_stock=5 must be ignored entirely');
    }

    public function test_min_order_never_goes_negative_when_stock_exceeds_threshold(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $fx = $this->createProductFixture();
        $this->createProductStock($fx['variant'], stock: 500, alertStock: 20);

        $row = $this->getAlertFor($fx['variant']->product_variant_id);
        $this->assertNotNull($row);
        $this->assertSame(0, $row->minim_order, 'max(0, 20 - 500) clamps to 0, never negative');
    }

    public function test_updateStockAlert_persists_ps_alert_stock_and_syncs_variant_alert(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $fx = $this->createProductFixture();
        $ps = $this->createProductStock($fx['variant'], stock: 40, alertStock: 5);

        $response = $this->post('/updateStockAlert', [
            'product_variant_id' => $fx['variant']->product_variant_id,
            'product_id' => $fx['variant']->product_id,
            'alert_stock' => 33,
            'alert_unit_id' => self::PIECE_UNIT_ID,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
        ]);
        $response->assertJson(['success' => true]);

        $ps->refresh();
        $this->assertSame(33, $ps->ps_alert_stock);
        $this->assertSame(33, $fx['variant']->fresh()->product_variant_alert);
    }

    public function test_updateMinOrder_persists_and_can_be_reset_back_to_automatic(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $fx = $this->createProductFixture();
        $ps = $this->createProductStock($fx['variant'], stock: 40, alertStock: 15);

        $this->post('/updateMinOrder', [
            'product_variant_id' => $fx['variant']->product_variant_id,
            'min_order' => 80,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
        ])->assertJson(['success' => true]);

        $ps->refresh();
        $this->assertSame(80, $ps->ps_min_order);

        $row = $this->getAlertFor($fx['variant']->product_variant_id);
        $this->assertTrue($row->min_order_is_manual);
        $this->assertSame(80, $row->min_order);

        // Empty string resets to automatic (alert-derived) mode.
        $this->post('/updateMinOrder', [
            'product_variant_id' => $fx['variant']->product_variant_id,
            'min_order' => '',
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
        ])->assertJson(['success' => true]);

        $ps->refresh();
        $this->assertNull($ps->ps_min_order);

        $row = $this->getAlertFor($fx['variant']->product_variant_id);
        $this->assertFalse($row->min_order_is_manual);
        $this->assertSame(15, $row->min_order, 'falls back to ps_alert_stock (15) once the manual override is cleared');
    }

    public function test_updateMinOrder_rejects_a_negative_value(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $fx = $this->createProductFixture();
        $ps = $this->createProductStock($fx['variant'], stock: 40);

        $this->post('/updateMinOrder', [
            'product_variant_id' => $fx['variant']->product_variant_id,
            'min_order' => -5,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
        ])->assertJson(['success' => false]);

        $ps->refresh();
        $this->assertNull($ps->ps_min_order, 'a rejected negative value must not be written');
    }
}
