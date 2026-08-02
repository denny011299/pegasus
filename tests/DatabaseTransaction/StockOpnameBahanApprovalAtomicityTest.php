<?php

namespace Tests\DatabaseTransaction;

use App\Models\StockOpnameBahan;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Mirrors tests/DatabaseTransaction/StockOpnameApprovalAtomicityTest.php exactly, one level down
 * (Bahan instead of Produk) — confirms the identical missing-transaction/null-guard gap exists on
 * this side too, not just assumed from the Produk-side finding.
 *
 * Trigger: `StockController::accStockOpnameBahan()` looks up `SuppliesStock` by `supplies_id` +
 * `unit_id` with no null-check (`StockController.php:481-498`). A second supplies line in the same
 * request carries a bogus `unit_id` that matches no stock row, so the lookup returns null. The
 * FIRST log_stocks insert reads `$s->ss_stock` — a read on null is only a PHP warning, not fatal —
 * but the very next line, `$s->ss_stock = $u['real_qty'];`, is a WRITE to a property on null,
 * which IS a fatal `Error` in PHP 8.
 */
class StockOpnameBahanApprovalAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private function pickTwoFixtureStocks(): array
    {
        return SuppliesStock::where('status', 1)
            ->where('ss_stock', '>', 10)
            ->limit(2)
            ->get()
            ->all();
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_a_mid_loop_failure_leaves_the_first_item_permanently_overwritten(): void
    {
        $this->actingAsSuperAdminStaff();

        [$stockA, $stockB] = $this->pickTwoFixtureStocks();
        $startingA = $stockA->ss_stock;
        $startingB = $stockB->ss_stock;
        $bogusUnitId = 999999; // guaranteed to match no supplies_stocks row for stockB's supply

        $insertResponse = $this->post('/insertStockOpnameBahan', [
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => 'DB transaction atomicity test (Bahan)',
            'is_draft' => 0,
            'item' => json_encode([
                [
                    'supplies_id' => $stockA->supplies_id,
                    'stobd_system' => $stockA->ss_stock.' pcs',
                    'stobd_real' => ($stockA->ss_stock - 1).' pcs',
                    'stobd_selisih' => '1 pcs',
                    'stobd_notes' => null,
                ],
                [
                    'supplies_id' => $stockB->supplies_id,
                    'stobd_system' => $stockB->ss_stock.' pcs',
                    'stobd_real' => ($stockB->ss_stock - 1).' pcs',
                    'stobd_selisih' => '1 pcs',
                    'stobd_notes' => null,
                ],
            ]),
        ]);
        $insertResponse->assertStatus(200);
        $stobId = (int) $insertResponse->json('stob_id');

        $logCountBefore = DB::table('log_stocks')->count();

        $accResponse = $this->post('/accStockOpnameBahan', [
            'stob_id' => $stobId,
            'item' => json_encode([
                [
                    'supplies_id' => $stockA->supplies_id,
                    'sp_units' => [['unit_id' => $stockA->unit_id, 'real_qty' => $startingA - 1]],
                ],
                [
                    'supplies_id' => $stockB->supplies_id,
                    'sp_units' => [['unit_id' => $bogusUnitId, 'real_qty' => $startingB - 1]],
                ],
            ]),
        ]);

        // Documents current behavior: an uncaught error, not a clean {status:-1, ...} response.
        $accResponse->assertStatus(500);

        // Item A (processed first) is fully, permanently overwritten with its counted value.
        $stockA->refresh();
        $this->assertSame($startingA - 1, $stockA->ss_stock, "the first item's stock is permanently overwritten despite the second item's later crash");

        // Item B is never touched — the crash happens before its own ss_stock write.
        $stockB->refresh();
        $this->assertSame($startingB, $stockB->ss_stock, "the second (bogus-unit) item's stock is left completely untouched");

        // Item A gets both of its log_stocks rows (before + after); item B's crash happens after
        // its first (read-on-null) log row is already inserted, but before its stock write.
        $this->assertGreaterThanOrEqual($logCountBefore + 2, DB::table('log_stocks')->count());
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 1,
            'log_item_id' => $stockA->supplies_id,
            'log_jumlah' => $startingA - 1,
        ]);

        // The document itself is left stuck: neither approved nor left in its prior pending shape
        // cleanly — status was never flipped to 2, since the loop never reaches the end.
        $stob = StockOpnameBahan::find($stobId);
        $this->assertSame(1, (int) $stob->status, 'the opname is left pending, despite the first item already being permanently overwritten');
        $this->assertNull($stob->acc_by);
    }
}
