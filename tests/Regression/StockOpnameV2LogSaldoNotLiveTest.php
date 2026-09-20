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
 * replace-style pair of logs per counted unit representing the old value leaving and the new
 * (counted) value arriving.
 *
 * Two bugs fixed together here:
 *
 * 1. log_saldo staleness: the leg written first ran BEFORE the stock row was actually
 *    overwritten, so leaving its `log_saldo` to `LogStock::insertLog()`'s
 *    `resolveCurrentSaldo()` fallback made it read the stock as it still stood before this leg's
 *    own effect -- the Sisa column froze instead of reflecting the leg. Same root cause/fix
 *    pattern as the 3 points fixed earlier in this issue (SupplierController::accPO(),
 *    ProductionController::accDeleteProduction(), SupplierController::deleteReturnSupplies()).
 *
 * 2. Leg order: the old code wrote KELUAR (old value) first, then MASUK (new value) second --
 *    backwards from the convention decided in #167 (the "main" event is logged first, the
 *    correction leg follows -- see also SuppliesUnitStock::addQty()'s $originIsFoldedDeduction:
 *    "masuk penuh dulu, baru turunkan"). Stock Opname's own log writing was deliberately left out
 *    of the #167 fix at the time; now that the V2 schema is considered stable, order is aligned
 *    too. Swapped to MASUK first, KELUAR second.
 *
 * log_saldo for each leg reflects the running balance AS IF the legs were applied in the order
 * they're written (a narrative device, not necessarily the literal intermediate DB row state --
 * same convention used throughout the codebase's other multi-leg conversion logs):
 *   MASUK (first):  log_saldo = beforeStock + newQty  (narrative: counted result credited)
 *   KELUAR (second): log_saldo = newQty                (narrative: old value corrected back out)
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

    public function test_produk_opname_approval_writes_masuk_before_keluar_with_live_log_saldo(): void
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

        $logs = LogStock::where('log_type', 1)
            ->where('log_item_id', $variant->product_variant_id)
            ->where('log_notes', 'Stock Opname Produk')
            ->orderBy('log_id')
            ->get();
        $this->assertCount(2, $logs, 'one MASUK leg + one KELUAR leg');

        $masukLog = $logs[0];
        $keluarLog = $logs[1];

        $this->assertSame(1, (int) $masukLog->log_category, 'BUG WOULD BE: KELUAR written first (backwards from #167 convention)');
        $this->assertSame(2, (int) $keluarLog->log_category);

        $this->assertSame(9.0, (float) $masukLog->log_jumlah);
        $this->assertSame(
            14.0,
            (float) $masukLog->log_saldo,
            'BUG WOULD BE: log_saldo left to the stale/fallback value instead of beforeStock(5) + newQty(9)'
        );

        $this->assertSame(5.0, (float) $keluarLog->log_jumlah);
        $this->assertSame(9.0, (float) $keluarLog->log_saldo, 'final leg must land on the true post-opname stock');
    }

    public function test_bahan_opname_approval_writes_masuk_before_keluar_with_live_log_saldo(): void
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

        $logs = LogStock::where('log_type', 2)
            ->where('log_item_id', $supplies->supplies_id)
            ->where('log_notes', 'Stock Opname Bahan Mentah')
            ->orderBy('log_id')
            ->get();
        $this->assertCount(2, $logs, 'one MASUK leg + one KELUAR leg');

        $masukLog = $logs[0];
        $keluarLog = $logs[1];

        $this->assertSame(1, (int) $masukLog->log_category, 'BUG WOULD BE: KELUAR written first (backwards from #167 convention)');
        $this->assertSame(2, (int) $keluarLog->log_category);

        $this->assertSame(60.0, (float) $masukLog->log_jumlah);
        $this->assertSame(
            68.0,
            (float) $masukLog->log_saldo,
            'BUG WOULD BE: log_saldo left to the stale/fallback value instead of beforeStock(8) + newQty(60)'
        );

        $this->assertSame(8.0, (float) $keluarLog->log_jumlah);
        $this->assertSame(60.0, (float) $keluarLog->log_saldo, 'final leg must land on the true post-opname stock');
    }
}
