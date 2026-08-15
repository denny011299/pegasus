<?php

namespace Tests\Regression;

use App\Models\StockOpnameBahan;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-04): Mirrors
 * tests/Regression/StockOpnameRepeatApprovalIsNotIdempotentTest.php — the same bug shape,
 * confirmed independently on `accStockOpnameBahan()`. Now refuses outright with a clean
 * `{status: -2, header: 'Gagal ACC', ...}` response if the document's status isn't `1`.
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

    public function test_approving_an_already_approved_opname_again_is_now_cleanly_rejected(): void
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

        // Calling approve a SECOND time on the same, already-approved (status=2) document must
        // now be cleanly rejected, matching PO/SO/Production's status guard.
        $secondResponse = $this->post('/accStockOpnameBahan', [
            'stob_id' => $stobId,
            'item' => json_encode([[
                'supplies_id' => $stock->supplies_id,
                'sp_units' => [[
                    'unit_id' => $stock->unit_id,
                    'real_qty' => $startingStock - 20,
                ]],
            ]]),
        ]);
        $secondResponse->assertOk();
        $secondResponse->assertJson(['status' => -2, 'header' => 'Gagal ACC']);

        $stob->refresh();
        $this->assertSame(2, (int) $stob->status, 'status must remain approved, not silently re-set');

        $stock->refresh();
        $this->assertSame(
            $startingStock - 5,
            $stock->ss_stock,
            'a rejected repeat approval must not touch stock at all'
        );

        $this->assertSame(
            $logCountAfterFirstApproval,
            DB::table('log_stocks')->count(),
            'a rejected repeat approval must not write any log_stocks rows'
        );
    }
}
