<?php

namespace Tests\Workflow;

use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\Support\ResolvesTestWarehouses;
use Tests\TestCase;

/**
 * "Peringatan Stok Bahan Mentah" side of the Stock Alert rewrite (rubenyw, Aug 7-9 2026, no
 * tests added upstream) — App\Models\StockAlertSupplies. Same shape as
 * tests/Workflow/StockAlertFlowTest.php (product side) but with its own separate unit-conversion
 * implementation: every stored value (supplies_alert, supplies_min_stock, safety_stock) is kept
 * in `supplies.supplies_default_unit` and, on read, displayed in whichever unit the ACTIVE
 * WAREHOUSE's type calls for — see StockAlertSupplies::resolveEceranUnitId()/convertQty(), which
 * walks supplies_relations like a graph, unlike ProductUnitStock's relation-table approach used on
 * the product side.
 *
 * GitHub #84 investigated whether that per-warehouse-type split was itself a bug (a MAIN warehouse
 * reports quantities in the supply's DEFAULT unit e.g. DOS; an ECERAN/retail warehouse reports them
 * converted into the leaf "eceran" unit e.g. Piece — `$isEceranWarehouse` in
 * getStockAlertSupplies()). Confirmed with the repo owner 2026-08-29: this is the intended
 * behavior, not a bug — a main/bulk warehouse tracks in the bulk unit, a retail warehouse tracks in
 * the retail unit. #84 closed as "working as intended"; this suite now covers BOTH branches
 * explicitly (it previously only exercised the main-warehouse path, and did so with an assumption —
 * always-leaf-conversion — that predated the warehouse-type split entirely).
 *
 * The WRITE side (updateStockAlertSupplies()/updateMinOrderSupplies()) is warehouse-agnostic on
 * purpose: staff always type thresholds in the small/eceran unit regardless of which warehouse is
 * active, and the value is converted back to the default unit for storage either way — see
 * test_updateStockAlertSupplies_persists_the_value_converted_back_to_the_default_unit(), which is
 * not warehouse-type-sensitive and needs no main/eceran split.
 *
 * Usage (for avg_daily) comes from log_stocks (log_type=2, log_category=2, log_notes LIKE
 * '%Pengurangan bahan untuk produksi%'), not sales_order_details — bahan mentah is consumed by
 * Production, not sold directly.
 *
 * GET /getStockAlertSupplies used to 500 unconditionally on any active supply row (undefined
 * `$leadTimeDays` in StockAlertSupplies::getStockAlertSupplies(), GitHub issue #35) — fixed
 * upstream, see tests/Regression/StockAlertSuppliesUndefinedVariableCrashTest.
 */
class StockAlertSuppliesFlowTest extends TestCase
{
    use ActingAsStaff;
    use ResolvesTestWarehouses;

    private const MAIN_WAREHOUSE_ID = 1;
    private const DOS_UNIT_ID = 7;   // parent unit, used as supplies_default_unit
    private const PIECE_UNIT_ID = 9; // child/leaf unit -> resolved as the "eceran" unit
    private const DOS_TO_PIECE = 12; // 1 DOS = 12 Piece

    private function createSupply(int $leadTimeDays = 0, int $safetyStockInDefaultUnit = 0): Supplies
    {
        $supply = new Supplies();
        $supply->supplies_name = 'Stock Alert Supplies Test '.uniqid();
        $supply->supplies_unit = json_encode([self::DOS_UNIT_ID, self::PIECE_UNIT_ID]);
        $supply->supplies_default_unit = self::DOS_UNIT_ID;
        $supply->lead_time_days = $leadTimeDays;
        $supply->safety_stock = $safetyStockInDefaultUnit;
        $supply->status = 1;
        $supply->save();

        return $supply;
    }

    private function relateDosToPiece(Supplies $supply): void
    {
        $relation = new SuppliesRelation();
        $relation->supplies_id = $supply->supplies_id;
        $relation->su_id_1 = self::DOS_UNIT_ID;   // parent (larger)
        $relation->su_id_2 = self::PIECE_UNIT_ID; // child (smaller/leaf)
        $relation->sr_value_1 = 1;
        $relation->sr_value_2 = self::DOS_TO_PIECE;
        $relation->status = 1;
        $relation->save();
    }

    private function createStock(Supplies $supply, float $stockInDosUnit, int $warehouseId = self::MAIN_WAREHOUSE_ID): SuppliesStock
    {
        $ss = new SuppliesStock();
        $ss->supplies_id = $supply->supplies_id;
        $ss->unit_id = self::DOS_UNIT_ID;
        $ss->warehouse_id = $warehouseId;
        $ss->ss_stock = $stockInDosUnit;
        $ss->status = 1;
        $ss->save();

        return $ss;
    }

    private function logProductionUsage(Supplies $supply, float $qtyInPiece, int $warehouseId = self::MAIN_WAREHOUSE_ID): void
    {
        DB::table('log_stocks')->insert([
            'log_date' => now(),
            'log_kode' => 'TEST-'.uniqid(),
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $supply->supplies_id,
            'log_notes' => 'Pengurangan bahan untuk produksi (test fixture)',
            'log_jumlah' => $qtyInPiece,
            'unit_id' => self::PIECE_UNIT_ID,
            'status' => 1,
            'warehouse_id' => $warehouseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getAlertFor(int $suppliesId, int $warehouseId = self::MAIN_WAREHOUSE_ID): ?object
    {
        $response = $this->get('/getStockAlertSupplies?warehouse_id='.$warehouseId);
        $response->assertStatus(200);

        $row = collect($response->json())->first(fn ($r) => (int) $r['supplies_id'] === $suppliesId);

        return $row === null ? null : (object) $row;
    }

    public function test_a_main_warehouse_keeps_values_in_the_default_unit_unconverted(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        // lead_time_days=3, safety_stock=2 DOS (stays 2 DOS -- main warehouse doesn't convert).
        $supply = $this->createSupply(leadTimeDays: 3, safetyStockInDefaultUnit: 2);
        $this->relateDosToPiece($supply);
        $supply->supplies_alert = 1; // stays 1 DOS
        $supply->save();
        $this->createStock($supply, stockInDosUnit: 0);
        // 90 Piece used today -> converted DOWN to the display unit (DOS): 90/12 = 7.5 DOS,
        // avg_daily = 7.5/30 = 0.25.
        $this->logProductionUsage($supply, qtyInPiece: 90);

        $row = $this->getAlertFor($supply->supplies_id);
        $this->assertNotNull($row, 'supply must appear in the stock alert list');

        $this->assertSame(self::DOS_UNIT_ID, $row->unit_id, 'a main warehouse displays the default unit (DOS), not the leaf eceran unit');
        $this->assertFalse((bool) $row->is_eceran_warehouse);
        $this->assertEqualsWithDelta(0.25, $row->avg_daily, 0.0001, '90 Piece usage converted down to 7.5 DOS, /30 days');
        $this->assertEqualsWithDelta(2.0, $row->safety_stock, 0.0001, 'already in the default unit, no conversion applied');
        $this->assertSame(3, $row->reorder_point, 'ceil(0.25*3 + 2.0) = 3');
        $this->assertEqualsWithDelta(0.0, $row->current_stock, 0.0001);
        $this->assertEqualsWithDelta(1.0, $row->supplies_alert, 0.0001, 'alert threshold left in DOS, unconverted');
        $this->assertNull($row->min_order_manual);
        $this->assertFalse($row->min_order_is_manual);
        $this->assertSame(1, $row->min_order, 'falls back to the (unconverted) alert threshold');
        $this->assertSame(1, $row->minim_order, 'max(0, round(1 - 0)) = 1');
    }

    public function test_an_eceran_warehouse_converts_values_to_the_leaf_unit(): void
    {
        $this->actingAsSuperAdminStaff();
        $warehouseId = $this->resolveActiveRetailWarehouseId('Peringatan Stok Bahan Mentah');
        $this->withActiveWarehouse($warehouseId);

        // lead_time_days=3, safety_stock=2 DOS (-> 24 Piece once converted).
        $supply = $this->createSupply(leadTimeDays: 3, safetyStockInDefaultUnit: 2);
        $this->relateDosToPiece($supply);
        $supply->supplies_alert = 1; // 1 DOS -> 12 Piece
        $supply->save();
        $this->createStock($supply, stockInDosUnit: 0, warehouseId: $warehouseId);
        // 90 Piece used today -> avg_daily = 90/30 = 3.
        $this->logProductionUsage($supply, qtyInPiece: 90, warehouseId: $warehouseId);

        $row = $this->getAlertFor($supply->supplies_id, $warehouseId);
        $this->assertNotNull($row, 'supply must appear in the stock alert list');

        $this->assertSame(self::PIECE_UNIT_ID, $row->unit_id, 'an eceran warehouse resolves to the leaf unit (Piece), not the default (DOS)');
        $this->assertTrue((bool) $row->is_eceran_warehouse);
        $this->assertEqualsWithDelta(3.0, $row->avg_daily, 0.0001);
        $this->assertEqualsWithDelta(24.0, $row->safety_stock, 0.0001, '2 DOS safety stock converted to 24 Piece');
        $this->assertSame(33, $row->reorder_point, 'ceil(3*3 + 24) = 33');
        $this->assertEqualsWithDelta(0.0, $row->current_stock, 0.0001);
        $this->assertEqualsWithDelta(12.0, $row->supplies_alert, 0.0001, '1 DOS alert converted to 12 Piece');
        $this->assertNull($row->min_order_manual);
        $this->assertFalse($row->min_order_is_manual);
        $this->assertSame(12, $row->min_order, 'falls back to the (converted) alert threshold');
        $this->assertSame(12, $row->minim_order, 'max(0, round(12 - 0)) = 12');
    }

    public function test_manual_min_stock_override_replaces_the_alert_threshold_in_a_main_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $supply = $this->createSupply();
        $this->relateDosToPiece($supply);
        $supply->supplies_alert = 1;        // stays 1 DOS if it were used (it must NOT be)
        $supply->supplies_min_stock = 2;    // stays 2 DOS -- main warehouse doesn't convert
        $supply->save();
        $this->createStock($supply, stockInDosUnit: 0);

        $row = $this->getAlertFor($supply->supplies_id);
        $this->assertNotNull($row);

        $this->assertSame(2, $row->min_order_manual);
        $this->assertTrue($row->min_order_is_manual);
        $this->assertSame(2, $row->min_order);
        $this->assertSame(2, $row->minim_order, 'manual override wins outright, the 1-DOS alert threshold must be ignored');
    }

    public function test_manual_min_stock_override_replaces_the_alert_threshold_in_an_eceran_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();
        $warehouseId = $this->resolveActiveRetailWarehouseId('Peringatan Stok Bahan Mentah');
        $this->withActiveWarehouse($warehouseId);

        $supply = $this->createSupply();
        $this->relateDosToPiece($supply);
        $supply->supplies_alert = 1;        // -> 12 Piece if it were used (it must NOT be)
        $supply->supplies_min_stock = 2;    // -> 24 Piece, DOS -> Piece
        $supply->save();
        $this->createStock($supply, stockInDosUnit: 0, warehouseId: $warehouseId);

        $row = $this->getAlertFor($supply->supplies_id, $warehouseId);
        $this->assertNotNull($row);

        $this->assertSame(24, $row->min_order_manual);
        $this->assertTrue($row->min_order_is_manual);
        $this->assertSame(24, $row->min_order);
        $this->assertSame(24, $row->minim_order, 'manual override wins outright, the 12-Piece alert threshold must be ignored');
    }

    public function test_supply_with_no_relations_uses_its_default_unit_directly(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $supply = new Supplies();
        $supply->supplies_name = 'Stock Alert Supplies No-Relation Test '.uniqid();
        $supply->supplies_unit = json_encode([self::PIECE_UNIT_ID]);
        $supply->supplies_default_unit = self::PIECE_UNIT_ID;
        $supply->supplies_alert = 5;
        $supply->status = 1;
        $supply->save();

        $ss = new SuppliesStock();
        $ss->supplies_id = $supply->supplies_id;
        $ss->unit_id = self::PIECE_UNIT_ID;
        $ss->warehouse_id = self::MAIN_WAREHOUSE_ID;
        $ss->ss_stock = 1;
        $ss->status = 1;
        $ss->save();

        $row = $this->getAlertFor($supply->supplies_id);
        $this->assertNotNull($row);
        $this->assertSame(self::PIECE_UNIT_ID, $row->unit_id, 'no relation rows -> eceran unit falls back to the default unit itself');
        $this->assertEqualsWithDelta(5.0, $row->supplies_alert, 0.0001, 'no conversion factor applies (fromUnit === toUnit)');
        $this->assertSame(4, $row->minim_order, 'max(0, round(5 - 1)) = 4');
    }

    public function test_updateStockAlertSupplies_persists_the_value_converted_back_to_the_default_unit(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $supply = $this->createSupply();
        $this->relateDosToPiece($supply);

        // UI sends the eceran (Piece) value; stored value must be converted back to DOS.
        $this->post('/updateStockAlertSupplies', [
            'supplies_id' => $supply->supplies_id,
            'alert_stock' => 36,
        ])->assertJson(['success' => true]);

        $this->assertSame(3, $supply->fresh()->supplies_alert, '36 Piece / 12 = 3 DOS');
    }

    public function test_updateMinOrderSupplies_persists_and_can_be_reset_back_to_automatic(): void
    {
        // updateMinOrderSupplies() is warehouse-agnostic on purpose (see class docblock) -- the
        // input is always the eceran/leaf-unit value regardless of which warehouse is active, and
        // it's always converted back to the default unit for storage. Run the write half under the
        // main warehouse and confirm the WRITE-side conversion (input Piece -> stored DOS) is
        // unaffected by the reader's warehouse-type display split covered elsewhere in this suite.
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $supply = $this->createSupply();
        $this->relateDosToPiece($supply);
        $supply->supplies_alert = 1; // stays 1 DOS as read back in this (main) warehouse
        $supply->save();
        $this->createStock($supply, stockInDosUnit: 0);

        $this->post('/updateMinOrderSupplies', [
            'supplies_id' => $supply->supplies_id,
            'min_order' => 24, // eceran (Piece) -- always, regardless of active warehouse
        ])->assertJson(['success' => true]);

        $this->assertSame(2, $supply->fresh()->supplies_min_stock, '24 Piece / 12 = 2 DOS stored');

        $row = $this->getAlertFor($supply->supplies_id);
        $this->assertTrue($row->min_order_is_manual);
        $this->assertSame(2, $row->min_order, 'main warehouse reads it back unconverted (2 DOS)');

        $this->post('/updateMinOrderSupplies', [
            'supplies_id' => $supply->supplies_id,
            'min_order' => '',
        ])->assertJson(['success' => true]);

        $this->assertNull($supply->fresh()->supplies_min_stock);

        $row = $this->getAlertFor($supply->supplies_id);
        $this->assertFalse($row->min_order_is_manual);
        $this->assertSame(1, $row->min_order, 'falls back to the (unconverted) alert threshold once cleared');
    }

    public function test_updateMinOrderSupplies_read_back_through_an_eceran_warehouse_stays_converted(): void
    {
        // Companion to the test above: same warehouse-agnostic write, but read back through an
        // ECERAN warehouse this time, confirming the display-side conversion (covered by
        // test_an_eceran_warehouse_converts_values_to_the_leaf_unit()) also applies to a manually
        // overridden threshold, not just the automatic alert one.
        $this->actingAsSuperAdminStaff();
        $warehouseId = $this->resolveActiveRetailWarehouseId('Peringatan Stok Bahan Mentah');
        $this->withActiveWarehouse($warehouseId);

        $supply = $this->createSupply();
        $this->relateDosToPiece($supply);
        $supply->supplies_alert = 1; // -> 12 Piece
        $supply->save();
        $this->createStock($supply, stockInDosUnit: 0, warehouseId: $warehouseId);

        $this->post('/updateMinOrderSupplies', [
            'supplies_id' => $supply->supplies_id,
            'min_order' => 24, // eceran (Piece)
        ])->assertJson(['success' => true]);

        $this->assertSame(2, $supply->fresh()->supplies_min_stock, '24 Piece / 12 = 2 DOS stored, same as any warehouse');

        $row = $this->getAlertFor($supply->supplies_id, $warehouseId);
        $this->assertTrue($row->min_order_is_manual);
        $this->assertSame(24, $row->min_order, 'eceran warehouse reads it back converted (24 Piece)');

        $this->post('/updateMinOrderSupplies', [
            'supplies_id' => $supply->supplies_id,
            'min_order' => '',
        ])->assertJson(['success' => true]);

        $this->assertNull($supply->fresh()->supplies_min_stock);

        $row = $this->getAlertFor($supply->supplies_id, $warehouseId);
        $this->assertFalse($row->min_order_is_manual);
        $this->assertSame(12, $row->min_order, 'falls back to the (converted) alert threshold once cleared');
    }

    public function test_updateMinOrderSupplies_rejects_a_negative_value(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $supply = $this->createSupply();

        $this->post('/updateMinOrderSupplies', [
            'supplies_id' => $supply->supplies_id,
            'min_order' => -3,
        ])->assertJson(['success' => false]);

        $this->assertNull($supply->fresh()->supplies_min_stock);
    }
}
