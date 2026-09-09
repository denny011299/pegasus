<?php

namespace Tests\Workflow;

use App\Models\LogStock;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Added 2026-08-25 with the unit-conversion coverage sweep.
 *
 * Before: `PurchaseOrderDeliveryDetail::insertPoDeliveryDetail()` — the live goods-receipt path
 * used by `accPO()` — credited stock with a flat `$s->ss_stock += $qty` and no unit conversion at
 * all. Receiving 24 Piece left 24 Piece sitting at the Piece level, even though 1 DOS = 12 Piece
 * and the very same 24 Piece coming out of PRODUCTION had rolled up to 2 DOS since GitHub #19. The
 * same physical goods were represented differently purely based on how they entered.
 *
 * After: receipt rolls up through the ladder via `App\Support\UnitRollUp`.
 *
 * The second test is the important one. Rolling up on receipt is NOT safe on its own: `tolakPO()`
 * (rejecting an already-approved PO) used to look only at the ORDERED unit's stock row and refuse
 * with "Stok bahan tidak mencukupi" if that one row was short. Once receipt rolls up, the received
 * stock is no longer in the ordered unit, so rejecting any laddered PO would have failed 100% of
 * the time. `tolakPO()` therefore now measures the whole ladder in smallest-unit-equivalent and
 * breaks bigger units back down before deducting. These two changes must ship together.
 */
class PurchaseOrderReceiptRollUpFlowTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE = 9;
    private const DOS = 7;

    /** @return array{0: Supplies, 1: SuppliesVariant, 2: SuppliesStock, 3: SuppliesStock, 4: Supplier} */
    private function createFixture(): array
    {
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'PO RollUp Ingredient '.uniqid();
        $supplies->supplies_unit = json_encode([self::PIECE, self::DOS]);
        $supplies->supplies_default_unit = self::PIECE;
        $supplies->status = 1;
        $supplies->save();

        $variant = new SuppliesVariant();
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplier_id = $supplier->supplier_id;
        $variant->supplies_variant_name = 'PO RollUp Variant';
        $variant->supplies_variant_sku = 'WF-POROLL-'.uniqid();
        $variant->supplies_variant_price = 1000;
        $variant->supplies_variant_barcode = 'WF-POROLL-BC-'.uniqid();
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

        return [$supplies, $variant, $pieceStock, $dosStock, $supplier];
    }

    private function createAndApprovePo(SuppliesVariant $variant, Supplier $supplier, int $qty): int
    {
        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => $qty * 1000,
            'jenis_discount' => 1,
            'po_desc' => 'PO roll-up flow test',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'PO RollUp Ingredient',
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

        return $poId;
    }

    public function test_receiving_goods_rolls_the_quantity_up_the_unit_ladder(): void
    {
        $this->actingAsSuperAdminStaff();
        [, $variant, $pieceStock, $dosStock, $supplier] = $this->createFixture();

        // 26 Piece = 2 DOS + 2 Piece.
        $this->createAndApprovePo($variant, $supplier, 26);

        $pieceStock->refresh();
        $dosStock->refresh();

        $this->assertSame(2, $dosStock->ss_stock, 'received qty must roll up into whole DOS');
        $this->assertSame(2, $pieceStock->ss_stock, 'the remainder stays at the Piece level');

        // Conservation: nothing invented, nothing lost.
        $this->assertSame(
            26,
            ($dosStock->ss_stock * 12) + $pieceStock->ss_stock,
            'total Piece-equivalent must equal exactly what was received'
        );
    }

    public function test_an_approved_po_can_still_be_rejected_after_its_receipt_rolled_up(): void
    {
        $this->actingAsSuperAdminStaff();
        [, $variant, $pieceStock, $dosStock, $supplier] = $this->createFixture();

        // 24 Piece rolls up to exactly 2 DOS, leaving ZERO at the ordered (Piece) unit — the case
        // that would have made the old tolakPO() refuse outright.
        $poId = $this->createAndApprovePo($variant, $supplier, 24);

        $pieceStock->refresh();
        $dosStock->refresh();
        $this->assertSame(2, $dosStock->ss_stock);
        $this->assertSame(0, $pieceStock->ss_stock, 'precondition: nothing left at the ordered unit');

        $response = $this->post('/tolakPO', ['po_id' => $poId]);
        $response->assertStatus(200);
        $this->assertStringNotContainsString(
            'tidak mencukupi',
            $response->getContent(),
            'BUG WOULD BE: rejection refused because the ordered unit reads 0 after the roll-up'
        );

        $pieceStock->refresh();
        $dosStock->refresh();

        // Everything received is fully backed out: one DOS is broken open to cover the 24 Piece.
        $this->assertSame(
            0,
            ($dosStock->ss_stock * 12) + $pieceStock->ss_stock,
            'rejecting the PO must remove exactly what it added, across the whole ladder'
        );

        $po = PurchaseOrder::find($poId);
        $this->assertSame(-1, (int) $po->status, 'the PO ends up rejected');
    }

    /**
     * GitHub #151 (2026-09-06): the roll-up above only looked at the qty THIS receipt brought in --
     * stock already sitting at the Piece level before the PO was received was ignored. Receiving 6
     * Piece when 6 Piece already existed (1 DOS = 12 Piece) landed as 12 Piece / 0 DOS forever,
     * because 6 alone is not a multiple of 12. Fixed via UnitRollUp::planSuppliesFolded() -- see its
     * class docblock.
     */
    public function test_receiving_goods_rolls_up_together_with_stock_already_on_hand(): void
    {
        $this->actingAsSuperAdminStaff();
        [, $variant, $pieceStock, $dosStock, $supplier] = $this->createFixture();

        $pieceStock->ss_stock = 6; // pre-existing, below the 12-Piece DOS ratio on its own
        $pieceStock->save();

        // 6 more Piece arrives -- not a multiple of 12 by itself, but 6 + 6 = 12 = exactly 1 DOS.
        $this->createAndApprovePo($variant, $supplier, 6);

        $pieceStock->refresh();
        $dosStock->refresh();

        $this->assertSame(0, $pieceStock->ss_stock, 'BUG WOULD BE: stuck at 12 Piece, never rolled up');
        $this->assertSame(1, $dosStock->ss_stock, 'existing 6 + received 6 = 12 = exactly 1 DOS');

        // History requested by the user (2026-09-07): 3 separate legs, not one netted delta.
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2, 'log_category' => 2, 'log_notes' => 'Konversi unit (Naik satuan)',
            'log_jumlah' => 12, 'unit_id' => self::PIECE,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2, 'log_category' => 1, 'log_notes' => 'Konversi unit (Hasil naik satuan)',
            'log_jumlah' => 1, 'unit_id' => self::DOS,
        ]);
    }

    /**
     * GitHub #167: the raw "Pembelian bahan mentah masuk" log was inserted AFTER
     * insertPoDeliveryDetail() had already written its "Naik satuan" (keluar) / "Hasil naik satuan"
     * (masuk) conversion legs, so the history read keluar → konversi → masuk -- confusing, since
     * nothing had "gone out" before the purchase itself ever landed. The masuk-first log must be
     * the earliest row, followed by the conversion legs in their existing keluar-then-masuk order.
     */
    public function test_purchase_log_is_written_before_the_rollup_conversion_legs(): void
    {
        $this->actingAsSuperAdminStaff();
        [$supplies, $variant, , , $supplier] = $this->createFixture();

        // 26 Piece = 2 DOS + 2 Piece -> triggers a roll-up conversion.
        $this->createAndApprovePo($variant, $supplier, 26);

        $logs = LogStock::where('log_type', 2)
            ->where('log_item_id', $supplies->supplies_id)
            ->orderBy('log_id')
            ->get();

        $this->assertStringStartsWith(
            'Pembelian bahan mentah '.$supplier->supplier_name,
            $logs[0]->log_notes
        );
        $this->assertSame(1, (int) $logs[0]->log_category, 'purchase masuk log must be first');

        $this->assertSame('Konversi unit (Naik satuan)', $logs[1]->log_notes);
        $this->assertSame(2, (int) $logs[1]->log_category, 'bongkar leg (keluar) comes after the purchase log');

        $this->assertSame('Konversi unit (Hasil naik satuan)', $logs[2]->log_notes);
        $this->assertSame(1, (int) $logs[2]->log_category, 'hasil leg (masuk) comes last');
    }
}
