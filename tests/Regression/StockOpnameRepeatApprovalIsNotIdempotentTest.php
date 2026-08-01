<?php

namespace Tests\Regression;

use App\Models\ProductStock;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `StockController::accStockOpname()` (and its `accStockOpnameBahan()` mirror) only guards on
 * `is_draft` — it never checks `status`, unlike every other approval action in this app (PO/SO/
 * Production all guard on `status == 1`). Calling approve again on an already-approved (`status=2`)
 * document is accepted exactly like the first call: it re-overwrites `ps_stock` with whatever
 * `real_qty` the request happens to carry this time, and writes two more `log_stocks` rows
 * (before/after) on every single call, with no idempotency guard or lock.
 *
 * Found while tracing cdocs/testing/workflows/STOCK_OPNAME_FLOW.md (Phase 3 pilot), flagged there
 * as "not yet characterized as a test." Confirmed 2026-08-01, deliberately deferred pending
 * developer confirmation — see cdocs/testing/KNOWN_ISSUES.md. This test characterizes the CURRENT
 * (non-idempotent) behavior on purpose — flip it once a `status` guard is added.
 */
class StockOpnameRepeatApprovalIsNotIdempotentTest extends TestCase
{
    use ActingAsStaff;

    private function pickFixtureStock(): ProductStock
    {
        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->where('ps_stock', '>', 10)
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

    private function insertStockOpname(ProductStock $stock): int
    {
        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Repeat-approval regression test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $stock->product_id,
                'product_variant_id' => $stock->product_variant_id,
                'stod_system' => $stock->ps_stock.' pcs',
                'stod_real' => $stock->ps_stock.' pcs',
                'stod_selisih' => '0 pcs',
                'stod_notes' => null,
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

    public function test_approving_an_already_approved_opname_again_silently_re_overwrites_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $startingStock = $stock->ps_stock;
        $stoId = $this->insertStockOpname($stock);

        $this->approve($stock, $stoId, $startingStock - 5);

        $sto = StockOpname::find($stoId);
        $this->assertSame(2, (int) $sto->status);
        $stock->refresh();
        $this->assertSame($startingStock - 5, $stock->ps_stock);

        $logCountAfterFirstApproval = DB::table('log_stocks')->count();

        // Calling approve a SECOND time on the same, already-approved (status=2) document is
        // accepted exactly like the first call — no `status` guard exists.
        $this->approve($stock, $stoId, $startingStock - 20);

        $sto->refresh();
        $this->assertSame(2, (int) $sto->status, 'status is unconditionally re-set to 2, indistinguishable from a first approval');

        $stock->refresh();
        $this->assertSame(
            $startingStock - 20,
            $stock->ps_stock,
            'repeat approval silently re-overwrites stock with whatever real_qty the second request carries'
        );

        $this->assertSame(
            $logCountAfterFirstApproval + 2,
            DB::table('log_stocks')->count(),
            'each approval call writes 2 more log_stocks rows (before/after), even on a repeat call'
        );
    }
}
