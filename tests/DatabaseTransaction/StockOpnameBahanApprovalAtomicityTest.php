<?php

namespace Tests\DatabaseTransaction;

use App\Models\StockOpnameBahan;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-04): Mirrors
 * tests/DatabaseTransaction/StockOpnameApprovalAtomicityTest.php exactly, one level down (Bahan
 * instead of Produk) — confirms the identical missing-transaction/null-guard fix applies on this
 * side too, not just assumed from the Produk-side finding.
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

    public function test_a_mid_loop_failure_is_now_cleanly_rejected_with_nothing_overwritten(): void
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

        // Fixed: a clean, structured rejection instead of an uncaught 500.
        $accResponse->assertOk();
        $accResponse->assertJson(['status' => 0, 'header' => 'Gagal ACC']);

        // Fixed: item A must be untouched now — the whole loop runs in one transaction, so item
        // B's missing stock row rolls item A's mutation back too.
        $stockA->refresh();
        $this->assertSame($startingA, $stockA->ss_stock, "a rejected approval must not touch item A's stock at all");

        $stockB->refresh();
        $this->assertSame($startingB, $stockB->ss_stock, "a rejected approval must not touch item B's stock either");

        // Fixed: no log_stocks rows survive a rolled-back approval.
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'a rejected approval must not leave any log_stocks rows behind');

        // The document remains pending — never flipped to approved.
        $stob = StockOpnameBahan::find($stobId);
        $this->assertSame(1, (int) $stob->status, 'a rejected approval must leave the opname pending');
        $this->assertNull($stob->acc_by);
    }
}
