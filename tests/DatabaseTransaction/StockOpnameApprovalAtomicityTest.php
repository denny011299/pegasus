<?php

namespace Tests\DatabaseTransaction;

use App\Models\ProductStock;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Extends the Phase 3 pilot (tests/Workflow/StockOpnameFlowTest.php,
 * cdocs/testing/workflows/STOCK_OPNAME_FLOW.md). `accStockOpname` has no `DB::transaction()`
 * (same gap shape as Purchase Order's `accPO` and Production's `accProduction`), so this documents
 * exactly how far a mid-loop failure gets.
 *
 * Trigger: `StockController::accStockOpname()` looks up `ProductStock` by
 * `product_variant_id` + `unit_id` with no null-check (`StockController.php:250-267`). A second
 * product line in the same request carries a bogus `unit_id` that matches no stock row, so the
 * lookup returns null. The FIRST log_stocks insert reads `$s->ps_stock` — a read on null is only
 * a PHP warning (evaluates to `null`), not fatal — but the very next line,
 * `$s->ps_stock = $u['real_qty'];`, is a WRITE to a property on null, which IS a fatal `Error` in
 * PHP 8. So the crash lands one line later than it looks at first glance.
 */
class StockOpnameApprovalAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private function pickTwoFixtureStocks(): array
    {
        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->where('ps_stock', '>', 10)
            ->limit(2)
            ->get()
            ->all();
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('status', 1)->value('category_id');
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_a_mid_loop_failure_leaves_the_first_item_permanently_overwritten(): void
    {
        $this->actingAsSuperAdminStaff();

        [$stockA, $stockB] = $this->pickTwoFixtureStocks();
        $startingA = $stockA->ps_stock;
        $startingB = $stockB->ps_stock;
        $bogusUnitId = 999999; // guaranteed to match no product_stocks row for stockB's variant

        $insertResponse = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'DB transaction atomicity test',
            'is_draft' => 0,
            'item' => json_encode([
                [
                    'product_id' => $stockA->product_id,
                    'product_variant_id' => $stockA->product_variant_id,
                    'stod_system' => $stockA->ps_stock.' pcs',
                    'stod_real' => ($stockA->ps_stock - 1).' pcs',
                    'stod_selisih' => '1 pcs',
                    'stod_notes' => null,
                ],
                [
                    'product_id' => $stockB->product_id,
                    'product_variant_id' => $stockB->product_variant_id,
                    'stod_system' => $stockB->ps_stock.' pcs',
                    'stod_real' => ($stockB->ps_stock - 1).' pcs',
                    'stod_selisih' => '1 pcs',
                    'stod_notes' => null,
                ],
            ]),
        ]);
        $insertResponse->assertStatus(200);
        $stoId = (int) $insertResponse->json('sto_id');

        $logCountBefore = DB::table('log_stocks')->count();

        $accResponse = $this->post('/accStockOpname', [
            'sto_id' => $stoId,
            'item' => json_encode([
                [
                    'product_variant_id' => $stockA->product_variant_id,
                    'units' => [['unit_id' => $stockA->unit_id, 'real_qty' => $startingA - 1]],
                ],
                [
                    'product_variant_id' => $stockB->product_variant_id,
                    'units' => [['unit_id' => $bogusUnitId, 'real_qty' => $startingB - 1]],
                ],
            ]),
        ]);

        // Documents current behavior: an uncaught error, not a clean {status:-1, ...} response.
        $accResponse->assertStatus(500);

        // Item A (processed first) is fully, permanently overwritten with its counted value.
        $stockA->refresh();
        $this->assertSame($startingA - 1, $stockA->ps_stock, "the first item's stock is permanently overwritten despite the second item's later crash");

        // Item B is never touched — the crash happens before its own ps_stock write.
        $stockB->refresh();
        $this->assertSame($startingB, $stockB->ps_stock, "the second (bogus-unit) item's stock is left completely untouched");

        // Item A gets both of its log_stocks rows (before + after); item B's crash happens after
        // its first (read-on-null) log row is already inserted, but before its stock write.
        $this->assertGreaterThanOrEqual($logCountBefore + 2, DB::table('log_stocks')->count());
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $stockA->product_variant_id,
            'log_jumlah' => $startingA - 1,
        ]);

        // The document itself is left stuck: neither approved nor left in its prior pending shape
        // cleanly — status was never flipped to 2, since the loop never reaches the end.
        $sto = StockOpname::find($stoId);
        $this->assertSame(1, (int) $sto->status, 'the opname is left pending, despite the first item already being permanently overwritten');
        $this->assertNull($sto->acc_by);
    }
}
