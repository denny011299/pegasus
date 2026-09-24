<?php

namespace Tests\Regression;

use App\Models\Customer;
use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\Supplies;
use App\Models\Unit;
use App\Models\Warehouse;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #203 follow-up (2026-09-25): the External API's POST /shipments/returns no longer
 * auto-defaults bahan mentah / non-eceran produk lines to the main warehouse (previously
 * confirmed 2026-08-17, now reversed — see ShipmentReturnController's class docblock) — a
 * warehouse_id-NULL detail row can now come from ANY line type, not just retail-unit produk.
 *
 * This exposed a pre-existing bug in CustomerReturnController::resolveBundle(): the query
 * backing GET /customerReturns/{docKey} (which fills the admin "Edit Pengembalian" modal) joined
 * to `warehouses` with an INNER JOIN on warehouse_id. A NULL warehouse_id silently excluded the
 * whole detail row from the response — so the exact rows that most need manual warehouse
 * assignment never appeared in the modal for a staff member to fix. Fixed by switching both
 * queries (supply and product side) to leftJoin.
 *
 * Also documents that CustomerReturnController::acceptSupply()/validateSupplyDetails() already
 * rejects accept() for ANY detail row with a null warehouse_id (not just produk-eceran ones) --
 * this was already true before GitHub #203, just never exercised for a bahan mentah row.
 */
class PengembalianNullWarehouseAdminEditTest extends TestCase
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

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Null Warehouse Return Test Armada';
        $customer->customer_code = 'NWR'.random_int(1000, 9999);
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function staffId(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    private function makeSupplyReturnWithNullWarehouse(): array
    {
        $unit = Unit::where('status', 1)->first();
        $this->assertNotNull($unit, 'fixture butuh minimal 1 satuan aktif');

        $supplies = new Supplies();
        $supplies->supplies_name = 'Null Warehouse Return Test Supplies '.uniqid();
        $supplies->supplies_unit = json_encode([$unit->unit_id]);
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $customer = $this->createArmada();

        $record = new CustomerSupplyReturn();
        $record->return_number = 'PBM-NWR-'.uniqid();
        $record->return_group = 'PKR-NWR-'.uniqid();
        $record->customer_id = $customer->customer_id;
        $record->return_date = now()->toDateString();
        $record->proof_path = null;
        $record->status = 1;
        $record->created_by = $this->staffId();
        $record->save();

        $detail = new CustomerSupplyReturnDetail();
        $detail->return_id = $record->return_id;
        $detail->supplies_id = $supplies->supplies_id;
        $detail->unit_id = $unit->unit_id;
        $detail->warehouse_id = null;
        $detail->qty = 4;
        $detail->status = 1;
        $detail->save();

        return ['record' => $record, 'supplies' => $supplies, 'unit' => $unit];
    }

    public function test_show_returns_a_bahan_mentah_line_with_a_null_warehouse_instead_of_silently_dropping_it(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->makeSupplyReturnWithNullWarehouse();

        $response = $this->getJson('/customerReturns/S:'.$fx['record']->return_id);

        $response->assertOk();
        $details = $response->json('supply_details');
        $this->assertCount(1, $details, 'BUG WOULD BE: an INNER JOIN to warehouses silently drops the row when warehouse_id is NULL');
        $this->assertSame($fx['supplies']->supplies_id, (int) $details[0]['supplies_id']);
        $this->assertNull($details[0]['warehouse_id']);
        $this->assertNull($details[0]['warehouse_name']);
    }

    public function test_accept_rejects_a_bahan_mentah_line_with_a_null_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->makeSupplyReturnWithNullWarehouse();

        $response = $this->postJson('/customerReturns/S:'.$fx['record']->return_id.'/accept');

        $response->assertStatus(422);
        $this->assertSame(1, (int) CustomerSupplyReturn::find($fx['record']->return_id)->status, 'the document must stay Pending, not be accepted with an unresolved warehouse');
    }

    public function test_accept_succeeds_once_the_warehouse_is_filled_in(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->makeSupplyReturnWithNullWarehouse();
        $mainWarehouseId = $this->mainWarehouseId();
        $this->assertGreaterThan(0, $mainWarehouseId, 'fixture needs a main warehouse (is_main_warehouse=1) in the seeded data');

        CustomerSupplyReturnDetail::where('return_id', $fx['record']->return_id)
            ->update(['warehouse_id' => $mainWarehouseId]);

        $response = $this->postJson('/customerReturns/S:'.$fx['record']->return_id.'/accept');

        $response->assertOk();
        $this->assertSame(2, (int) CustomerSupplyReturn::find($fx['record']->return_id)->status, 'status 2 = accepted');
    }
}
