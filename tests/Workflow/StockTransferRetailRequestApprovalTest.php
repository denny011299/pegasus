<?php

namespace Tests\Workflow;

use App\Models\Staff;
use App\Models\StaffWarehouse;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use App\Support\RoleIds;
use App\Support\StockTransferApproval;
use Tests\TestCase;

/**
 * Sequential approval chain for the fase-2 "permintaan eceran" stock transfer
 * (App\Support\StockTransferApproval). A retail warehouse requests stock from a
 * main warehouse; before anything is shipped, staff AT THE ORIGIN sign off in a
 * fixed order — QC & Gudang first, then Kepala Operasional — and only once the
 * chain is complete does the transfer become shippable.
 *
 * Why this file exists separately from StockTransferWorkflowTest: that one
 * covers what shipping/receiving does to ProductStock (packing, unpacking,
 * retail conversion). This one covers WHO may act and IN WHAT ORDER, which is
 * pure decision logic over staff/warehouse assignment rows and never touches
 * stock at all. The two failure modes are unrelated, so they stay apart.
 *
 * Fixtures are built fresh via Eloquent rather than picked out of the seeded
 * data, per this program's convention (see the pegasus-testing skill): the
 * predicates below key off "is there ANY active QC staff assigned to this
 * warehouse", so reusing a seeded warehouse would make every assertion depend
 * on whichever staff rows that dataset happens to carry. A brand-new warehouse
 * with exactly the staff each test wants is the only way these stay
 * deterministic across the default seed and the okeh8644 snapshot alike (see
 * memory pegasus-testing-db-multiwarehouse-drift).
 *
 * Note the approval predicates take `$header` as a loose object and read
 * `qc_approved_by`/`ops_approved_by` off it — a real StockTransfer row is used
 * here rather than a stub so the column names stay honest against the schema
 * added by 2026_08_24_003700_add_approval_columns_to_stock_transfers_table.
 */
class StockTransferRetailRequestApprovalTest extends TestCase
{
    private int $mainWarehouseId;
    private int $retailWarehouseId;
    private int $qcStaffId;
    private int $kepalaStaffId;
    private int $requesterStaffId;

    protected function setUp(): void
    {
        parent::setUp();

        $mainType = WarehouseType::create([
            'warehouse_type_name' => 'Utama ST '.uniqid(),
            'is_main_warehouse' => 1,
            'status' => 1,
        ]);
        $retailType = WarehouseType::create([
            'warehouse_type_name' => 'Eceran ST '.uniqid(),
            'is_main_warehouse' => 0,
            'status' => 1,
        ]);

        $this->mainWarehouseId = $this->makeWarehouse('Gudang Utama ST ', $mainType->id);
        $this->retailWarehouseId = $this->makeWarehouse('Gudang Eceran ST ', $retailType->id);

        $this->qcStaffId = $this->makeStaff('QC ST ', RoleIds::QC_GUDANG);
        $this->kepalaStaffId = $this->makeStaff('Kepala ST ', 3);
        $this->requesterStaffId = $this->makeStaff('Pemohon Eceran ST ', 3);
    }

    private function makeWarehouse(string $prefix, $typeId): int
    {
        $w = new Warehouse();
        $w->warehouse_name = $prefix.uniqid();
        $w->warehouse_type_id = $typeId;
        $w->status = 1;
        $w->save();

        return (int) $w->id;
    }

    private function makeStaff(string $prefix, int $roleId): int
    {
        $s = new Staff();
        $s->staff_name = $prefix.uniqid();
        $s->role_id = $roleId;
        $s->status = 1;
        $s->save();

        return (int) $s->staff_id;
    }

    private function assign(int $staffId, int $warehouseId, bool $isKepala = false): void
    {
        StaffWarehouse::create([
            'staff_id' => $staffId,
            'warehouse_id' => $warehouseId,
            'is_kepala_cabang' => $isKepala ? 1 : 0,
        ]);
    }

    /** Staff row as the session user object the predicates receive. */
    private function user(int $staffId): Staff
    {
        return Staff::query()->where('staff_id', $staffId)->firstOrFail();
    }

    private function header(array $overrides = []): StockTransfer
    {
        return StockTransfer::create(array_merge([
            'transfer_code' => 'ST-TEST-'.strtoupper(uniqid()),
            'transfer_date' => date('Y-m-d'),
            'sender_id' => $this->requesterStaffId,
            'from_warehouse_id' => $this->mainWarehouseId,
            'to_warehouse_id' => $this->retailWarehouseId,
            'source_type' => 'retail_request',
            'status' => 1,
        ], $overrides));
    }

    // ---------------------------------------------------------------- routing

    public function test_only_a_main_to_retail_retail_request_counts_as_the_approval_route(): void
    {
        $this->assertTrue(StockTransferApproval::isRetailRequestRoute('retail_request', true, false));

        // Right source_type, wrong direction — a main warehouse pulling from
        // another main one is the ordinary transfer, not a retail request.
        $this->assertFalse(StockTransferApproval::isRetailRequestRoute('retail_request', true, true));
        $this->assertFalse(StockTransferApproval::isRetailRequestRoute('retail_request', false, false));

        // Right direction, wrong source_type — a production or manual transfer
        // main->retail still skips QC/Ops entirely.
        $this->assertFalse(StockTransferApproval::isRetailRequestRoute('production', true, false));
        $this->assertFalse(StockTransferApproval::isRetailRequestRoute(null, true, false));
    }

    public function test_a_non_retail_request_never_requires_approval_even_with_qc_and_ops_present(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId, isKepala: true);

        $this->assertFalse(StockTransferApproval::requiresApproval(
            'production', true, false, $this->mainWarehouseId
        ));
    }

    public function test_a_retail_request_from_a_warehouse_with_no_qc_or_ops_skips_approval(): void
    {
        // Nobody assigned to the origin warehouse at all.
        $this->assertFalse(StockTransferApproval::requiresApproval(
            'retail_request', true, false, $this->mainWarehouseId
        ));

        // ...and with nothing required, it is vacuously "fully approved", so
        // the transfer is immediately shippable.
        $this->assertTrue(StockTransferApproval::isFullyApproved(
            $this->header(), $this->mainWarehouseId
        ));
    }

    public function test_an_inactive_kepala_does_not_make_ops_approval_required(): void
    {
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId, isKepala: true);
        Staff::query()->where('staff_id', $this->kepalaStaffId)->update(['status' => 0]);

        $this->assertFalse(StockTransferApproval::opsRequiredAtWarehouse($this->mainWarehouseId));
        $this->assertFalse(StockTransferApproval::requiresApproval(
            'retail_request', true, false, $this->mainWarehouseId
        ));
    }

    public function test_a_staff_assigned_without_the_kepala_flag_does_not_make_ops_required(): void
    {
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId, isKepala: false);

        $this->assertFalse(StockTransferApproval::opsRequiredAtWarehouse($this->mainWarehouseId));
    }

    // ------------------------------------------------------------- sequencing

    public function test_ops_cannot_approve_before_qc_when_both_are_required(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId, isKepala: true);
        $header = $this->header();

        $this->assertTrue(StockTransferApproval::canApproveQc($header, $this->mainWarehouseId));
        $this->assertFalse(
            StockTransferApproval::canApproveOps($header, $this->mainWarehouseId),
            'Kepala Operasional must not be able to sign off before QC & Gudang has.'
        );
    }

    public function test_ops_can_approve_once_qc_has_signed_off(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId, isKepala: true);

        $header = $this->header(['qc_approved_by' => $this->qcStaffId]);

        $this->assertFalse(
            StockTransferApproval::canApproveQc($header, $this->mainWarehouseId),
            'QC must not be able to approve twice.'
        );
        $this->assertTrue(StockTransferApproval::canApproveOps($header, $this->mainWarehouseId));
        $this->assertFalse(
            StockTransferApproval::isFullyApproved($header, $this->mainWarehouseId),
            'QC alone is not enough while a Kepala Operasional exists at the origin.'
        );
    }

    public function test_the_transfer_is_only_fully_approved_once_both_have_signed(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId, isKepala: true);

        $header = $this->header([
            'qc_approved_by' => $this->qcStaffId,
            'ops_approved_by' => $this->kepalaStaffId,
        ]);

        $this->assertTrue(StockTransferApproval::isFullyApproved($header, $this->mainWarehouseId));
        $this->assertFalse(StockTransferApproval::canApproveQc($header, $this->mainWarehouseId));
        $this->assertFalse(StockTransferApproval::canApproveOps($header, $this->mainWarehouseId));
    }

    public function test_qc_alone_is_enough_when_the_origin_has_no_kepala(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);

        $pending = $this->header();
        $this->assertTrue(StockTransferApproval::requiresApproval(
            'retail_request', true, false, $this->mainWarehouseId
        ));
        $this->assertFalse(StockTransferApproval::isFullyApproved($pending, $this->mainWarehouseId));

        $approved = $this->header(['qc_approved_by' => $this->qcStaffId]);
        $this->assertTrue(StockTransferApproval::isFullyApproved($approved, $this->mainWarehouseId));
    }

    public function test_phase_badge_walks_requested_then_need_approval_then_ready(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId, isKepala: true);

        $this->assertSame('requested', StockTransferApproval::retailRequestPhase(
            $this->header(), $this->mainWarehouseId
        ));
        $this->assertSame('need_approval', StockTransferApproval::retailRequestPhase(
            $this->header(['qc_approved_by' => $this->qcStaffId]), $this->mainWarehouseId
        ));
        $this->assertSame('ready', StockTransferApproval::retailRequestPhase(
            $this->header([
                'qc_approved_by' => $this->qcStaffId,
                'ops_approved_by' => $this->kepalaStaffId,
            ]),
            $this->mainWarehouseId
        ));
    }

    // ------------------------------------------------------------------ roles

    public function test_qc_role_requires_both_the_role_id_and_an_assignment_to_that_warehouse(): void
    {
        $qc = $this->user($this->qcStaffId);

        // Correct role, not assigned anywhere yet.
        $this->assertFalse(StockTransferApproval::isQcAssignedToWarehouse($qc, $this->mainWarehouseId));

        // Assigned to a DIFFERENT warehouse than the transfer's origin.
        $this->assign($this->qcStaffId, $this->retailWarehouseId);
        $this->assertFalse(StockTransferApproval::isQcAssignedToWarehouse(
            $this->user($this->qcStaffId), $this->mainWarehouseId
        ));

        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $this->assertTrue(StockTransferApproval::isQcAssignedToWarehouse(
            $this->user($this->qcStaffId), $this->mainWarehouseId
        ));
    }

    /**
     * RoleIds::QC_GUDANG is a fixed id on purpose (renaming the role must not
     * break the filter). A staff assigned to the warehouse but holding any
     * other role is not QC.
     */
    public function test_a_non_qc_role_assigned_to_the_warehouse_is_not_treated_as_qc(): void
    {
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId);

        $this->assertFalse(StockTransferApproval::isQcAssignedToWarehouse(
            $this->user($this->kepalaStaffId), $this->mainWarehouseId
        ));
    }

    /**
     * One person holding both hats acts as QC first and only becomes Ops after
     * the QC signature exists — the sequence is preserved even when there is
     * nobody else to enforce it.
     */
    public function test_a_dual_role_kepala_who_is_also_qc_acts_as_qc_first_then_ops(): void
    {
        $dualId = $this->makeStaff('Kepala merangkap QC ', RoleIds::QC_GUDANG);
        $this->assign($dualId, $this->mainWarehouseId, isKepala: true);
        $dual = $this->user($dualId);

        $this->assertSame('qc', StockTransferApproval::resolveActorRole(
            $dual, $this->mainWarehouseId, $this->header()
        ));

        $this->assertSame('ops', StockTransferApproval::resolveActorRole(
            $dual, $this->mainWarehouseId, $this->header(['qc_approved_by' => $dualId])
        ));

        $this->assertNull(StockTransferApproval::resolveActorRole(
            $dual,
            $this->mainWarehouseId,
            $this->header(['qc_approved_by' => $dualId, 'ops_approved_by' => $dualId])
        ));
    }

    // --------------------------------------------------------------- rejection

    public function test_qc_may_reject_only_before_its_own_approval(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $qc = $this->user($this->qcStaffId);

        $this->assertTrue(StockTransferApproval::canRejectAtOrigin(
            $qc, $this->header(), $this->mainWarehouseId
        ));
        $this->assertFalse(StockTransferApproval::canRejectAtOrigin(
            $qc, $this->header(['qc_approved_by' => $this->qcStaffId]), $this->mainWarehouseId
        ));
    }

    public function test_ops_may_reject_only_after_qc_has_approved(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $this->assign($this->kepalaStaffId, $this->mainWarehouseId, isKepala: true);
        $kepala = $this->user($this->kepalaStaffId);

        $this->assertFalse(
            StockTransferApproval::canRejectAtOrigin($kepala, $this->header(), $this->mainWarehouseId),
            'Ops must not short-circuit the chain by rejecting before QC has looked at it.'
        );
        $this->assertTrue(StockTransferApproval::canRejectAtOrigin(
            $kepala, $this->header(['qc_approved_by' => $this->qcStaffId]), $this->mainWarehouseId
        ));
        $this->assertFalse(StockTransferApproval::canRejectAtOrigin(
            $kepala,
            $this->header(['qc_approved_by' => $this->qcStaffId, 'ops_approved_by' => $this->kepalaStaffId]),
            $this->mainWarehouseId
        ));
    }

    public function test_an_unrelated_staff_can_never_reject_at_the_origin(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $outsider = $this->user($this->requesterStaffId);

        $this->assertFalse(StockTransferApproval::canRejectAtOrigin(
            $outsider, $this->header(), $this->mainWarehouseId
        ));
    }

    // ------------------------------------------------------------ cancellation

    public function test_the_requester_may_cancel_only_while_no_approval_exists(): void
    {
        $this->assign($this->qcStaffId, $this->mainWarehouseId);
        $requester = $this->user($this->requesterStaffId);

        $this->assertTrue(StockTransferApproval::canCancelRetailRequestAtDestination(
            $requester, $this->header(), $this->retailWarehouseId, $this->mainWarehouseId
        ));

        $this->assertFalse(
            StockTransferApproval::canCancelRetailRequestAtDestination(
                $requester,
                $this->header(['qc_approved_by' => $this->qcStaffId]),
                $this->retailWarehouseId,
                $this->mainWarehouseId
            ),
            'Once QC has signed off the origin is already acting on it; the retail side loses the cancel.'
        );
    }

    public function test_a_staff_who_is_not_the_requester_cannot_cancel(): void
    {
        $someoneElse = $this->user($this->kepalaStaffId);

        $this->assertFalse(StockTransferApproval::canCancelRetailRequestAtDestination(
            $someoneElse, $this->header(), $this->retailWarehouseId, $this->mainWarehouseId
        ));
    }
}
