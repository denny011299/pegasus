<?php

namespace Tests\Regression;

use App\Models\PurchaseOrder;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-05): `ProductIssuesDetail::stockCheck()`'s bongkar closure (used by Return
 * Supplies, `SupplierController::insertReturnSupplies()`) used to find "the next larger unit's
 * stock row" via a plain array index one position over (`$units[$targetKey + 1]`), NOT by matching
 * `SuppliesRelation::su_id_1` the way Production's own bongkar closure does
 * (`ProductionController.php`'s `$siapkanStok`). The stock rows are ordered by `ss_id DESC`
 * (most-recently-created first) — so this only worked if the LARGER unit's `SuppliesStock` row
 * happened to be created BEFORE the smaller unit's row (see the sibling,
 * `tests/Workflow/ReturnSuppliesBongkarFlowTest.php`). If the smaller unit's row was created FIRST
 * instead (equally plausible in real provisioning order), the smaller unit's row ended up at the
 * LAST index, `$targetKey + 1` ran off the end of the array, and the bongkar closure returned
 * `false` immediately without ever finding the larger-unit row — even though the combined physical
 * stock was more than sufficient. `insertReturnSupplies` then rejected the return as `"Stok bahan
 * tidak mencukupi"`, touching no stock at all.
 *
 * Fix: the closure now looks up `SuppliesRelation::su_id_1` for the current unit FIRST, then finds
 * whichever array index holds that unit_id — matching Production's own `$siapkanStok` pattern
 * exactly. Array position is no longer relevant. The identical bug shape was also found (and fixed
 * in the same pass) in this method's sibling closure for `tipe_return == 2` (Retur Armada,
 * `$siapkanStokProd` using `ProductStock`/`ProductRelation`) — not previously flagged in
 * KNOWN_ISSUES.md, found while fixing this one.
 *
 * Real unit ids used (same ones as ProductionUnitConversionFlowTest, none renamed here):
 * 3 = Liter, 5 = Drum.
 */
class ReturnSuppliesBongkarFailsOnStockRowInsertionOrderTest extends TestCase
{
    use ActingAsStaff;

    private const LITER = 3;
    private const DRUM = 5;

    public function test_bongkar_now_succeeds_even_when_the_smaller_units_stock_row_was_created_first(): void
    {
        $this->actingAsSuperAdminStaff();

        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Return Bongkar Regression Ingredient';
        $supplies->supplies_unit = json_encode([self::LITER, self::DRUM]);
        $supplies->supplies_default_unit = self::LITER;
        $supplies->status = 1;
        $supplies->save();

        $variant = new SuppliesVariant();
        $variant->supplier_id = $supplier->supplier_id;
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplies_variant_name = 'Return Bongkar Regression Variant';
        $variant->supplies_variant_sku = 'REG-RETBONGKAR-'.uniqid();
        $variant->supplies_variant_barcode = 'REG-RETBONGKAR-BC-'.uniqid();
        $variant->supplies_variant_price = 1000;
        $variant->supplies_variant_stock = 0;
        $variant->status = 1;
        $variant->save();

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = self::DRUM;   // larger unit
        $relation->su_id_2 = self::LITER;  // smaller unit
        $relation->sr_value_1 = 1;
        $relation->sr_value_2 = 200; // 1 Drum = 200 Liter
        $relation->status = 1;
        $relation->save();

        // Smaller unit's row created FIRST (lower ss_id) — the reproducing order.
        $literStock = new SuppliesStock();
        $literStock->supplies_id = $supplies->supplies_id;
        $literStock->unit_id = self::LITER;
        $literStock->warehouse_id = 1;
        $literStock->ss_stock = 50;
        $literStock->status = 1;
        $literStock->save();

        $drumStock = new SuppliesStock();
        $drumStock->supplies_id = $supplies->supplies_id;
        $drumStock->unit_id = self::DRUM;
        $drumStock->warehouse_id = 1;
        $drumStock->ss_stock = 5; // 5 Drum = 1000 Liter-equivalent, plenty combined with the 50 Liter
        $drumStock->status = 1;
        $drumStock->save();

        $po = new PurchaseOrder();
        $po->po_number = 'REG-RETBONGKAR-'.uniqid();
        $po->po_supplier = $supplier->supplier_id;
        $po->po_date = now()->toDateString();
        $po->po_total = 1000000; // ample, so the return-amount guard never rejects this
        $po->jenis_discount = 1;
        $po->po_desc = 'Return Supplies bongkar regression PO';
        $po->po_img = json_encode([]);
        $po->status = 1;
        $po->save();

        $returnQty = 300; // more than the 50 Liter alone; 5 Drums would easily cover it if reached

        $response = $this->post('/insertReturnSupplies', [
            'po_id' => $po->po_id,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Bongkar regression test (smaller unit row created first)',
            'rs_total' => $returnQty * 1,
            'returs' => json_encode([[
                'supplies_id' => $variant->supplies_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'unit_id' => self::LITER,
                'rsd_qty' => $returnQty,
                'rsd_price' => 1,
            ]]),
        ]);

        $response->assertStatus(200);

        $literStock->refresh();
        $drumStock->refresh();
        $this->assertSame(150, $literStock->ss_stock, '2 Drums broken down (50 + 400 = 450), then 300 deducted for the return = 150');
        $this->assertSame(3, $drumStock->ss_stock, '2 of the 5 Drums were broken down to cover the shortfall, regardless of which row was created first');
    }
}
