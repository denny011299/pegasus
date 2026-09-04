<?php

namespace Tests\Workflow;

use App\Models\StockOpnameBahan;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/STOCK_OPNAME_FLOW.md's "Stock Opname Bahan" section. Confirms the
 * TODO's assumption that `StockOpnameBahan` is structurally identical to `StockOpname` (Produk) —
 * verified directly rather than assumed: same overwrite-not-delta semantics on approval, same
 * is_draft gate, same status transitions. Differences are purely naming (`stob_*`/`stobd_*`
 * instead of `sto_*`/`stod_*`, `sp_units` instead of `units`, `supplies_id`/`SuppliesStock` instead
 * of `product_variant_id`/`ProductStock`) — no `category_id` field exists on this side at all.
 */
class StockOpnameBahanFlowTest extends TestCase
{
    use ActingAsStaff;

    private function pickFixtureStock(): SuppliesStock
    {
        return SuppliesStock::where('status', 1)
            ->where('ss_stock', '>', 10)
            ->firstOrFail();
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    /**
     * Rancang ulang 2026-08-27: hasil hitung ikut DOKUMEN, bukan body request saat ACC -- jadi
     * $realQty dititipkan di sini (sp_units[]), persis seperti yang memang dikirim
     * CreateStockOpnameSupplies.js. Default null = tidak dihitung, stok tidak akan disentuh saat ACC.
     */
    private function insertStockOpnameBahan(SuppliesStock $stock, bool $isDraft = false, ?int $realQty = null): int
    {
        $response = $this->post('/insertStockOpnameBahan', [
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => 'Workflow test opname bahan',
            'is_draft' => $isDraft ? 1 : 0,
            'item' => json_encode([[
                'supplies_id' => $stock->supplies_id,
                'sp_units' => [[
                    'unit_id' => $stock->unit_id,
                    'system_qty' => $stock->ss_stock,
                    'real_qty' => $realQty ?? $stock->ss_stock,
                ]],
                'stobd_notes' => null,
            ]]),
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        return (int) $response->json('stob_id');
    }

    private function approvalPayload(SuppliesStock $stock, int $stobId, int $realQty): array
    {
        return [
            'stob_id' => $stobId,
            'item' => json_encode([[
                'supplies_id' => $stock->supplies_id,
                'sp_units' => [[
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
        $startingStock = $stock->ss_stock;
        $realQty = $startingStock - 5; // simulate a shrinkage found during the count
        $logCountBefore = DB::table('log_stocks')->count();

        $stobId = $this->insertStockOpnameBahan($stock, realQty: $realQty);

        $stob = StockOpnameBahan::find($stobId);
        $this->assertSame(1, (int) $stob->status, 'a freshly inserted, non-draft opname should be pending approval');
        $this->assertFalse((bool) $stob->is_draft);
        // Dokumen baru disimpan di stock_opname_bahan_lines (satu baris per satuan, angka
        // betulan); stock_opname_detail_bahans khusus dokumen lama dan sengaja tidak ikut ditulis.
        $this->assertDatabaseHas('stock_opname_bahan_lines', [
            'stob_id' => $stobId,
            'supplies_id' => $stock->supplies_id,
            'unit_id' => $stock->unit_id,
            'sobl_counted_qty' => $realQty,
        ]);
        $this->assertDatabaseMissing('stock_opname_detail_bahans', ['stob_id' => $stobId]);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'inserting an opname must not touch stock before approval');
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'inserting an opname must not write a log_stocks row');

        $accResponse = $this->post('/accStockOpnameBahan', $this->approvalPayload($stock, $stobId, $realQty));
        $accResponse->assertStatus(200);

        $stob->refresh();
        $this->assertSame(2, (int) $stob->status, 'approving an opname sets status to 2');
        $this->assertNotNull($stob->acc_by);

        $stock->refresh();
        $this->assertSame($realQty, $stock->ss_stock, 'approval must OVERWRITE stock with the counted real_qty, not add/subtract a delta');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $stock->supplies_id,
            'log_jumlah' => $startingStock,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 1,
            'log_item_id' => $stock->supplies_id,
            'log_jumlah' => $realQty,
        ]);
    }

    public function test_reject_a_pending_opname_leaves_stock_untouched(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $startingStock = $stock->ss_stock;

        $stobId = $this->insertStockOpnameBahan($stock, realQty: $startingStock - 5);

        $this->post('/tolakStockOpnameBahan', ['stob_id' => $stobId])->assertStatus(200);

        $stob = StockOpnameBahan::find($stobId);
        $this->assertSame(3, (int) $stob->status, 'rejecting a pending opname sets status to 3');

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'rejecting a pending opname must not touch stock (nothing was changed yet)');
    }

    public function test_draft_document_cannot_be_approved_until_submitted(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $startingStock = $stock->ss_stock;
        $realQty = $startingStock - 5;

        $stobId = $this->insertStockOpnameBahan($stock, isDraft: true, realQty: $realQty);

        $stob = StockOpnameBahan::find($stobId);
        $this->assertTrue((bool) $stob->is_draft, 'inserting with is_draft should keep the document as a draft');

        // A draft cannot be approved yet.
        $blockedResponse = $this->post('/accStockOpnameBahan', $this->approvalPayload($stock, $stobId, $realQty));
        $blockedResponse->assertJson(['status' => -1]);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'a blocked (still-draft) approval must not touch stock');

        // Submitting takes it out of draft...
        $this->post('/submitStockOpnameBahan', ['stob_id' => $stobId])->assertStatus(200);
        $stob->refresh();
        $this->assertFalse((bool) $stob->is_draft, 'submitStockOpnameBahan must clear is_draft');
        $this->assertSame(1, (int) $stob->status, 'submitting does not itself change status — it was already pending');

        // ...and only now can it actually be approved.
        $this->post('/accStockOpnameBahan', $this->approvalPayload($stock, $stobId, $realQty))->assertStatus(200);
        $stob->refresh();
        $this->assertSame(2, (int) $stob->status);

        $stock->refresh();
        $this->assertSame($realQty, $stock->ss_stock);
    }

    /**
     * Kembaran StockOpnameDraftPrivacyTest's PDF guard, sisi Bahan (2026-09-04): draft belum
     * punya snapshot apa pun, tombol Print-nya sendiri sudah disembunyikan client-side
     * (Stock_Opname_Bahan.js's `item.is_draft`), tapi endpoint-nya harus menolak juga -- termasuk
     * untuk pembuat dokumennya sendiri, bukan cuma staf lain.
     */
    public function test_generate_pdf_rejects_a_draft_even_for_its_own_owner(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = $this->pickFixtureStock();
        $stobId = $this->insertStockOpnameBahan($stock, isDraft: true, realQty: $stock->ss_stock - 1);

        $this->get('/generateStockOpnameBahan/'.$stobId)->assertStatus(404);

        $this->post('/submitStockOpnameBahan', ['stob_id' => $stobId])->assertStatus(200);

        $response = $this->get('/generateStockOpnameBahan/'.$stobId);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
