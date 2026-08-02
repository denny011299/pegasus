<?php

namespace Tests\Workflow;

use App\Models\PurchaseOrder;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/RETURN_SUPPLIES_FLOW.md's "Unit-conversion 'bongkar' inside
 * ProductIssuesDetail::stockCheck()" section for the full trace. Unlike Production's own bongkar
 * closure (which finds the larger unit's stock row by explicitly matching
 * `SuppliesRelation::su_id_1`), this implementation walks `$units[$targetKey + 1]` — a plain
 * ARRAY INDEX one position over — and simply assumes that position holds the next-larger unit's
 * stock row. Since the query orders rows by `ss_id DESC` (most-recently-created first), this only
 * works if the larger unit's `SuppliesStock` row happens to have been created BEFORE the smaller
 * unit's row. Real-world provisioning order isn't guaranteed either way — this fixture controls it
 * explicitly to prove the mechanism is genuinely order-dependent, not just "always the same as
 * Production's."
 *
 * Real unit ids used (same ones as ProductionUnitConversionFlowTest, none renamed here):
 * 3 = Liter, 5 = Drum.
 */
class ReturnSuppliesBongkarFlowTest extends TestCase
{
    use ActingAsStaff;

    private const LITER = 3;
    private const DRUM = 5;

    /** @return array{0: PurchaseOrder, 1: SuppliesVariant, 2: SuppliesStock, 3: SuppliesStock} */
    private function createFixture(bool $largerUnitRowCreatedFirst): array
    {
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Return Bongkar Test Ingredient';
        $supplies->supplies_unit = json_encode([self::LITER, self::DRUM]);
        $supplies->supplies_default_unit = self::LITER;
        $supplies->status = 1;
        $supplies->save();

        $variant = new SuppliesVariant();
        $variant->supplier_id = $supplier->supplier_id;
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplies_variant_name = 'Return Bongkar Test Variant';
        $variant->supplies_variant_sku = 'WF-RETBONGKAR-'.uniqid();
        $variant->supplies_variant_barcode = 'WF-RETBONGKAR-BC-'.uniqid();
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

        $makeLiterStock = function () use ($supplies) {
            $s = new SuppliesStock();
            $s->supplies_id = $supplies->supplies_id;
            $s->unit_id = self::LITER;
            $s->warehouse_id = 1;
            $s->ss_stock = 50;
            $s->status = 1;
            $s->save();
            return $s;
        };
        $makeDrumStock = function () use ($supplies) {
            $s = new SuppliesStock();
            $s->supplies_id = $supplies->supplies_id;
            $s->unit_id = self::DRUM;
            $s->warehouse_id = 1;
            $s->ss_stock = 5; // 5 Drum = 1000 Liter-equivalent, plenty combined with the 50 Liter
            $s->status = 1;
            $s->save();
            return $s;
        };

        if ($largerUnitRowCreatedFirst) {
            $drumStock = $makeDrumStock();
            $literStock = $makeLiterStock();
        } else {
            $literStock = $makeLiterStock();
            $drumStock = $makeDrumStock();
        }

        $po = new PurchaseOrder();
        $po->po_number = 'WF-RETBONGKAR-'.uniqid();
        $po->po_supplier = $supplier->supplier_id;
        $po->po_date = now()->toDateString();
        $po->po_total = 1000000; // ample, so the return-amount guard never rejects this
        $po->jenis_discount = 1;
        $po->po_desc = 'Return Supplies bongkar test PO';
        $po->po_img = json_encode([]);
        $po->status = 1;
        $po->save();

        return [$po, $variant, $literStock, $drumStock];
    }

    public function test_bongkar_succeeds_when_the_larger_units_stock_row_was_created_first(): void
    {
        $this->actingAsSuperAdminStaff();

        [$po, $variant, $literStock, $drumStock] = $this->createFixture(largerUnitRowCreatedFirst: true);

        $returnQty = 300; // more than the 50 Liter alone, needs 2 Drums broken down

        $response = $this->post('/insertReturnSupplies', [
            'po_id' => $po->po_id,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Bongkar workflow test (larger unit row created first)',
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
        $this->assertSame(3, $drumStock->ss_stock, '2 of the 5 Drums were broken down to cover the shortfall');
    }
}
