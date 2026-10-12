<?php

namespace Tests\Workflow;

use App\Models\Customer;
use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\Supplies;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Retur dari PMO masuk tanpa gudang (warehouse_id NULL). accept() wajib menolak selama masih kosong,
 * dan menerima pilihan gudang dari modal Konfirmasi (supply_warehouses) sebelum ACC.
 */
class CustomerReturnAcceptWarehousePickTest extends TestCase
{
    use ActingAsStaff;

    private function mainWarehouseId(): int
    {
        return (int) Warehouse::query()
            ->where('warehouses.status', 1)
            ->whereHas('type', fn ($q) => $q->where('status', 1)->where('is_main_warehouse', 1))
            ->orderBy('warehouses.id')
            ->value('id');
    }

    private function makePendingReturnWithoutWarehouse(): CustomerSupplyReturnDetail
    {
        $unit = Unit::where('status', 1)->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Warehouse Pick Test '.uniqid();
        $supplies->supplies_unit = json_encode([$unit->unit_id]);
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $customer = new Customer();
        $customer->customer_name = 'Warehouse Pick Test Armada';
        $customer->customer_code = 'WPT'.random_int(1000, 9999);
        $customer->status = 1;
        $customer->save();

        $record = new CustomerSupplyReturn();
        $record->return_number = 'PBM-WPT-'.uniqid();
        $record->customer_id = $customer->customer_id;
        $record->return_date = now()->toDateString();
        $record->status = 1;
        $record->created_by = (int) DB::table('staffs')->where('status', 1)->value('staff_id');
        $record->save();

        $detail = new CustomerSupplyReturnDetail();
        $detail->return_id = $record->return_id;
        $detail->supplies_id = $supplies->supplies_id;
        $detail->unit_id = $unit->unit_id;
        $detail->warehouse_id = null;
        $detail->qty = 3;
        $detail->status = 1;
        $detail->save();

        return $detail;
    }

    public function test_accept_is_rejected_while_warehouse_is_empty(): void
    {
        $this->actingAsSuperAdminStaff();
        $detail = $this->makePendingReturnWithoutWarehouse();

        $this->postJson("/customerReturns/S:{$detail->return_id}/accept")->assertStatus(422);

        $this->assertSame(1, (int) CustomerSupplyReturn::find($detail->return_id)->status);
    }

    public function test_accept_saves_the_warehouse_picked_in_the_confirm_modal(): void
    {
        $this->actingAsSuperAdminStaff();
        $warehouseId = $this->mainWarehouseId();
        $this->assertGreaterThan(0, $warehouseId, 'fixture butuh gudang utama aktif');
        $detail = $this->makePendingReturnWithoutWarehouse();

        $this->postJson("/customerReturns/S:{$detail->return_id}/accept", [
            'supply_warehouses' => json_encode([
                ['return_detail_id' => $detail->return_detail_id, 'warehouse_id' => $warehouseId],
            ]),
        ])->assertOk();

        $this->assertSame($warehouseId, (int) $detail->fresh()->warehouse_id);
        $this->assertSame(2, (int) CustomerSupplyReturn::find($detail->return_id)->status);
    }
}
