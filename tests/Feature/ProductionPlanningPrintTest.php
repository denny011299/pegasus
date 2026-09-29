<?php

namespace Tests\Feature;

use App\Models\ProductionPlanning;
use App\Models\ProductionPlanningItem;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

class ProductionPlanningPrintTest extends TestCase
{
    use ActingAsStaff;

    private function planning(string $status = 'released', int $warehouseId = 1): ProductionPlanning
    {
        $pp = (new ProductionPlanning())->forceFill([
            'pp_number' => 'PRINT-'.uniqid(), 'pp_date' => '2026-09-23',
            'warehouse_id' => $warehouseId, 'pp_status' => $status, 'status' => 1,
        ]);
        $pp->save();
        (new ProductionPlanningItem())->forceFill([
            'production_planning_id' => $pp->production_planning_id,
            'product_name' => 'Print Test Product', 'qty' => 12, 'unit_label' => 'DOS', 'status' => 1,
        ])->save();

        return $pp;
    }

    public function test_released_planning_prints_pdf_with_production_view_permission(): void
    {
        $this->actingAsStaffWithOnlyPermission('Produksi', ['view']);
        $this->withActiveWarehouse(1);
        $pp = $this->planning();
        $response = $this->get('/printProductionPlanning/'.$pp->production_planning_id);
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_draft_cannot_print_production_instruction(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(1);
        $pp = $this->planning('draft');
        $this->get('/printProductionPlanning/'.$pp->production_planning_id)->assertStatus(422);
    }

    public function test_other_warehouse_planning_is_not_exposed(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(1);
        $pp = $this->planning('released', 2);
        $this->get('/printProductionPlanning/'.$pp->production_planning_id)->assertNotFound();
    }

    public function test_staff_without_permission_cannot_print(): void
    {
        $this->actingAsStaffWithNoAccess();
        $this->withActiveWarehouse(1);
        $pp = $this->planning();
        $this->get('/printProductionPlanning/'.$pp->production_planning_id)->assertForbidden();
    }
}
