<?php

namespace Tests\Workflow;

use App\Models\ProductStock;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/STOCK_OPNAME_FLOW.md for the fully-traced flow this asserts
 * against. Unlike Purchase Order/Sales Order/Production (all add/subtract a delta), approving a
 * stock opname OVERWRITES ps_stock with the counted real_qty — that's the point of a stock take.
 * Also covers the is_draft gate added 2026-07-31, the newest piece of this flow.
 */
class StockOpnameFlowTest extends TestCase
{
    use ActingAsStaff;

    private function pickFixtureStock(): ProductStock
    {
        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1) // Gudang Pusat (main), seeded 2026-08-01
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

    private function insertStockOpname(ProductStock $stock, bool $isDraft = false): int
    {
        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Workflow test opname',
            'is_draft' => $isDraft ? 1 : 0,
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
        $response->assertJson(['status' => 1]);

        return (int) $response->json('sto_id');
    }

    private function approvalPayload(ProductStock $stock, int $stoId, int $realQty): array
    {
        return [
            'sto_id' => $stoId,
            'item' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'units' => [[
                    'unit_id' => $stock->unit_id,
                    'real_qty' => $realQty,
                ]],
            ]]),
        ];
    }

    public function test_insert_then_approve_overwrites_stock_and_writes_log(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $startingStock = $stock->ps_stock;
        $realQty = $startingStock - 5; // simulate a shrinkage found during the count
        $logCountBefore = DB::table('log_stocks')->count();

        $stoId = $this->insertStockOpname($stock);

        $sto = StockOpname::find($stoId);
        $this->assertSame(1, (int) $sto->status, 'a freshly inserted, non-draft opname should be pending approval');
        $this->assertFalse((bool) $sto->is_draft);
        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoId,
            'product_variant_id' => $stock->product_variant_id,
        ]);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'inserting an opname must not touch stock before approval');
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'inserting an opname must not write a log_stocks row');

        $accResponse = $this->post('/accStockOpname', $this->approvalPayload($stock, $stoId, $realQty));
        $accResponse->assertStatus(200);

        $sto->refresh();
        $this->assertSame(2, (int) $sto->status, 'approving an opname sets status to 2');
        $this->assertNotNull($sto->acc_by);

        $stock->refresh();
        $this->assertSame($realQty, $stock->ps_stock, 'approval must OVERWRITE stock with the counted real_qty, not add/subtract a delta');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $stock->product_variant_id,
            'log_jumlah' => $startingStock,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $stock->product_variant_id,
            'log_jumlah' => $realQty,
        ]);
    }

    public function test_reject_a_pending_opname_leaves_stock_untouched(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $startingStock = $stock->ps_stock;

        $stoId = $this->insertStockOpname($stock);

        $this->post('/tolakStockOpname', ['sto_id' => $stoId])->assertStatus(200);

        $sto = StockOpname::find($stoId);
        $this->assertSame(3, (int) $sto->status, 'rejecting a pending opname sets status to 3');

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'rejecting a pending opname must not touch stock (nothing was changed yet)');
    }

    public function test_draft_document_cannot_be_approved_until_submitted(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $startingStock = $stock->ps_stock;
        $realQty = $startingStock - 5;

        $stoId = $this->insertStockOpname($stock, isDraft: true);

        $sto = StockOpname::find($stoId);
        $this->assertTrue((bool) $sto->is_draft, 'inserting with is_draft should keep the document as a draft');

        // A draft cannot be approved yet.
        $blockedResponse = $this->post('/accStockOpname', $this->approvalPayload($stock, $stoId, $realQty));
        $blockedResponse->assertJson(['status' => -1]);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'a blocked (still-draft) approval must not touch stock');

        // Submitting takes it out of draft...
        $this->post('/submitStockOpname', ['sto_id' => $stoId])->assertStatus(200);
        $sto->refresh();
        $this->assertFalse((bool) $sto->is_draft, 'submitStockOpname must clear is_draft');
        $this->assertSame(1, (int) $sto->status, 'submitting does not itself change status — it was already pending');

        // ...and only now can it actually be approved.
        $this->post('/accStockOpname', $this->approvalPayload($stock, $stoId, $realQty))->assertStatus(200);
        $sto->refresh();
        $this->assertSame(2, (int) $sto->status);

        $stock->refresh();
        $this->assertSame($realQty, $stock->ps_stock);
    }
}
