<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Stok gudang Bahan Mentah: baris trading menampilkan stok product_variant link.
 */
class SuppliesTradingWarehouseStockDisplayTest extends TestCase
{
    use ActingAsStaff;

    public function test_get_stock_supplies_shows_linked_product_stock_for_trading(): void
    {
        if (! Supplies::hasKindColumn() || ! Schema::hasColumn('supplies', 'trading_product_variant_id')) {
            $this->markTestSkipped('Kolom trading belum ada di DB testing.');
        }

        $this->actingAsSuperAdminStaff();
        $whId = 1;
        $this->withActiveWarehouse($whId);

        $unitId = (int) (\App\Models\Unit::where('status', 1)->value('unit_id') ?: 0);
        $this->assertGreaterThan(0, $unitId);

        $category = new Category();
        $category->category_name = 'Trading Stock Cat '.uniqid();
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Trading Stock Prod '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unitId]);
        $product->unit_id = $unitId;
        $product->status = 1;
        if (Schema::hasColumn('products', 'product_kind')) {
            $product->product_kind = Product::KIND_PRODUCT;
        }
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Var '.uniqid();
        $variant->product_variant_sku = 'TRD-STK-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->unit_id = $unitId;
        $variant->status = 1;
        $variant->save();

        $ps = new ProductStock();
        $ps->product_id = $product->product_id;
        $ps->product_variant_id = $variant->product_variant_id;
        $ps->unit_id = $unitId;
        $ps->warehouse_id = $whId;
        $ps->ps_stock = 42;
        $ps->status = 1;
        $ps->save();

        $supply = new Supplies();
        $supply->supplies_name = 'Trading Bahan '.uniqid();
        $supply->supplies_kind = Supplies::KIND_TRADING;
        $supply->trading_product_variant_id = $variant->product_variant_id;
        $supply->supplies_unit = json_encode([$unitId]);
        $supply->supplies_default_unit = $unitId;
        $supply->supplies_alert = 0;
        $supply->status = 1;
        $supply->save();

        $res = $this->getJson('/getStockSupplies?draw=1&start=0&length=100&warehouse_id='.$whId
            .'&search[value]='.urlencode($supply->supplies_name));
        $res->assertOk();
        $rows = collect($res->json('data') ?? []);
        $hit = $rows->firstWhere('supplies_id', $supply->supplies_id);
        $this->assertNotNull($hit, 'Baris trading harus muncul di getStockSupplies');
        $this->assertTrue((bool) ($hit['is_trading'] ?? false));
        $this->assertStringContainsString('42', (string) ($hit['supplies_variant_stock_text'] ?? ''));
    }
}
