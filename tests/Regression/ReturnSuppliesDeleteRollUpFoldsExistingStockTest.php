<?php

namespace Tests\Regression;

use App\Models\LogStock;
use App\Models\PurchaseOrder;
use App\Models\ReturnSupplies;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #151 (2026-09-06): `ProductIssuesDetail::deleteProductIssuesDetail()`'s "Retur ke
 * Supplier" cancellation (`SupplierController::deleteReturnSupplies()`, the real, reachable path
 * — see `ProductIssuesDeleteRefNumGuardIsDeadCodeTest`) rolls the qty being restored up the unit
 * ladder via `UnitRollUp`, but only looked at the qty THIS cancellation restores — stock already
 * sitting at the Piece level was ignored. Restoring 6 Piece when 6 Piece already existed (1 DOS =
 * 12 Piece) landed as 12 Piece / 0 DOS forever, because 6 alone is not a multiple of 12. Fixed via
 * `UnitRollUp::planSuppliesFolded()` — see its class docblock and `UnitRollUp.php`'s.
 */
class ReturnSuppliesDeleteRollUpFoldsExistingStockTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE = 9;
    private const DOS = 7;

    public function test_cancelling_a_return_to_supplier_rolls_up_together_with_stock_already_on_hand(): void
    {
        $this->actingAsSuperAdminStaff();

        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Return Delete RollUp Ingredient '.uniqid();
        $supplies->supplies_unit = json_encode([self::PIECE, self::DOS]);
        $supplies->supplies_default_unit = self::PIECE;
        $supplies->status = 1;
        $supplies->save();

        $variant = new SuppliesVariant();
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplier_id = $supplier->supplier_id;
        $variant->supplies_variant_name = 'Return Delete RollUp Variant';
        $variant->supplies_variant_sku = 'WF-RETDELRU-'.uniqid();
        $variant->supplies_variant_price = 1000;
        $variant->supplies_variant_barcode = 'WF-RETDELRU-BC-'.uniqid();
        $variant->supplies_variant_stock = 0;
        $variant->status = 1;
        $variant->save();

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = self::DOS;   // bigger
        $relation->su_id_2 = self::PIECE; // smaller
        $relation->sr_value_1 = 1;
        $relation->sr_value_2 = 12;       // 1 DOS = 12 Piece
        $relation->status = 1;
        $relation->save();

        $pieceStock = new SuppliesStock();
        $pieceStock->supplies_id = $supplies->supplies_id;
        $pieceStock->unit_id = self::PIECE;
        $pieceStock->warehouse_id = 1;
        $pieceStock->ss_stock = 0;
        $pieceStock->status = 1;
        $pieceStock->save();

        $dosStock = new SuppliesStock();
        $dosStock->supplies_id = $supplies->supplies_id;
        $dosStock->unit_id = self::DOS;
        $dosStock->warehouse_id = 1;
        $dosStock->ss_stock = 0;
        $dosStock->status = 1;
        $dosStock->save();

        $qty = 10;
        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * 1000,
            'jenis_discount' => 1,
            'po_desc' => 'Return Delete RollUp PO',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'Return Delete RollUp Ingredient',
                'supplies_variant_name' => $variant->supplies_variant_name,
                'supplies_variant_sku' => $variant->supplies_variant_sku,
                'qty' => $qty,
                'supplies_variant_price' => 1000,
                'unit_id_select' => self::PIECE,
            ]]),
        ])->json();

        $this->post('/accPO', [
            'data' => [
                'po_id' => $poId,
                'po_supplier' => $supplier->supplier_id,
                'items' => [[
                    'supplies_variant_id' => $variant->supplies_variant_id,
                    'unit_id' => self::PIECE,
                    'pod_sku' => $variant->supplies_variant_sku,
                    'pod_qty' => $qty,
                ]],
            ],
        ])->assertStatus(200);

        // Receipt itself already rolled 10 Piece up to 0 DOS / 10 Piece (10 < 12). Reset to the
        // exact preconditions this test needs: 6 Piece already on hand, none of it in DOS yet.
        $pieceStock->refresh();
        $pieceStock->ss_stock = 6;
        $pieceStock->save();

        // Return 6 Piece to the supplier (deducts stock, tracked via ReturnSupplies).
        $this->post('/insertReturnSupplies', [
            'po_id' => $poId,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Return Delete RollUp regression return',
            'rs_total' => 6000,
            'returs' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'unit_id' => self::PIECE,
                'rsd_qty' => 6,
                'rsd_price' => 1000,
            ]]),
        ])->assertStatus(200);

        $pieceStock->refresh();
        $this->assertSame(0, $pieceStock->ss_stock, 'precondition: the return deducted the 6 Piece already on hand');

        $rs = ReturnSupplies::where('po_id', $poId)->firstOrFail();

        // Restore the 6 Piece back onto the shelf first, so the cancellation below has 6 Piece
        // already sitting there when it restores 6 more — 6 + 6 = 12 = exactly 1 DOS.
        $pieceStock->ss_stock = 6;
        $pieceStock->save();

        // Cancel the return -- restores the 6 Piece via deleteProductIssuesDetail()'s roll-up.
        $this->post('/deleteReturnSupplies', ['rs_id' => $rs->rs_id, 'po_id' => $poId])
            ->assertStatus(200);

        $pieceStock->refresh();
        $dosStock->refresh();

        $this->assertSame(0, $pieceStock->ss_stock, 'BUG WOULD BE: stuck at 12 Piece, never rolled up');
        $this->assertSame(1, $dosStock->ss_stock, 'existing 6 + restored 6 = 12 = exactly 1 DOS');

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertSame($qty * 1000, (int) $po->po_total, 'po_total is restored by cancelling the return');

        // GitHub #167 (same log-order bug found in SupplierController::accPO()): the "Pembatalan
        // retur pembelian ... masuk" log must be written BEFORE deleteProductIssuesDetail()'s
        // "Konversi unit" legs, not after -- otherwise the history reads keluar → konversi → masuk.
        $logs = LogStock::where('log_type', 2)
            ->where('log_item_id', $supplies->supplies_id)
            ->orderBy('log_id')
            ->get();
        $cancelLogIndex = $logs->search(fn ($l) => str_starts_with($l->log_notes, 'Pembatalan retur pembelian'));
        $this->assertNotFalse($cancelLogIndex, 'the cancellation masuk log must exist');
        foreach ($logs as $i => $log) {
            if (str_starts_with($log->log_notes, 'Konversi unit')) {
                $this->assertGreaterThan(
                    $cancelLogIndex,
                    $i,
                    'conversion leg "'.$log->log_notes.'" must come after the cancellation masuk log'
                );
            }
        }
    }
}
