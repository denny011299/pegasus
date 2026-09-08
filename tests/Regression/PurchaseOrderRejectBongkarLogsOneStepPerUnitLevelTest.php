<?php

namespace Tests\Regression;

use App\Models\PurchaseOrder;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-09-08, GitHub #165): `SupplierController::bongkarSuppliesUntilEnough()` (used by
 * `tolakPO` when a PO's ordered unit no longer has enough stock on its own after receiving rolled
 * it up the unit ladder) broke the larger unit down ONE unit at a time in a while-loop, writing a
 * "Konversi unit (Bongkar)" + "Konversi unit (Hasil)" log pair on every single iteration. Cancelling
 * a PO whose shortfall required breaking down many larger units (e.g. needing 100 Piece, 18
 * Piece/DOS) flooded the stock history with dozens of 1-DOS-at-a-time conversion rows instead of
 * one. Fixed by computing the whole deficit in one shot and logging a single pair per unit level.
 *
 * Real unit ids used (same ones as ProductionUnitConversionFlowTest): 3 = Liter, 5 = Drum.
 */
class PurchaseOrderRejectBongkarLogsOneStepPerUnitLevelTest extends TestCase
{
    use ActingAsStaff;

    private const LITER = 3;
    private const DRUM = 5;

    public function test_rejecting_a_po_bongkars_the_whole_deficit_in_a_single_logged_step(): void
    {
        $this->actingAsSuperAdminStaff();

        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Reject Bongkar Batching Regression Ingredient';
        $supplies->supplies_unit = json_encode([self::LITER, self::DRUM]);
        $supplies->supplies_default_unit = self::LITER;
        $supplies->status = 1;
        $supplies->save();

        $variant = new SuppliesVariant();
        $variant->supplier_id = $supplier->supplier_id;
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplies_variant_name = 'Reject Bongkar Batching Regression Variant';
        $variant->supplies_variant_sku = 'REG-REJBONGKAR-'.uniqid();
        $variant->supplies_variant_barcode = 'REG-REJBONGKAR-BC-'.uniqid();
        $variant->supplies_variant_price = 1000;
        $variant->supplies_variant_stock = 0;
        $variant->status = 1;
        $variant->save();

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = self::DRUM;   // larger unit
        $relation->su_id_2 = self::LITER;  // smaller unit
        $relation->sr_value_1 = 1;
        $relation->sr_value_2 = 18; // 1 Drum = 18 Liter, mirroring the DOS/Piece ratio from #165
        $relation->status = 1;
        $relation->save();

        $literStock = new SuppliesStock();
        $literStock->supplies_id = $supplies->supplies_id;
        $literStock->unit_id = self::LITER;
        $literStock->warehouse_id = 1;
        $literStock->ss_stock = 4; // not enough alone for the qty being reversed below
        $literStock->status = 1;
        $literStock->save();

        $drumStock = new SuppliesStock();
        $drumStock->supplies_id = $supplies->supplies_id;
        $drumStock->unit_id = self::DRUM;
        $drumStock->warehouse_id = 1;
        $drumStock->ss_stock = 10; // plenty to cover the shortfall once broken down
        $drumStock->status = 1;
        $drumStock->save();

        // A PO was ordered in Liter and approved, rolling receipt up into Drum (real flow, per
        // App\Support\UnitRollUp) so by the time it's rejected, most of the stock sits in Drum.
        $po = new PurchaseOrder();
        $po->po_number = 'REG-REJBONGKAR-'.uniqid();
        $po->po_supplier = $supplier->supplier_id;
        $po->po_date = now()->toDateString();
        $po->po_total = 100000;
        $po->jenis_discount = 1;
        $po->po_desc = 'Reject bongkar batching regression PO';
        $po->po_img = json_encode([]);
        $po->status = 2;
        $po->acc_by = null;
        $po->pembayaran = 0;
        $po->save();

        DB::table('purchase_orders_details')->insert([
            'po_id' => $po->po_id,
            'supplies_variant_id' => $variant->supplies_variant_id,
            'pod_nama' => $variant->supplies_variant_name,
            'pod_variant' => $variant->supplies_variant_name,
            'pod_sku' => $variant->supplies_variant_sku,
            'unit_id' => self::LITER,
            'pod_qty' => 25, // > 4 on hand in Liter, needs 2 Drum (36 Liter) broken down
            'pod_harga' => 1000,
            'pod_subtotal' => 25000,
            'status' => 1,
        ]);

        $response = $this->post('/tolakPO', ['po_id' => $po->po_id]);
        $response->assertStatus(200);

        $literStock->refresh();
        $drumStock->refresh();

        // 2 Drums broken down: 4 + 36 = 40 Liter, minus 25 for the reversal = 15 Liter left.
        $this->assertSame(15, $literStock->ss_stock);
        $this->assertSame(8, $drumStock->ss_stock, '2 of the 10 Drums were broken down to cover the shortfall');

        // Exactly one Bongkar + one Hasil conversion log row, each
        // carrying the FULL batched amount (2 Drum / 36 Liter) -- not one pair per Drum broken down.
        $this->assertDatabaseHas('log_stocks', [
            'log_item_id' => $supplies->supplies_id,
            'log_notes' => 'Konversi unit (Bongkar) pembatalan PO',
            'log_jumlah' => 2,
            'unit_id' => self::DRUM,
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_item_id' => $supplies->supplies_id,
            'log_notes' => 'Konversi unit (Hasil) pembatalan PO',
            'log_jumlah' => 36,
            'unit_id' => self::LITER,
        ]);
        $this->assertSame(1, DB::table('log_stocks')
            ->where('log_item_id', $supplies->supplies_id)
            ->where('log_notes', 'Konversi unit (Bongkar) pembatalan PO')
            ->count(), 'the deficit must be bongkar-ed in a single logged step, not one row per unit broken down');
    }
}
