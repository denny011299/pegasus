<?php

namespace Tests\Workflow;

use App\Http\Controllers\StockController;
use App\Models\ProductStock;
use App\Models\StockOpnameDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Follow-up to GitHub #53, reported by the user after the PDF-highlight fix shipped:
 * stod_system/stod_selisih are frozen at creation/edit time and never refresh. The race that
 * exposes it: Opname A is created (freezing "system stock" at that instant), then — before A is
 * approved — Opname B for the SAME product is approved, which overwrites the real system stock.
 * A's PDF, if printed while still pending, would silently show the stale pre-B system figure.
 *
 * Fix (StockController):
 *  - accStockOpname()/accStockOpnameBahan() now re-freeze stod_system/stod_selisih (Bahan:
 *    stobd_*) to the TRUE value used at approval — the same "before" figure already written to
 *    log_stocks — instead of leaving the creation-time snapshot in place.
 *  - refreshLiveSystemQty() (called from generateStockOpname()/generateStockOpnameBahan() only
 *    for status==1/pending docs) recomputes system/selisih live from the already-attached
 *    ->stock collection (see ProductVariant::getProductVariantBulk()/Supplies::getSuppliesBulk())
 *    instead of trusting the stored string — an approved/rejected doc's frozen snapshot is a
 *    deliberate historical record and must NOT be recomputed live.
 */
class StockOpnamePdfLiveSystemStockTest extends TestCase
{
    use ActingAsStaff;

    private function pickFixtureStock(): ProductStock
    {
        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1) // Gudang Pusat (main), seeded 2026-08-01
            ->where('ps_stock', '>', 30)
            ->firstOrFail();
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('status', 1)->value('category_id');
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    private function unitShortName(ProductStock $stock): string
    {
        return (string) (Unit::find($stock->unit_id)->unit_short_name ?? 'pcs');
    }

    private function insertStockOpname(ProductStock $stock, string $unit, int $realQty): int
    {
        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Live system stock race test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $stock->product_id,
                'product_variant_id' => $stock->product_variant_id,
                'stod_system' => $stock->ps_stock.' '.$unit,
                'stod_real' => $realQty.' '.$unit,
                'stod_selisih' => ($realQty - $stock->ps_stock).' '.$unit,
                'stod_notes' => null,
                'stod_touched' => 1,
            ]]),
        ]);
        $response->assertStatus(200);

        return (int) $response->json('sto_id');
    }

    private function approve(ProductStock $stock, int $stoId, int $realQty): void
    {
        $this->post('/accStockOpname', [
            'sto_id' => $stoId,
            'item' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'units' => [[
                    'unit_id' => $stock->unit_id,
                    'real_qty' => $realQty,
                ]],
            ]]),
        ])->assertStatus(200);
    }

    private function parseQty(?string $string, string $unit): ?int
    {
        if (!$string) {
            return null;
        }
        foreach (explode(',', $string) as $part) {
            [$qty, $u] = array_pad(explode(' ', trim($part)), 2, null);
            if ($u === $unit) {
                return (int) $qty;
            }
        }
        return null;
    }

    public function test_a_still_pending_document_pdf_data_reflects_live_stock_not_the_stale_creation_time_snapshot(): void
    {
        $this->actingAsSuperAdminStaff();
        $stock = $this->pickFixtureStock();
        $unit = $this->unitShortName($stock);
        $startingStock = $stock->ps_stock;

        // A created first, matching system stock at that instant.
        $stoIdA = $this->insertStockOpname($stock, $unit, realQty: $startingStock);

        // Before A is approved, B (same product) is approved and moves the real stock.
        $stoIdB = $this->insertStockOpname($stock, $unit, realQty: $startingStock - 15);
        $this->approve($stock, $stoIdB, $startingStock - 15);

        $stock->refresh();
        $this->assertSame($startingStock - 15, $stock->ps_stock, "sanity: B's approval must have moved the live stock");

        // Prove the stored row really is stale (this is the bug being guarded against).
        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoIdA,
            'product_variant_id' => $stock->product_variant_id,
            'stod_system' => $startingStock.' '.$unit,
        ]);

        // What generateStockOpname() feeds the PDF for a still-pending doc must be live, not that
        // stale row -- refreshLiveSystemQty() is private, called only from there, so invoke it
        // directly (ReflectionMethod is already an established pattern in this suite, see
        // tests/Health/SchemaConsistencyTest.php).
        $detail = StockOpnameDetail::getDetail(['sto_id' => $stoIdA]);
        $refresh = new ReflectionMethod(StockController::class, 'refreshLiveSystemQty');
        $refresh->setAccessible(true);
        $refresh->invoke(new StockController(), $detail, 'stod_real', 'stod_system', 'stod_selisih', 'ps_stock');

        $row = $detail->firstWhere('product_variant_id', $stock->product_variant_id);
        $this->assertSame($startingStock - 15, $this->parseQty($row->stod_system, $unit), 'PDF data for a pending doc must show the CURRENT live system stock');
        $this->assertSame(15, $this->parseQty($row->stod_selisih, $unit), 'selisih must be recomputed against the live system stock too');
    }

    public function test_approving_freezes_the_true_system_stock_at_that_moment_not_the_creation_time_one(): void
    {
        $this->actingAsSuperAdminStaff();
        $stock = $this->pickFixtureStock();
        $unit = $this->unitShortName($stock);
        $startingStock = $stock->ps_stock;

        $stoIdA = $this->insertStockOpname($stock, $unit, realQty: $startingStock);
        $stoIdB = $this->insertStockOpname($stock, $unit, realQty: $startingStock - 15);
        $this->approve($stock, $stoIdB, $startingStock - 15);

        // A is approved with real_qty = $startingStock (the count taken before B's approval
        // moved anything) -- accStockOpname() re-fetches live stock right before overwriting, so
        // the true "before" value here is $startingStock - 15, NOT the stale creation-time
        // $startingStock baked into A's stod_system.
        $this->approve($stock, $stoIdA, $startingStock);

        $row = StockOpnameDetail::where('sto_id', $stoIdA)
            ->where('product_variant_id', $stock->product_variant_id)
            ->firstOrFail();

        $this->assertSame($startingStock - 15, $this->parseQty($row->stod_system, $unit), 'approval must freeze the TRUE system stock at approval time');
        $this->assertSame(15, $this->parseQty($row->stod_selisih, $unit), 'the frozen selisih must reflect the true discrepancy found at approval, not a stale one');

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, "A's approval must still overwrite with A's own real_qty, unaffected by this fix");
    }
}
