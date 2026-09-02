<?php

namespace Tests\DatabaseTransaction;

use App\Models\ProductStock;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-04): Extends the Phase 3 pilot (tests/Workflow/StockOpnameFlowTest.php,
 * cdocs/testing/workflows/STOCK_OPNAME_FLOW.md). `accStockOpname` used to have no
 * `DB::transaction()` (same gap shape as Purchase Order's `accPO` and Production's
 * `accProduction`) and no null-check on its `ProductStock` lookup (`product_variant_id` +
 * `unit_id`) — a bogus `unit_id` on a later item crashed the whole request with a fatal "Attempt
 * to assign property on null" while an earlier item's stock was already permanently overwritten.
 *
 * Fix: the whole mutation loop now runs inside one `DB::transaction()`, and a missing
 * `ProductStock` row is collected and rolled back with a clean `{status: 0, header: 'Gagal ACC',
 * ...}` response instead of crashing.
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

    public function test_a_mid_loop_failure_is_now_cleanly_rejected_with_nothing_overwritten(): void
    {
        $this->actingAsSuperAdminStaff();

        [$stockA, $stockB] = $this->pickTwoFixtureStocks();
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
                    'units' => [[
                        'unit_id' => $stockA->unit_id,
                        'system_qty' => $stockA->ps_stock,
                        'real_qty' => $stockA->ps_stock - 1,
                    ]],
                    'stod_notes' => null,
                ],
                [
                    // Rancang ulang 2026-08-27: satuan rusaknya sekarang harus ada di DOKUMEN,
                    // bukan diselipkan saat ACC -- ACC tidak lagi membaca angka dari body request.
                    'product_id' => $stockB->product_id,
                    'product_variant_id' => $stockB->product_variant_id,
                    'units' => [[
                        'unit_id' => $bogusUnitId,
                        'system_qty' => $stockB->ps_stock,
                        'real_qty' => $stockB->ps_stock - 1,
                    ]],
                    'stod_notes' => null,
                ],
            ]),
        ]);
        $insertResponse->assertStatus(200);
        $stoId = (int) $insertResponse->json('sto_id');

        // Baseline taken AFTER the insert on purpose (GitHub #91, 2026-08-31): inserting a
        // non-draft opname now also heals live stock that was stuck under-rolled from before
        // GitHub #87 (OpnameLifecycle::healUntouchedSystemStock()), and this fixture picks REAL
        // okeh8644 rows -- variant 2 genuinely is stuck, so the heal legitimately rewrites both
        // rows here before ACC is ever called. That heal is a separate, already-committed step; it
        // is NOT part of the accStockOpname() transaction this test is about, so the post-insert
        // state is the correct baseline for "did the rejected approval touch anything".
        $stockA->refresh();
        $stockB->refresh();
        $startingA = $stockA->ps_stock;
        $startingB = $stockB->ps_stock;

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

        // Fixed: a clean, structured rejection instead of an uncaught 500.
        $accResponse->assertOk();
        $accResponse->assertJson(['status' => 0, 'header' => 'Gagal ACC']);

        // Fixed: item A must be untouched now — the whole loop runs in one transaction, so item
        // B's missing stock row rolls item A's mutation back too.
        $stockA->refresh();
        $this->assertSame($startingA, $stockA->ps_stock, "a rejected approval must not touch item A's stock at all");

        $stockB->refresh();
        $this->assertSame($startingB, $stockB->ps_stock, "a rejected approval must not touch item B's stock either");

        // Fixed: no log_stocks rows survive a rolled-back approval.
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'a rejected approval must not leave any log_stocks rows behind');

        // The document remains pending — never flipped to approved.
        $sto = StockOpname::find($stoId);
        $this->assertSame(1, (int) $sto->status, 'a rejected approval must leave the opname pending');
        $this->assertNull($sto->acc_by);
    }
}
