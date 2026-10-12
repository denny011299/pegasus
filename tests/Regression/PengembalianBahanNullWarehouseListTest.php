<?php

namespace Tests\Regression;

use App\Models\Customer;
use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\Supplies;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #216 follow-up (2026-10-13): potongan barang dari POST /payments/cash membuat dokumen
 * Pengembalian bahan dengan warehouse_id NULL di detailnya, supaya staf IPM yang memilih gudang.
 * Filter daftar Pengembalian (GET /customerReturns) untuk sisi bahan dulu hanya mencocokkan
 * warehouse_id persis, jadi dokumen itu tidak pernah tampil di gudang mana pun dan gudangnya tidak
 * bisa dipilih. Sekarang mengikuti aturan sisi produk: baris tanpa gudang tampil di gudang utama
 * saja, tidak di gudang eceran.
 */
class PengembalianBahanNullWarehouseListTest extends TestCase
{
    use ActingAsStaff;

    private function warehouseId(bool $main): ?int
    {
        $id = DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('wt.is_main_warehouse', $main ? 1 : 0)->where('w.status', 1)
            ->orderBy('w.id')->value('w.id');

        return $id !== null ? (int) $id : null;
    }

    /** Dokumen bahan berkelompok (return_group) dengan satu baris tanpa gudang. */
    private function createNullWarehouseSupplyReturn(?string $group): CustomerSupplyReturn
    {
        $customer = new Customer();
        $customer->customer_name = 'Null WH Armada';
        $customer->customer_code = 'NWH'.random_int(100000, 999999);
        $customer->customer_notes = 'Armada Null WH';
        $customer->status = 1;
        $customer->save();

        $unit = new Unit();
        $unit->unit_name = 'Null WH Unit '.uniqid();
        $unit->unit_short_name = 'NW-'.random_int(1000, 9999);
        $unit->status = 1;
        $unit->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Null WH Bahan '.uniqid();
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->supplies_unit = json_encode([(string) $unit->unit_id]);
        $supplies->supplies_alert = 0;
        $supplies->status = 1;
        $supplies->save();

        $return = CustomerSupplyReturn::create([
            'return_number' => (new CustomerSupplyReturn())->generateReturnNumber(),
            'return_group' => $group,
            'customer_id' => $customer->customer_id,
            'return_date' => '2026-10-13',
            'ref_number' => 'NWH-'.uniqid(),
            'status' => 1,
        ]);

        $detail = new CustomerSupplyReturnDetail();
        $detail->return_id = $return->return_id;
        $detail->supplies_id = $supplies->supplies_id;
        $detail->unit_id = $unit->unit_id;
        $detail->warehouse_id = null;
        $detail->qty = 3;
        $detail->status = 1;
        $detail->save();

        return $return;
    }

    private function listContains(int $warehouseId, string $search): bool
    {
        $this->assignWarehousesToActingStaff($warehouseId);
        $this->withActiveWarehouse($warehouseId);
        $response = $this->getJson('/customerReturns?'.http_build_query(['draw' => 1, 'search' => ['value' => $search]]));

        // Gudang eceran saat ini tidak diberi menu Pengiriman sama sekali (403) — itu juga
        // berarti dokumennya tidak terlihat di sana.
        if ($response->status() === 403) {
            return false;
        }

        return count($response->assertOk()->json('data')) > 0;
    }

    public function test_grouped_bahan_return_without_warehouse_shows_in_main_only(): void
    {
        $this->actingAsSuperAdminStaff();
        $return = $this->createNullWarehouseSupplyReturn('PKR-NWH-'.random_int(100000, 999999));

        $this->assertTrue($this->listContains($this->warehouseId(true), $return->ref_number));

        $retail = $this->warehouseId(false);
        if ($retail !== null) {
            $this->assertFalse($this->listContains($retail, $return->ref_number));
        }
    }

    public function test_standalone_bahan_return_without_warehouse_shows_in_main_only(): void
    {
        $this->actingAsSuperAdminStaff();
        $return = $this->createNullWarehouseSupplyReturn(null);

        $this->assertTrue($this->listContains($this->warehouseId(true), $return->ref_number));

        $retail = $this->warehouseId(false);
        if ($retail !== null) {
            $this->assertFalse($this->listContains($retail, $return->ref_number));
        }
    }
}
