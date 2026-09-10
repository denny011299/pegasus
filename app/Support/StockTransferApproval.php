<?php

namespace App\Support;

use App\Models\Staff;
use App\Models\StaffWarehouse;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Schema;

/**
 * Approval berurut Stock Transfer request antar gudang utama ↔ eceran.
 *
 * retail_request (eceran minta dari utama): FROM utama → TO eceran
 *   1. QC lalu Kepala Ops di gudang asal (utama), status pending
 *   2. Setelah lengkap → auto Kirim; eceran hanya Terima
 *
 * main_request (utama minta dari eceran): FROM eceran → TO utama
 *   1. Eceran Acc Kirim (tanpa QC/Ops)
 *   2. QC lalu Kepala Ops di gudang tujuan (utama), status Kirim
 *   3. Setelah lengkap → auto Terima (stok masuk utama)
 *
 * Di gudang utama kedua tahap selalu wajib. Direksi / Developer boleh
 * menggantikan orang QC atau Kepala Ops (berurut — tidak skip tahap).
 * Transfer lain (produksi, biasa): tanpa QC/Ops.
 */
class StockTransferApproval
{
    public static function isRetailRequestRoute(
        ?string $sourceType,
        ?bool $fromIsMain,
        ?bool $toIsMain
    ): bool {
        if ($sourceType !== 'retail_request') {
            return false;
        }

        return $fromIsMain === true && $toIsMain === false;
    }

    public static function isMainRequestRoute(
        ?string $sourceType,
        ?bool $fromIsMain,
        ?bool $toIsMain
    ): bool {
        if ($sourceType !== 'main_request') {
            return false;
        }

        return $fromIsMain === false && $toIsMain === true;
    }

    /** Gudang tempat QC/Ops berlaku untuk route ini (asal untuk retail, tujuan untuk main). */
    public static function approvalWarehouseId(
        ?string $sourceType,
        ?bool $fromIsMain,
        ?bool $toIsMain,
        int $fromWarehouseId,
        int $toWarehouseId
    ): int {
        if (self::isMainRequestRoute($sourceType, $fromIsMain, $toIsMain)) {
            return $toWarehouseId;
        }
        if (self::isRetailRequestRoute($sourceType, $fromIsMain, $toIsMain)) {
            return $fromWarehouseId;
        }

        return 0;
    }

    /**
     * Perlu langkah approval QC/Ops (di gudang approvalWarehouseId).
     */
    public static function requiresApproval(
        ?string $sourceType,
        ?bool $fromIsMain,
        ?bool $toIsMain,
        int $fromWarehouseId = 0,
        int $toWarehouseId = 0
    ): bool {
        $wh = self::approvalWarehouseId($sourceType, $fromIsMain, $toIsMain, $fromWarehouseId, $toWarehouseId);
        if ($wh <= 0) {
            return false;
        }

        return self::qcRequiredAtWarehouse($wh) || self::opsRequiredAtWarehouse($wh);
    }

    /** retail_request: approval sebelum Kirim. */
    public static function requiresShipApproval(
        ?string $sourceType,
        ?bool $fromIsMain,
        ?bool $toIsMain,
        int $fromWarehouseId = 0
    ): bool {
        if (! self::isRetailRequestRoute($sourceType, $fromIsMain, $toIsMain)) {
            return false;
        }

        return self::requiresApproval($sourceType, $fromIsMain, $toIsMain, $fromWarehouseId, 0);
    }

    /** main_request: approval sebelum Terima (stok masuk). */
    public static function requiresReceiveApproval(
        ?string $sourceType,
        ?bool $fromIsMain,
        ?bool $toIsMain,
        int $toWarehouseId = 0
    ): bool {
        if (! self::isMainRequestRoute($sourceType, $fromIsMain, $toIsMain)) {
            return false;
        }

        return self::requiresApproval($sourceType, $fromIsMain, $toIsMain, 0, $toWarehouseId);
    }

    /**
     * Gudang utama: tahap QC selalu wajib (Staf QC atau pengganti Direksi/Developer).
     * Gudang non-utama: hanya jika ada Staf QC assigned.
     */
    public static function qcRequiredAtWarehouse(int $warehouseId): bool
    {
        if ($warehouseId <= 0) {
            return false;
        }
        if (self::warehouseIsMain($warehouseId) === true) {
            return true;
        }

        return Staff::qcGudangForWarehouse($warehouseId) !== [];
    }

    /**
     * Gudang utama: tahap Kepala Ops selalu wajib (Kepala atau pengganti Direksi/Developer).
     * Jangan skip Ops hanya karena belum ada is_kepala_cabang — elevated tetap harus stamp Ops.
     * Gudang non-utama: hanya jika ada Kepala aktif.
     */
    public static function opsRequiredAtWarehouse(int $warehouseId): bool
    {
        if ($warehouseId <= 0) {
            return false;
        }
        if (self::warehouseIsMain($warehouseId) === true) {
            return true;
        }
        if (! Schema::hasTable('staff_warehouses')
            || ! Schema::hasColumn('staff_warehouses', 'is_kepala_cabang')) {
            return false;
        }

        $kepalaIds = StaffWarehouse::query()
            ->where('warehouse_id', $warehouseId)
            ->where('is_kepala_cabang', 1)
            ->pluck('staff_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($kepalaIds === []) {
            return false;
        }

        return Staff::query()
            ->where('status', 1)
            ->whereIn('staff_id', $kepalaIds)
            ->exists();
    }

    /** @return bool|null null = gudang/type tidak ketemu */
    public static function warehouseIsMain(int $warehouseId): ?bool
    {
        if ($warehouseId <= 0) {
            return null;
        }

        $warehouse = Warehouse::query()
            ->with('type:id,is_main_warehouse')
            ->find($warehouseId, ['id', 'warehouse_type_id']);
        if (! $warehouse || ! $warehouse->type) {
            return null;
        }

        return (int) $warehouse->type->is_main_warehouse === 1;
    }

    public static function isFullyApproved($header, int $approvalWarehouseId = 0): bool
    {
        $qcReq = $approvalWarehouseId > 0
            ? self::qcRequiredAtWarehouse($approvalWarehouseId)
            : true;
        $opsReq = $approvalWarehouseId > 0
            ? self::opsRequiredAtWarehouse($approvalWarehouseId)
            : true;

        if (! $qcReq && ! $opsReq) {
            return true;
        }
        if ($qcReq && ! self::isQcApproved($header)) {
            return false;
        }
        if ($opsReq && ! self::isOpsApproved($header)) {
            return false;
        }

        return true;
    }

    public static function canApproveQc($header, int $approvalWarehouseId): bool
    {
        return self::qcRequiredAtWarehouse($approvalWarehouseId)
            && ! self::isQcApproved($header);
    }

    /** Direksi / Developer — boleh menggantikan QC atau Kepala Ops (berurut, tidak skip tahap). */
    public static function isElevatedApprover($user): bool
    {
        if (! $user) {
            return false;
        }

        $roleId = (int) ($user->role_id ?? 0);

        return in_array($roleId, [RoleIds::DIREKSI, RoleIds::DEVELOPER], true);
    }

    public static function isAtOriginForApproval($user, int $fromWarehouseId, int $activeWarehouseId): bool
    {
        return self::isAtWarehouseForApproval($user, $fromWarehouseId, $activeWarehouseId);
    }

    public static function isAtDestinationForApproval($user, int $toWarehouseId, int $activeWarehouseId): bool
    {
        return self::isAtWarehouseForApproval($user, $toWarehouseId, $activeWarehouseId);
    }

    public static function isAtWarehouseForApproval($user, int $warehouseId, int $activeWarehouseId): bool
    {
        if ($activeWarehouseId <= 0 || $activeWarehouseId !== $warehouseId) {
            return false;
        }
        if (self::isElevatedApprover($user)) {
            return true;
        }

        $assignedWh = Staff::assignedWarehouseIds($user);

        return $assignedWh === [] || in_array($warehouseId, $assignedWh, true);
    }

    public static function canApproveOps($header, int $approvalWarehouseId): bool
    {
        if (! self::opsRequiredAtWarehouse($approvalWarehouseId)) {
            return false;
        }
        if (self::isOpsApproved($header)) {
            return false;
        }
        if (self::qcRequiredAtWarehouse($approvalWarehouseId) && ! self::isQcApproved($header)) {
            return false;
        }

        return true;
    }

    /**
     * Tolak di gudang approval (QC / Kepala Ops).
     * QC hanya sebelum QC approve; Ops hanya setelah QC approve (jika QC wajib).
     */
    public static function canRejectAtOrigin($user, $header, int $approvalWarehouseId): bool
    {
        return self::canRejectAtApprovalWarehouse($user, $header, $approvalWarehouseId);
    }

    public static function canRejectAtApprovalWarehouse($user, $header, int $approvalWarehouseId): bool
    {
        $actor = self::resolveActorRole($user, $approvalWarehouseId, $header);
        if ($actor === 'qc') {
            return ! self::isQcApproved($header);
        }
        if ($actor === 'ops') {
            if (self::qcRequiredAtWarehouse($approvalWarehouseId) && ! self::isQcApproved($header)) {
                return false;
            }

            return ! self::isOpsApproved($header);
        }

        return false;
    }

    /**
     * Cancel request dari gudang pemohon (tujuan): hanya sender, sebelum ada approval.
     * Dipakai retail_request (eceran) dan main_request (utama) saat masih pending.
     */
    public static function canCancelRetailRequestAtDestination(
        $user,
        $header,
        int $toWarehouseId,
        int $fromWarehouseId
    ): bool {
        return self::canCancelRequestAtDestination($user, $header, $toWarehouseId, $fromWarehouseId);
    }

    public static function canCancelRequestAtDestination(
        $user,
        $header,
        int $toWarehouseId,
        int $approvalWarehouseIdForOpsCheck = 0
    ): bool {
        if (! $user || $toWarehouseId <= 0) {
            return false;
        }

        $staffId = (int) ($user->staff_id ?? 0);
        if ($staffId <= 0) {
            return false;
        }
        if ((int) ($header->sender_id ?? 0) !== $staffId) {
            return false;
        }
        if (self::isQcApproved($header) || self::isOpsApproved($header)) {
            return false;
        }
        if ($approvalWarehouseIdForOpsCheck > 0
            && self::resolveActorRole($user, $approvalWarehouseIdForOpsCheck, $header) === 'ops') {
            return false;
        }

        return true;
    }

    public static function isQcApproved($header): bool
    {
        return (int) ($header->qc_approved_by ?? 0) > 0;
    }

    public static function isOpsApproved($header): bool
    {
        return (int) ($header->ops_approved_by ?? 0) > 0;
    }

    /**
     * Fase badge: requested → need_approval → ready.
     * Retail: status=1 di gudang asal. Main: status=2 di gudang tujuan.
     *
     * @return 'requested'|'need_approval'|'ready'|null
     */
    public static function retailRequestPhase($header, int $approvalWarehouseId): ?string
    {
        return self::approvalPhase($header, $approvalWarehouseId);
    }

    public static function mainRequestPhase($header, int $approvalWarehouseId): ?string
    {
        return self::approvalPhase($header, $approvalWarehouseId);
    }

    public static function approvalPhase($header, int $approvalWarehouseId): ?string
    {
        if ($approvalWarehouseId <= 0) {
            return null;
        }
        $qcReq = self::qcRequiredAtWarehouse($approvalWarehouseId);
        $opsReq = self::opsRequiredAtWarehouse($approvalWarehouseId);
        if ($qcReq && ! self::isQcApproved($header)) {
            return 'requested';
        }
        if ($opsReq && ! self::isOpsApproved($header)) {
            return 'need_approval';
        }

        return 'ready';
    }

    /**
     * @return 'qc'|'ops'|null
     */
    public static function resolveActorRole($user, int $warehouseId, $header = null): ?string
    {
        if (! $user || $warehouseId <= 0) {
            return null;
        }

        $staffId = (int) ($user->staff_id ?? 0);
        if ($staffId <= 0) {
            return null;
        }

        if (self::isElevatedApprover($user) && $header !== null) {
            if (self::qcRequiredAtWarehouse($warehouseId) && ! self::isQcApproved($header)) {
                return 'qc';
            }
            if (self::opsRequiredAtWarehouse($warehouseId) && ! self::isOpsApproved($header)) {
                return 'ops';
            }

            return null;
        }

        $isKepala = self::isKepalaOfWarehouse($staffId, $warehouseId);
        $isQc = self::isQcAssignedToWarehouse($user, $warehouseId);

        // Dual role: QC dulu, setelah QC approve baru bertindak sebagai Ops.
        if ($isKepala && $isQc) {
            if ($header !== null
                && self::qcRequiredAtWarehouse($warehouseId)
                && ! self::isQcApproved($header)) {
                return 'qc';
            }
            if ($header !== null
                && self::opsRequiredAtWarehouse($warehouseId)
                && ! self::isOpsApproved($header)) {
                return 'ops';
            }

            return null;
        }
        if ($isKepala) {
            return 'ops';
        }
        if ($isQc) {
            return 'qc';
        }

        return null;
    }

    public static function isKepalaOfWarehouse(int $staffId, int $warehouseId): bool
    {
        if ($staffId <= 0 || $warehouseId <= 0) {
            return false;
        }
        if (! Schema::hasTable('staff_warehouses')
            || ! Schema::hasColumn('staff_warehouses', 'is_kepala_cabang')) {
            return false;
        }

        return StaffWarehouse::query()
            ->where('staff_id', $staffId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_kepala_cabang', 1)
            ->exists();
    }

    public static function isQcAssignedToWarehouse($user, int $warehouseId): bool
    {
        if (! $user || $warehouseId <= 0) {
            return false;
        }
        if ((int) ($user->role_id ?? 0) !== RoleIds::QC_GUDANG) {
            return false;
        }

        $assigned = Staff::assignedWarehouseIds($user);
        // Harus assigned ke gudang ini (kosong = bukan QC gudang mana pun)
        return $assigned !== [] && in_array($warehouseId, $assigned, true);
    }
}
