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

    /**
     * Rancang ulang 2026-08-27: hasil hitung sekarang ikut DOKUMEN, bukan body request saat ACC.
     * Karena itu $realQty harus dititipkan di sini (units[]), persis seperti yang memang dikirim
     * CreateStockOpname.js. Default null = tidak dihitung, stok tidak akan disentuh saat ACC.
     */
    private function insertStockOpname(ProductStock $stock, bool $isDraft = false, ?int $realQty = null): int
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
                'units' => [[
                    'unit_id' => $stock->unit_id,
                    'system_qty' => $stock->ps_stock,
                    'real_qty' => $realQty ?? $stock->ps_stock,
                ]],
                'stod_system' => $stock->ps_stock.' pcs',
                'stod_real' => ($realQty ?? $stock->ps_stock).' pcs',
                'stod_selisih' => (($realQty ?? $stock->ps_stock) - $stock->ps_stock).' pcs',
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

        $stoId = $this->insertStockOpname($stock, realQty: $realQty);

        $sto = StockOpname::find($stoId);
        $this->assertSame(1, (int) $sto->status, 'a freshly inserted, non-draft opname should be pending approval');
        $this->assertFalse((bool) $sto->is_draft);
        // Dokumen baru disimpan di stock_opname_lines (satu baris per satuan, angka betulan);
        // stock_opname_details khusus dokumen lama dan sengaja tidak ikut ditulis lagi.
        $this->assertDatabaseHas('stock_opname_lines', [
            'sto_id' => $stoId,
            'product_variant_id' => $stock->product_variant_id,
            'unit_id' => $stock->unit_id,
            'sol_counted_qty' => $realQty,
        ]);
        $this->assertDatabaseMissing('stock_opname_details', ['sto_id' => $stoId]);

        // NB (GitHub #91, 2026-08-31): inserting a non-draft opname now ALSO heals live stock that
        // was stuck under-rolled from before GitHub #87 (see OpnameLifecycle::
        // healUntouchedSystemStock()) -- and this fixture picks a REAL okeh8644 row, which for
        // variant 2 genuinely is stuck (19 DOS + 30 Piece, 1 DOS = 24 Piece). So neither "stock is
        // byte-identical to before" nor "no log row at all" is a valid invariant here any more.
        // What this test actually cares about is unchanged and still asserted: inserting must not
        // apply the COUNT itself -- that only happens at approval, below.
        $stock->refresh();
        $this->assertNotSame($realQty, $stock->ps_stock, 'inserting an opname must not apply the counted real_qty to stock -- that is approval\'s job');
        $stockAfterInsert = $stock->ps_stock;
        $logCountAfterInsert = DB::table('log_stocks')->count();

        $accResponse = $this->post('/accStockOpname', $this->approvalPayload($stock, $stoId, $realQty));
        $accResponse->assertStatus(200);

        $sto->refresh();
        $this->assertSame(2, (int) $sto->status, 'approving an opname sets status to 2');
        $this->assertNotNull($sto->acc_by);

        $stock->refresh();
        $this->assertSame($realQty, $stock->ps_stock, 'approval must OVERWRITE stock with the counted real_qty, not add/subtract a delta');
        $this->assertGreaterThan($logCountAfterInsert, DB::table('log_stocks')->count(), 'approval must write its own log_stocks rows');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $stock->product_variant_id,
            'log_jumlah' => $stockAfterInsert,
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

        $stoId = $this->insertStockOpname($stock, realQty: $startingStock - 5);

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

        $stoId = $this->insertStockOpname($stock, isDraft: true, realQty: $realQty);

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
