<?php

namespace Tests\Regression;

use App\Models\StockOpnameBahan;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Mirrors tests/Regression/StockOpnameRepeatApprovalIsNotIdempotentTest.php — that test's own
 * docblock already predicted "and its accStockOpnameBahan() mirror" but left it unverified. This
 * confirms it directly: `accStockOpnameBahan()` only guards on `is_draft`, never `status`, so
 * calling approve again on an already-approved (`status=2`) document is accepted exactly like the
 * first call, silently re-overwriting `ss_stock` with whatever `real_qty` the second request
 * carries, with no idempotency guard or lock.
 */
class StockOpnameBahanRepeatApprovalIsNotIdempotentTest extends TestCase
{
    use ActingAsStaff;

    private function pickFixtureStock(): SuppliesStock
    {
        return SuppliesStock::where('status', 1)
            ->where('ss_stock', '>', 20)
            ->firstOrFail();
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    private function insertStockOpnameBahan(SuppliesStock $stock): int
    {
        $response = $this->post('/insertStockOpnameBahan', [
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => 'Repeat-approval regression test (Bahan)',
            'is_draft' => 0,
            'item' => json_encode([[
                'supplies_id' => $stock->supplies_id,
                'stobd_system' => $stock->ss_stock.' pcs',
                'stobd_real' => $stock->ss_stock.' pcs',
                'stobd_selisih' => '0 pcs',
                'stobd_notes' => null,
            ]]),
        ]);
        $response->assertStatus(200);

        return (int) $response->json('stob_id');
    }

    private function approve(SuppliesStock $stock, int $stobId, int $realQty): void
    {
        $this->post('/accStockOpnameBahan', [
            'stob_id' => $stobId,
            'item' => json_encode([[
                'supplies_id' => $stock->supplies_id,
                'sp_units' => [[
                    'unit_id' => $stock->unit_id,
                    'real_qty' => $realQty,
                ]],
            ]]),
        ])->assertStatus(200);
    }

    public function test_approving_an_already_approved_opname_again_silently_re_overwrites_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $startingStock = $stock->ss_stock;
        $stobId = $this->insertStockOpnameBahan($stock);

        $this->approve($stock, $stobId, $startingStock - 5);

        $stob = StockOpnameBahan::find($stobId);
        $this->assertSame(2, (int) $stob->status);
        $stock->refresh();
        $this->assertSame($startingStock - 5, $stock->ss_stock);

        $logCountAfterFirstApproval = DB::table('log_stocks')->count();

        // Calling approve a SECOND time on the same, already-approved (status=2) document is
        // accepted exactly like the first call — no `status` guard exists.
        $this->approve($stock, $stobId, $startingStock - 20);

        $stob->refresh();
        $this->assertSame(2, (int) $stob->status, 'status is unconditionally re-set to 2, indistinguishable from a first approval');

        $stock->refresh();
        $this->assertSame(
            $startingStock - 20,
            $stock->ss_stock,
            'repeat approval silently re-overwrites stock with whatever real_qty the second request carries'
        );

        $this->assertSame(
            $logCountAfterFirstApproval + 2,
            DB::table('log_stocks')->count(),
            'each approval call writes 2 more log_stocks rows (before/after), even on a repeat call'
        );
    }
}
