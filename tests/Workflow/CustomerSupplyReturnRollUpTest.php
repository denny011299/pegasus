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
 * Follow-up to GitHub #132/#159 (2026-09-07): the Bahan/Supplies side of Pengembalian
 * (`CustomerSupplyReturnController::accept()`) had the same flat-credit-no-rollup gap as
 * `CustomerProductReturnController::accept()` (fixed for #132), but there was no `ss_stock`
 * equivalent of `ProductUnitStock::addQty()` to reuse -- flagged-but-not-fixed in
 * `cdocs/testing/KNOWN_ISSUES.md`. Fixed via the new `App\Support\SuppliesUnitStock::addQty()`.
 */
class CustomerSupplyReturnRollUpTest extends TestCase
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

    /** 1 DOS = 12 pcs, kedua satuan sudah punya baris SuppliesStock aktif di $warehouseId. */
    private function makeLadderedSupplies(int $warehouseId, int $dosStock, int $pcsStock, int $ratio = 12): Supplies
    {
        $supplies = new Supplies();
        $supplies->supplies_name = 'Supply Return RollUp Test '.uniqid();
        $supplies->supplies_unit = json_encode([$this->units['dos']->unit_id, $this->units['pcs']->unit_id]);
        $supplies->supplies_default_unit = $this->units['pcs']->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = $this->units['dos']->unit_id; // besar
        $relation->su_id_2 = $this->units['pcs']->unit_id; // kecil
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
        $customer->customer_name = 'Supply Return RollUp Test Armada';
        $customer->customer_code = 'SRU'.random_int(1000, 9999);
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

    private function makeReturn(Customer $customer, Supplies $s, int $warehouseId, string $unitKey, int $qty): CustomerSupplyReturn
    {
        $record = new CustomerSupplyReturn();
        $record->return_number = 'PBM-TEST-'.uniqid();
        $record->customer_id = $customer->customer_id;
        $record->return_date = now()->toDateString();
        $record->proof_path = 'supply-return-rollup-test.png';
        $record->status = 1;
        $record->created_by = $this->staffId();
        $record->save();

        $detail = new CustomerSupplyReturnDetail();
        $detail->return_id = $record->return_id;
        $detail->supplies_id = $s->supplies_id;
        $detail->unit_id = $this->units[$unitKey]->unit_id;
        $detail->warehouse_id = $warehouseId;
        $detail->qty = $qty;
        $detail->status = 1;
        $detail->save();

        return $record;
    }

    private function staffId(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_returning_pcs_rolls_up_into_the_bigger_unit(): void
    {
        $this->actingAsSuperAdminStaff();

        $warehouseId = $this->mainWarehouseId();
        $s = $this->makeLadderedSupplies($warehouseId, dosStock: 0, pcsStock: 0, ratio: 12);
        $customer = $this->createArmada();

        // Dikembalikan 100 pcs (1 DOS = 12 pcs) -> harus tergulung jadi 8 DOS + 4 Piece.
        $record = $this->makeReturn($customer, $s, $warehouseId, 'pcs', 100);

        $response = $this->post("/customerSupplyReturns/{$record->return_id}/accept");
        $response->assertOk();

        $this->assertSame(8, $this->currentStock($s, 'dos', $warehouseId));
        $this->assertSame(4, $this->currentStock($s, 'pcs', $warehouseId));
    }

    public function test_returning_pcs_rolls_up_together_with_stock_already_on_hand(): void
    {
        $this->actingAsSuperAdminStaff();

        $warehouseId = $this->mainWarehouseId();
        // 6 pcs already sitting there, below the 12-pcs DOS ratio on its own.
        $s = $this->makeLadderedSupplies($warehouseId, dosStock: 0, pcsStock: 6, ratio: 12);
        $customer = $this->createArmada();

        // 6 more pcs returned -- not a multiple of 12 by itself, but 6 + 6 = 12 = exactly 1 DOS.
        $record = $this->makeReturn($customer, $s, $warehouseId, 'pcs', 6);

        $response = $this->post("/customerSupplyReturns/{$record->return_id}/accept");
        $response->assertOk();

        $this->assertSame(0, $this->currentStock($s, 'pcs', $warehouseId), 'BUG WOULD BE: stuck at 12 Piece, never rolled up');
        $this->assertSame(1, $this->currentStock($s, 'dos', $warehouseId), 'existing 6 + returned 6 = 12 = exactly 1 DOS');
    }
}
