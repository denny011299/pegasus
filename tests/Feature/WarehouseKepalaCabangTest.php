<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\StaffWarehouse;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

class WarehouseKepalaCabangTest extends TestCase
{
    use ActingAsStaff;

    private int $typeId;
    private int $staffA;
    private int $staffB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsSuperAdminStaff();

        $type = WarehouseType::create([
            'warehouse_type_name' => 'Tipe Kepala '.uniqid(),
            'is_main_warehouse' => 0,
            'status' => 1,
        ]);
        $this->typeId = (int) $type->id;

        $this->staffA = $this->makeStaff('Kepala A');
        $this->staffB = $this->makeStaff('Kepala B');
    }

    private function makeStaff(string $name): int
    {
        $staff = new Staff();
        $staff->staff_name = $name.' '.uniqid();
        $staff->status = 1;
        $staff->save();

        return (int) $staff->staff_id;
    }

    private function insertPayload(array $overrides = []): array
    {
        return array_merge([
            'warehouse_name' => 'Gudang Kepala '.uniqid(),
            'warehouse_type_id' => $this->typeId,
            'warehouse_address' => 'Jl. Test',
        ], $overrides);
    }

    public function test_insert_without_kepala_key_still_works_for_external_api(): void
    {
        $id = (new Warehouse())->insertWarehouse($this->insertPayload());

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        $this->assertFalse(
            StaffWarehouse::query()->where('warehouse_id', $id)->where('is_kepala_cabang', 1)->exists()
        );
    }

    public function test_insert_rejects_empty_kepala_when_key_is_sent(): void
    {
        $result = (new Warehouse())->insertWarehouse($this->insertPayload([
            'kepala_staff_id' => '',
        ]));

        $this->assertSame(-3, $result);
    }

    public function test_insert_assigns_staff_as_kepala_cabang(): void
    {
        $id = (new Warehouse())->insertWarehouse($this->insertPayload([
            'kepala_staff_id' => $this->staffA,
        ]));

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $pivot = StaffWarehouse::query()
            ->where('warehouse_id', $id)
            ->where('staff_id', $this->staffA)
            ->first();
        $this->assertNotNull($pivot);
        $this->assertSame(1, (int) $pivot->is_kepala_cabang);
    }

    public function test_update_rejects_staff_not_assigned_to_warehouse(): void
    {
        $id = (new Warehouse())->insertWarehouse($this->insertPayload([
            'kepala_staff_id' => $this->staffA,
        ]));

        $result = (new Warehouse())->updateWarehouse([
            'id' => $id,
            'warehouse_name' => 'Gudang Kepala Update '.uniqid(),
            'warehouse_type_id' => $this->typeId,
            'warehouse_address' => 'Jl. Test',
            'kepala_staff_id' => $this->staffB,
        ]);

        $this->assertSame(-3, $result);
    }

    public function test_update_switches_kepala_among_assigned_staff(): void
    {
        $id = (new Warehouse())->insertWarehouse($this->insertPayload([
            'kepala_staff_id' => $this->staffA,
        ]));

        StaffWarehouse::create([
            'staff_id' => $this->staffB,
            'warehouse_id' => $id,
            'is_kepala_cabang' => 0,
        ]);

        $result = (new Warehouse())->updateWarehouse([
            'id' => $id,
            'warehouse_name' => 'Gudang Kepala Switch '.uniqid(),
            'warehouse_type_id' => $this->typeId,
            'warehouse_address' => 'Jl. Test',
            'kepala_staff_id' => $this->staffB,
        ]);

        $this->assertSame($id, $result);
        $this->assertSame(
            1,
            (int) StaffWarehouse::query()->where('warehouse_id', $id)->where('staff_id', $this->staffB)->value('is_kepala_cabang')
        );
        $this->assertSame(
            0,
            (int) StaffWarehouse::query()->where('warehouse_id', $id)->where('staff_id', $this->staffA)->value('is_kepala_cabang')
        );
    }

    public function test_edit_staff_keeps_existing_kepala_row(): void
    {
        $id = (new Warehouse())->insertWarehouse($this->insertPayload([
            'kepala_staff_id' => $this->staffA,
        ]));

        $createdAt = StaffWarehouse::query()
            ->where('warehouse_id', $id)
            ->where('staff_id', $this->staffA)
            ->value('created_at');

        (new Staff())->applyStaffWarehouses($this->staffA, [$id]);

        $pivot = StaffWarehouse::query()
            ->where('warehouse_id', $id)
            ->where('staff_id', $this->staffA)
            ->first();
        $this->assertNotNull($pivot);
        $this->assertSame(1, (int) $pivot->is_kepala_cabang);
        $this->assertEquals($createdAt, $pivot->created_at);
    }

    public function test_cannot_unassign_warehouse_when_staff_is_kepala(): void
    {
        $id = (new Warehouse())->insertWarehouse($this->insertPayload([
            'kepala_staff_id' => $this->staffA,
        ]));

        $blocked = (new Staff())->kepalaUnassignError($this->staffA, []);
        $this->assertIsArray($blocked);
        $this->assertSame(-1, $blocked['status']);

        $this->assertTrue(
            StaffWarehouse::query()->where('warehouse_id', $id)->where('staff_id', $this->staffA)->where('is_kepala_cabang', 1)->exists()
        );
    }

    public function test_can_unassign_non_kepala_warehouse_without_touching_kepala(): void
    {
        $kepalaId = (new Warehouse())->insertWarehouse($this->insertPayload([
            'kepala_staff_id' => $this->staffA,
        ]));
        $extraId = (new Warehouse())->insertWarehouse($this->insertPayload());
        StaffWarehouse::create([
            'staff_id' => $this->staffA,
            'warehouse_id' => $extraId,
            'is_kepala_cabang' => 0,
        ]);

        $this->assertNull((new Staff())->kepalaUnassignError($this->staffA, [$kepalaId]));
        (new Staff())->applyStaffWarehouses($this->staffA, [$kepalaId]);

        $this->assertTrue(
            StaffWarehouse::query()->where('warehouse_id', $kepalaId)->where('staff_id', $this->staffA)->where('is_kepala_cabang', 1)->exists()
        );
        $this->assertFalse(
            StaffWarehouse::query()->where('warehouse_id', $extraId)->where('staff_id', $this->staffA)->exists()
        );
    }
}
