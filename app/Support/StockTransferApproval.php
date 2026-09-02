<?php

namespace App\Support;

use App\Models\Staff;
use App\Models\StaffWarehouse;
use Illuminate\Support\Facades\Schema;

/**
 * Approval berurut hanya untuk request eceran (source_type=retail_request):
 * gudang eceran minta stok dari gudang utama (FROM utama → TO eceran).
 *
 * 1. Staf QC & Gudang (jika ada di gudang asal)
 * 2. Kepala Operasional (jika ada di gudang asal)
 * 3. Setelah approval lengkap → otomatis Kirim (potong stok); eceran hanya Terima
 *
 * Transfer lain (utama buat sendiri, eceran↔eceran, produksi): tanpa QC/Ops —
 * Acc/Tolak Kirim & Acc/Tolak Terima seperti alur lama.
 * Terima: tanpa edit qty (qty diterima = qty kirim).
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

    /**
     * Perlu langkah approval sebelum Kirim (ada QC dan/atau Kepala Ops di gudang asal).
     */
    public static function requiresApproval(
        ?string $sourceType,
        ?bool $fromIsMain,
        ?bool $toIsMain,
        int $fromWarehouseId = 0
    ): bool {
        if (! self::isRetailRequestRoute($sourceType, $fromIsMain, $toIsMain)) {
            return false;
        }
        if ($fromWarehouseId <= 0) {
            return true;
        }

        return self::qcRequiredAtWarehouse($fromWarehouseId)
            || self::opsRequiredAtWarehouse($fromWarehouseId);
    }

    public static function qcRequiredAtWarehouse(int $warehouseId): bool
    {
        return $warehouseId > 0 && Staff::qcGudangForWarehouse($warehouseId) !== [];
    }

    public static function opsRequiredAtWarehouse(int $warehouseId): bool
    {
        if ($warehouseId <= 0) {
            return false;
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

    public static function isFullyApproved($header, int $fromWarehouseId = 0): bool
    {
        $qcReq = $fromWarehouseId > 0
            ? self::qcRequiredAtWarehouse($fromWarehouseId)
            : true;
        $opsReq = $fromWarehouseId > 0
            ? self::opsRequiredAtWarehouse($fromWarehouseId)
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

    public static function canApproveQc($header, int $fromWarehouseId): bool
    {
        return self::qcRequiredAtWarehouse($fromWarehouseId)
            && ! self::isQcApproved($header);
    }

    /** Direksi / Okejob (Developer) — bypass QC & Kepala Ops gudang asal. */
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
        if ($activeWarehouseId <= 0 || $activeWarehouseId !== $fromWarehouseId) {
            return false;
        }
        if (self::isElevatedApprover($user)) {
            return true;
        }

        $assignedWh = Staff::assignedWarehouseIds($user);

        return $assignedWh === [] || in_array($fromWarehouseId, $assignedWh, true);
    }

    public static function canApproveOps($header, int $fromWarehouseId): bool
    {
        if (! self::opsRequiredAtWarehouse($fromWarehouseId)) {
            return false;
        }
        if (self::isOpsApproved($header)) {
            return false;
        }
        if (self::qcRequiredAtWarehouse($fromWarehouseId) && ! self::isQcApproved($header)) {
            return false;
        }

        return true;
    }

    /**
     * Tolak request di gudang asal (besar): hanya QC / Kepala Ops.
     * QC hanya sebelum QC approve; Ops hanya setelah QC approve (jika QC wajib).
     */
    public static function canRejectAtOrigin($user, $header, int $fromWarehouseId): bool
    {
        $actor = self::resolveActorRole($user, $fromWarehouseId, $header);
        if ($actor === 'qc') {
            return ! self::isQcApproved($header);
        }
        if ($actor === 'ops') {
            if (self::qcRequiredAtWarehouse($fromWarehouseId) && ! self::isQcApproved($header)) {
                return false;
            }

            return ! self::isOpsApproved($header);
        }

        return false;
    }

    /**
     * Cancel request dari gudang eceran: hanya pemohon (sender), sebelum ada approval.
     * Setelah QC/Ops approve → tidak bisa cancel dari eceran.
     */
    public static function canCancelRetailRequestAtDestination(
        $user,
        $header,
        int $toWarehouseId,
        int $fromWarehouseId
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
        if ($fromWarehouseId > 0 && self::resolveActorRole($user, $fromWarehouseId, $header) === 'ops') {
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
     * Fase badge list (gudang asal / besar) untuk retail_request status=1.
     *
     * @return 'requested'|'need_approval'|'ready'|null
     */
    public static function retailRequestPhase($header, int $fromWarehouseId): ?string
    {
        if ($fromWarehouseId <= 0) {
            return null;
        }
        $qcReq = self::qcRequiredAtWarehouse($fromWarehouseId);
        $opsReq = self::opsRequiredAtWarehouse($fromWarehouseId);
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
