<?php

namespace Tests\Workflow;

use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\Customer;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\Unit;
use App\Models\Warehouse;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Follow-up to GitHub #132/#159 (2026-09-07): `CustomerReturnController::acceptSupply()` is the
 * docKey-based twin of `CustomerSupplyReturnController::accept()` (see
 * CustomerSupplyReturnRollUpTest for that one) and had the exact same flat-credit-no-rollup gap.
 * Fixed via the same `App\Support\SuppliesUnitStock::addQty()`.
 */
class CustomerReturnAcceptSupplyRollUpTest extends TestCase
{
    use ActingAsStaff;

    private array $units = [];

    protected function setUp(): void
    {
        parent::setUp();
        $rows = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $rows->count(), 'fixture butuh minimal 2 satuan aktif');
        $this->units = ['dos' => $rows[0], 'pcs' => $rows[1]];
    }

    private function mainWarehouseId(): int
    {
        return (int) Warehouse::query()
            ->where('warehouses.status', 1)
            ->whereHas('type', fn ($q) => $q->where('status', 1)->where('is_main_warehouse', 1))
            ->orderBy('warehouses.id')
            ->value('id');
    }

    private function makeLadderedSupplies(int $warehouseId, int $dosStock, int $pcsStock, int $ratio = 12): Supplies
    {
        $supplies = new Supplies();
        $supplies->supplies_name = 'Combined Return RollUp Test '.uniqid();
        $supplies->supplies_unit = json_encode([$this->units['dos']->unit_id, $this->units['pcs']->unit_id]);
        $supplies->supplies_default_unit = $this->units['pcs']->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = $this->units['dos']->unit_id;
        $relation->su_id_2 = $this->units['pcs']->unit_id;
        $relation->sr_value_1 = 1;
        $relation->sr_value_2 = $ratio;
        $relation->status = 1;
        $relation->save();

        foreach ([['dos', $dosStock], ['pcs', $pcsStock]] as [$key, $qty]) {
            $s = new SuppliesStock();
            $s->supplies_id = $supplies->supplies_id;
            $s->unit_id = $this->units[$key]->unit_id;
            $s->warehouse_id = $warehouseId;
            $s->ss_stock = $qty;
            $s->status = 1;
            $s->save();
        }

        return $supplies;
    }

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Combined Return RollUp Test Armada';
        $customer->customer_code = 'CRU'.random_int(1000, 9999);
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function currentStock(Supplies $s, string $unitKey, int $warehouseId): int
    {
        return (int) SuppliesStock::withoutGlobalScope('active_warehouse')
            ->where('supplies_id', $s->supplies_id)
            ->where('unit_id', $this->units[$unitKey]->unit_id)
            ->where('warehouse_id', $warehouseId)
            ->value('ss_stock');
    }

    private function staffId(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_accepting_a_supply_only_combined_return_rolls_up_together_with_stock_already_on_hand(): void
    {
        $this->actingAsSuperAdminStaff();

        $warehouseId = $this->mainWarehouseId();
        // 6 pcs already sitting there, below the 12-pcs DOS ratio on its own.
        $s = $this->makeLadderedSupplies($warehouseId, dosStock: 0, pcsStock: 6, ratio: 12);
        $customer = $this->createArmada();

        $record = new CustomerSupplyReturn();
        $record->return_number = 'PBM-CRT-'.uniqid();
        $record->customer_id = $customer->customer_id;
        $record->return_date = now()->toDateString();
        $record->proof_path = 'combined-return-rollup-test.png';
        $record->status = 1;
        $record->created_by = $this->staffId();
        $record->save();

        $detail = new CustomerSupplyReturnDetail();
        $detail->return_id = $record->return_id;
        $detail->supplies_id = $s->supplies_id;
        $detail->unit_id = $this->units['pcs']->unit_id;
        $detail->warehouse_id = $warehouseId;
        $detail->qty = 6; // existing 6 + returned 6 = 12 = exactly 1 DOS
        $detail->status = 1;
        $detail->save();

        // docKey "S:<id>" = supply-only bundle (no product side) -- see resolveBundle().
        $response = $this->post("/customerReturns/S:{$record->return_id}/accept");
        $response->assertOk();

        $this->assertSame(0, $this->currentStock($s, 'pcs', $warehouseId), 'BUG WOULD BE: stuck at 12 Piece, never rolled up');
        $this->assertSame(1, $this->currentStock($s, 'dos', $warehouseId), 'existing 6 + returned 6 = 12 = exactly 1 DOS');
    }
}
