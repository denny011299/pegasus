<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockAlertSupplies;
use App\Models\StockOpname;
use App\Models\StockOpnameBahan;
use App\Models\StockOpnameBahanLine;
use App\Models\Supplies;
use App\Support\PendingStockSoftBlock;
use App\Support\StockOpname\OpenOpnameGuard;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/** Trading: boleh SO Bahan; ACC tulis stok produk; SO Bahan+trading freeze mutasi produk. */
class SuppliesTradingOpnameAlertTest extends TestCase
{
    use ActingAsStaff;

    private function skipUnlessTradingSchema(): void
    {
        if (! Supplies::hasKindColumn() || ! Schema::hasColumn('supplies', 'trading_product_variant_id')) {
            $this->markTestSkipped('Kolom trading belum ada di DB testing.');
        }
    }

    private function makeTradingFixture(int $whId, float $productStock = 42): array
    {
        $unitId = (int) (\App\Models\Unit::where('status', 1)->value('unit_id') ?: 0);
        $this->assertGreaterThan(0, $unitId);

        $category = new Category();
        $category->category_name = 'Trd Opname Cat '.uniqid();
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Trd Opname Prod '.uniqid();
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
        $variant->product_variant_sku = 'TRD-OP-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->unit_id = $unitId;
        $variant->status = 1;
        $variant->save();

        $ps = new ProductStock();
        $ps->product_id = $product->product_id;
        $ps->product_variant_id = $variant->product_variant_id;
        $ps->unit_id = $unitId;
        $ps->warehouse_id = $whId;
        $ps->ps_stock = $productStock;
        $ps->status = 1;
        $ps->save();

        $supply = new Supplies();
        $supply->supplies_name = 'Trading Bahan '.uniqid();
        $supply->supplies_kind = Supplies::KIND_TRADING;
        $supply->trading_product_variant_id = $variant->product_variant_id;
        $supply->supplies_unit = json_encode([$unitId]);
        $supply->supplies_default_unit = $unitId;
        $supply->supplies_alert = 10;
        $supply->status = 1;
        $supply->save();

        return compact('unitId', 'product', 'variant', 'supply', 'ps');
    }

    public function test_insert_stock_opname_bahan_allows_trading(): void
    {
        $this->skipUnlessTradingSchema();
        $this->actingAsSuperAdminStaff();
        $whId = 1;
        $this->withActiveWarehouse($whId);

        $fx = $this->makeTradingFixture($whId);

        $res = $this->postJson('/insertStockOpnameBahan', [
            'stob_date' => now()->toDateString(),
            'is_draft' => 1,
            'item' => json_encode([[
                'supplies_id' => $fx['supply']->supplies_id,
                'stobd_notes' => '',
                'sp_units' => [[
                    'unit_id' => $fx['unitId'],
                    'real_qty' => 1,
                    'use_system_stock' => 0,
                ]],
            ]]),
        ]);

        $res->assertOk();
        $this->assertSame(1, (int) ($res->json('status') ?? 0), (string) ($res->json('message') ?? ''));
    }

    public function test_open_so_bahan_with_trading_freezes_product_domain(): void
    {
        $this->skipUnlessTradingSchema();
        $this->actingAsSuperAdminStaff();
        $whId = 1;
        $this->withActiveWarehouse($whId);

        $fx = $this->makeTradingFixture($whId);
        $staffId = (int) (session('user')->staff_id ?? 0);

        $stob = new StockOpnameBahan();
        $stob->stob_code = 'SB'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $stob->stob_date = now()->toDateString();
        $stob->warehouse_id = $whId;
        $stob->staff_id = $staffId;
        $stob->status = 1;
        $stob->is_draft = false;
        if (Schema::hasColumn('stock_opname_bahans', 'is_old_version')) {
            $stob->is_old_version = false;
        }
        $stob->created_by = $staffId;
        $stob->save();

        StockOpnameBahanLine::upsertLine([
            'stob_id' => $stob->stob_id,
            'supplies_id' => $fx['supply']->supplies_id,
            'unit_id' => $fx['unitId'],
            'sobl_counted_qty' => 10,
            'sobl_notes' => null,
        ]);

        $msg = PendingStockSoftBlock::messageIfBlocked($whId, OpenOpnameGuard::DOMAIN_PRODUCT);
        $this->assertNotNull($msg);
        $this->assertNotNull(PendingStockSoftBlock::messageIfBlocked($whId, OpenOpnameGuard::DOMAIN_SUPPLIES));
    }

    public function test_stock_alert_supplies_uses_linked_product_stock_for_trading(): void
    {
        $this->skipUnlessTradingSchema();
        $this->actingAsSuperAdminStaff();
        $whId = 1;
        $this->withActiveWarehouse($whId);

        $fx = $this->makeTradingFixture($whId, 42);

        $rows = (new StockAlertSupplies())->getStockAlertSupplies([
            'mode' => 1,
            'warehouse_id' => $whId,
        ]);
        $hit = collect($rows)->firstWhere('supplies_id', $fx['supply']->supplies_id);
        $this->assertNotNull($hit, 'Trading harus muncul di stock alert');
        $this->assertSame(1, (int) ($hit->is_trading ?? 0));
        $this->assertEqualsWithDelta(42.0, (float) $hit->current_stock, 0.01);
    }

    public function test_classify_kinds_and_soft_block_domain_for_trading(): void
    {
        $this->skipUnlessTradingSchema();
        $this->actingAsSuperAdminStaff();
        $whId = 1;
        $this->withActiveWarehouse($whId);

        $fx = $this->makeTradingFixture($whId);
        [$hasSupply, $hasTrading] = Supplies::classifyKindsForSoftBlock([$fx['supply']->supplies_id]);
        $this->assertFalse($hasSupply);
        $this->assertTrue($hasTrading);

        $sto = new StockOpname();
        $staffId = (int) (session('user')->staff_id ?? 0);
        $sto->sto_code = 'SP'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $sto->sto_date = now()->toDateString();
        $sto->warehouse_id = $whId;
        $sto->staff_id = $staffId;
        $sto->category_id = 0;
        $sto->status = 1;
        $sto->is_draft = false;
        if (Schema::hasColumn('stock_opnames', 'is_old_version')) {
            $sto->is_old_version = false;
        }
        $sto->created_by = $staffId;
        $sto->save();

        $msg = PendingStockSoftBlock::messageIfBlocked($whId, OpenOpnameGuard::DOMAIN_PRODUCT);
        $this->assertNotNull($msg);
        $this->assertNull(PendingStockSoftBlock::messageIfBlocked($whId, OpenOpnameGuard::DOMAIN_SUPPLIES));
    }

    public function test_reject_trading_supplies_ids_helper_still_for_bom(): void
    {
        $this->skipUnlessTradingSchema();
        $this->actingAsSuperAdminStaff();
        $fx = $this->makeTradingFixture(1);

        $msg = Supplies::rejectTradingSuppliesIds([$fx['supply']->supplies_id]);
        $this->assertNotNull($msg);
        $this->assertStringContainsString('BOM', $msg);
    }
}
