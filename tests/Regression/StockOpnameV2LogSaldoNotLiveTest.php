<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\Unit;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #194 (found during manual verification, not one of the 3 originally reported points):
 * `accStockOpnameV2()` (Produk) and `accStockOpnameBahanV2()` (Bahan) -- the live approval path
 * for the versioned Stock Opname schema, see `pegasus-stockopname-v2-redesign` -- write a
 * replace-style pair of logs per counted unit: a KELUAR leg carrying the OLD value, then a MASUK
 * leg carrying the NEW (counted) value. The KELUAR leg is written BEFORE the stock row is
 * actually overwritten, so leaving its `log_saldo` to `LogStock::insertLog()`'s
 * `resolveCurrentSaldo()` fallback makes it read the stock as it still stood before this leg's
 * own effect -- the Sisa column freezes instead of showing "0" (this leg's own credit fully
 * replaced by the very next leg). Same root cause/fix pattern as the 3 points fixed earlier in
 * this issue (SupplierController::accPO(), ProductionController::accDeleteProduction(),
 * SupplierController::deleteReturnSupplies()) -- log_saldo must be computed explicitly, not left
 * to the fallback, whenever a log is written at a point in time that doesn't line up with its own
 * stock mutation.
 *
 * This was explicitly OUT of scope for the #167 log-ordering fix (which only reordered
 * SupplierController's accPO()/deleteReturnSupplies() calls) -- Stock Opname's own log writing
 * was deliberately left untouched at the time.
 */
class StockOpnameV2LogSaldoNotLiveTest extends TestCase
{
    use ActingAsStaff;

    private array $units = [];

    protected function setUp(): void
    {
        parent::setUp();
        $rows = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $rows->count(), 'fixture butuh minimal 2 satuan aktif');
        $this->units = ['dos' => $rows[0], 'pcs' => $rows[1]];
    }

    public function test_produk_opname_approval_writes_a_live_log_saldo_for_the_keluar_leg(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'GH194 Opname LogSaldo Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'GH194 Opname LogSaldo Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$this->units['dos']->unit_id]);
        $product->unit_id = $this->units['dos']->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'GH194 Opname LogSaldo Variant';
        $variant->product_variant_sku = 'WF-GH194-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = $this->units['dos']->unit_id;
        $stock->warehouse_id = 1;
        $stock->ps_stock = 5;
        $stock->status = 1;
        $stock->save();

        $staffId = (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');

        $insertResponse = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $staffId,
            'category_id' => $category->category_id,
            'sto_notes' => 'GH194 test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $product->product_id,
                'product_variant_id' => $variant->product_variant_id,
                'units' => [[
                    'unit_id' => $this->units['dos']->unit_id,
                    'system_qty' => 5,
                    'real_qty' => 9,
                    'use_system_stock' => 0,
                ]],
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $stoId = (int) $insertResponse->json('sto_id');

        $this->post('/accStockOpname', ['sto_id' => $stoId, 'item' => json_encode([])])
            ->assertStatus(200);

        $stock->refresh();
        $this->assertSame(9, (int) $stock->ps_stock, 'precondition: stock counted to 9');

        $keluarLog = LogStock::where('log_type', 1)
            ->where('log_item_id', $variant->product_variant_id)
            ->where('log_category', 2)
            ->where('log_notes', 'Stock Opname Produk')
            ->firstOrFail();
        $masukLog = LogStock::where('log_type', 1)
            ->where('log_item_id', $variant->product_variant_id)
            ->where('log_category', 1)
            ->where('log_notes', 'Stock Opname Produk')
            ->firstOrFail();

        $this->assertSame(
            0.0,
            (float) $keluarLog->log_saldo,
            'BUG WOULD BE: log_saldo reads the pre-mutation 5 instead of 0 (this leg zeroes the row)'
        );
        $this->assertSame(9.0, (float) $masukLog->log_saldo);
    }

    public function test_bahan_opname_approval_writes_a_live_log_saldo_for_the_keluar_leg(): void
    {
        $this->actingAsSuperAdminStaff();

        $supplies = new Supplies();
        $supplies->supplies_name = 'GH194 Opname LogSaldo Ingredient '.uniqid();
        $supplies->supplies_unit = json_encode([$this->units['dos']->unit_id]);
        $supplies->supplies_default_unit = $this->units['dos']->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $stock = new SuppliesStock();
        $stock->supplies_id = $supplies->supplies_id;
        $stock->unit_id = $this->units['dos']->unit_id;
        $stock->warehouse_id = 1;
        $stock->ss_stock = 8;
        $stock->status = 1;
        $stock->save();

        $staffId = (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');

        $insertResponse = $this->post('/insertStockOpnameBahan', [
            'stob_date' => now()->toDateString(),
            'staff_id' => $staffId,
            'stob_notes' => 'GH194 test',
            'is_draft' => 0,
            'item' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'sp_units' => [[
                    'unit_id' => $this->units['dos']->unit_id,
                    'system_qty' => 8,
                    'real_qty' => 60,
                ]],
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $stobId = (int) $insertResponse->json('stob_id');

        $this->post('/accStockOpnameBahan', ['stob_id' => $stobId, 'item' => json_encode([])])
            ->assertStatus(200);

        $stock->refresh();
        $this->assertSame(60, (int) $stock->ss_stock, 'precondition: stock counted to 60');

        $keluarLog = LogStock::where('log_type', 2)
            ->where('log_item_id', $supplies->supplies_id)
            ->where('log_category', 2)
            ->where('log_notes', 'Stock Opname Bahan Mentah')
            ->firstOrFail();
        $masukLog = LogStock::where('log_type', 2)
            ->where('log_item_id', $supplies->supplies_id)
            ->where('log_category', 1)
            ->where('log_notes', 'Stock Opname Bahan Mentah')
            ->firstOrFail();

        $this->assertSame(
            0.0,
            (float) $keluarLog->log_saldo,
            'BUG WOULD BE: log_saldo reads the pre-mutation 8 instead of 0 (this leg zeroes the row)'
        );
        $this->assertSame(60.0, (float) $masukLog->log_saldo);
    }
}
