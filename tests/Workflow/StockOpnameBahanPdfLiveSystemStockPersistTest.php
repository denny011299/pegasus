<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\StockOpnameDetailBahan;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bahan (Supplies) mirror of StockOpnamePdfLiveSystemStockTest's PM task (2026-08-24) coverage —
 * refreshLiveSystemQty() is one shared method parameterized for both Produk and Bahan (see
 * StockController::generateStockOpnameBahan()), but the two call sites pass different model
 * classes/keys (StockOpnameDetailBahan::class/'stobd_id' vs StockOpnameDetail::class/'stod_id'),
 * so this exercises that wiring specifically rather than assuming the Produk-side test alone
 * proves it.
 */
class StockOpnameBahanPdfLiveSystemStockPersistTest extends TestCase
{
    use ActingAsStaff;

    /** Built fresh via Eloquent — see StockOpnamePdfLiveSystemStockTest::pickFixtureStock()'s
     * docblock for why real seeded/live data isn't hand-picked here (multi-warehouse collisions). */
    private function pickFixtureStock(): SuppliesStock
    {
        $unit = Unit::where('status', 1)->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Opname Bahan PDF Persist Test Ingredient '.uniqid();
        $supplies->supplies_unit = json_encode([$unit->unit_id]);
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $stock = new SuppliesStock();
        $stock->supplies_id = $supplies->supplies_id;
        $stock->unit_id = $unit->unit_id;
        $stock->warehouse_id = 1;
        $stock->ss_stock = 100;
        $stock->status = 1;
        $stock->save();

        return $stock;
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    private function unitShortName(SuppliesStock $stock): string
    {
        return (string) (Unit::find($stock->unit_id)->unit_short_name ?? 'pcs');
    }

    private function insertStockOpnameBahan(SuppliesStock $stock, string $unit, int $realQty, bool $isDraft = false): int
    {
        $response = $this->post('/insertStockOpnameBahan', [
            'stob_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'stob_notes' => 'PDF persistence Bahan test',
            'is_draft' => $isDraft ? 1 : 0,
            'item' => json_encode([[
                'supplies_id' => $stock->supplies_id,
                'stobd_system' => $stock->ss_stock.' '.$unit,
                'stobd_real' => $realQty.' '.$unit,
                'stobd_selisih' => ($realQty - $stock->ss_stock).' '.$unit,
                'stobd_notes' => null,
                'stobd_touched' => 1,
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

    public function test_downloading_the_pdf_persists_live_stock_to_the_real_bahan_row_for_a_draft_document(): void
    {
        $this->actingAsSuperAdminStaff();
        $stock = $this->pickFixtureStock();
        $unit = $this->unitShortName($stock);
        $startingStock = $stock->ss_stock;

        $stobIdDraft = $this->insertStockOpnameBahan($stock, $unit, realQty: $startingStock, isDraft: true);
        $this->assertDatabaseHas('stock_opname_bahans', ['stob_id' => $stobIdDraft, 'is_draft' => 1, 'status' => 1]);

        // A different opname on the same ingredient gets approved first, moving the live stock.
        $otherStobId = $this->insertStockOpnameBahan($stock, $unit, realQty: $startingStock - 12);
        $this->approve($stock, $otherStobId, $startingStock - 12);
        $stock->refresh();
        $this->assertSame($startingStock - 12, $stock->ss_stock, 'sanity: the other approval must have moved the live stock');

        // Downloading the still-draft doc's PDF must persist the now-current live stock to its row.
        $this->get('/generateStockOpnameBahan/'.$stobIdDraft)->assertStatus(200);
        $this->assertDatabaseHas('stock_opname_detail_bahans', [
            'stob_id' => $stobIdDraft,
            'supplies_id' => $stock->supplies_id,
            'stobd_system' => ($startingStock - 12).' '.$unit,
            'stobd_selisih' => '12 '.$unit,
        ]);
    }

    public function test_downloading_the_pdf_for_an_already_decided_bahan_document_does_not_touch_its_frozen_row(): void
    {
        $this->actingAsSuperAdminStaff();
        $stock = $this->pickFixtureStock();
        $unit = $this->unitShortName($stock);
        $startingStock = $stock->ss_stock;

        $stobId = $this->insertStockOpnameBahan($stock, $unit, realQty: $startingStock);
        $this->approve($stock, $stobId, $startingStock);

        $frozenBefore = StockOpnameDetailBahan::where('stob_id', $stobId)
            ->where('supplies_id', $stock->supplies_id)
            ->firstOrFail();

        $otherStobId = $this->insertStockOpnameBahan($stock, $unit, realQty: $startingStock - 9);
        $this->approve($stock, $otherStobId, $startingStock - 9);

        $this->get('/generateStockOpnameBahan/'.$stobId)->assertStatus(200);

        $this->assertDatabaseHas('stock_opname_detail_bahans', [
            'stob_id' => $stobId,
            'supplies_id' => $stock->supplies_id,
            'stobd_system' => $frozenBefore->stobd_system,
            'stobd_selisih' => $frozenBefore->stobd_selisih,
        ]);
    }
}
