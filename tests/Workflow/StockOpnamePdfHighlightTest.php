<?php

namespace Tests\Workflow;

use App\Models\ProductStock;
use App\Models\StockOpnameDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #53: the Stock Opname PDF (Backoffice/PDF/Opname.blade.php) must show three highlight
 * states per row, not the old binary yellow/none:
 *   - untouched (real-stock input left blank client-side, real qty just defaulted to system qty)
 *     -> no highlight
 *   - touched, no selisih (staff typed a value and it matches the system) -> green
 *   - touched, has selisih -> yellow (unchanged behaviour)
 *
 * See cdocs/testing/workflows/STOCK_OPNAME_FLOW.md — stod_system/stod_real/stod_selisih are pure
 * display strings never read by accStockOpname(), so stod_touched is purely a display flag too.
 */
class StockOpnamePdfHighlightTest extends TestCase
{
    use ActingAsStaff;

    private const YELLOW = 'background-color: #FFF9C4;';
    private const GREEN = 'background-color: #C8E6C9;';

    private function pickFixtureStocks(int $count): \Illuminate\Support\Collection
    {
        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1) // Gudang Pusat (main), seeded 2026-08-01
            ->where('ps_stock', '>', 10)
            ->limit($count)
            ->get();
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('status', 1)->value('category_id');
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_insert_persists_stod_touched_per_row_matching_what_the_frontend_sends(): void
    {
        $this->actingAsSuperAdminStaff();

        $stocks = $this->pickFixtureStocks(2);
        $this->assertCount(2, $stocks, 'fixture needs at least 2 distinct product_stocks rows in Gudang Pusat');
        [$touchedStock, $untouchedStock] = $stocks;

        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'PDF highlight test',
            'is_draft' => 0,
            'item' => json_encode([
                [
                    'product_id' => $touchedStock->product_id,
                    'product_variant_id' => $touchedStock->product_variant_id,
                    'stod_system' => $touchedStock->ps_stock.' pcs',
                    'stod_real' => $touchedStock->ps_stock.' pcs',
                    'stod_selisih' => '0 pcs',
                    'stod_notes' => null,
                    // What CreateStockOpname.js now sends when the staff actually typed a value.
                    'stod_touched' => 1,
                ],
                [
                    'product_id' => $untouchedStock->product_id,
                    'product_variant_id' => $untouchedStock->product_variant_id,
                    'stod_system' => $untouchedStock->ps_stock.' pcs',
                    'stod_real' => $untouchedStock->ps_stock.' pcs',
                    'stod_selisih' => '0 pcs',
                    'stod_notes' => null,
                    // Left blank client-side -> real qty auto-defaulted to system qty, not typed.
                    'stod_touched' => 0,
                ],
            ]),
        ]);
        $response->assertStatus(200);
        $stoId = (int) $response->json('sto_id');

        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoId,
            'product_variant_id' => $touchedStock->product_variant_id,
            'stod_touched' => 1,
        ]);
        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoId,
            'product_variant_id' => $untouchedStock->product_variant_id,
            'stod_touched' => 0,
        ]);

        // getDetail() (what generateStockOpname() feeds the PDF view) must round-trip the flag.
        $detail = StockOpnameDetail::getDetail(['sto_id' => $stoId])->keyBy('product_variant_id');
        $this->assertSame(1, (int) $detail[$touchedStock->product_variant_id]->stod_touched);
        $this->assertSame(0, (int) $detail[$untouchedStock->product_variant_id]->stod_touched);
    }

    private function renderOpnameRow(array $item): string
    {
        return View::make('Backoffice.PDF.Opname', [
            'stockOpname' => ['sto_code' => 'SP0001', 'sto_date' => now()->toDateString(), 'sto_notes' => null],
            'staff_name' => ['staff_name' => 'Tester'],
            'status' => 'Disetujui',
            'detail' => [$item],
        ])->render();
    }

    public function test_untouched_row_gets_no_highlight(): void
    {
        $html = $this->renderOpnameRow([
            'product_variant_sku' => 'SKU-UNTOUCHED',
            'pr_name' => 'Produk Untouched',
            'product_variant_name' => 'Default',
            'stod_system' => '10 pcs',
            'stod_real' => '10 pcs',
            'stod_selisih' => '0 pcs',
            'stod_notes' => null,
            'stod_touched' => 0,
        ]);

        $this->assertStringNotContainsString(self::YELLOW, $html);
        $this->assertStringNotContainsString(self::GREEN, $html);
    }

    public function test_touched_row_with_no_selisih_gets_green_highlight(): void
    {
        $html = $this->renderOpnameRow([
            'product_variant_sku' => 'SKU-MATCH',
            'pr_name' => 'Produk Match',
            'product_variant_name' => 'Default',
            'stod_system' => '10 pcs',
            'stod_real' => '10 pcs',
            'stod_selisih' => '0 pcs',
            'stod_notes' => null,
            'stod_touched' => 1,
        ]);

        $this->assertStringContainsString(self::GREEN, $html);
        $this->assertStringNotContainsString(self::YELLOW, $html);
    }

    public function test_touched_row_with_selisih_gets_yellow_highlight(): void
    {
        $html = $this->renderOpnameRow([
            'product_variant_sku' => 'SKU-DIFF',
            'pr_name' => 'Produk Diff',
            'product_variant_name' => 'Default',
            'stod_system' => '10 pcs',
            'stod_real' => '8 pcs',
            'stod_selisih' => '-2 pcs',
            'stod_notes' => null,
            'stod_touched' => 1,
        ]);

        $this->assertStringContainsString(self::YELLOW, $html);
        $this->assertStringNotContainsString(self::GREEN, $html);
    }
}
