<?php

namespace App\Support;

use App\Models\SalesOrder;
use App\Models\Staff;

/**
 * Approval berurut Pengiriman (sales_orders) status 1 "Pending": Staf QC & Gudang lalu Kepala
 * Operasional, keduanya terkait gudang utama — lihat
 * cdocs/docs/specs/shipment-external-api-approval-flow.md.
 *
 * "Wajib gudang aktif sesi = gudang utama" HANYA berlaku untuk tahap Ops (DIPUTUSKAN
 * 2026-09-23, koreksi dari keputusan awal yang mewajibkan kedua tahap) — lihat
 * isAtWarehouseForApproval() dan pemanggilnya di CustomerController::approveShipment()/
 * rejectShipment()/App\Models\SalesOrder::getSalesOrderDataTable(). Tahap QC tidak pernah
 * dicek terhadap gudang aktif sesi di sini; siapa yang berhak jadi QC tetap ditentukan lewat
 * penugasan staff_warehouses ke gudang utama (resolveActorRole() di bawah).
 *
 * Beda dengan App\Support\StockTransferApproval (yang dipakai sebagai referensi pola): tidak ada
 * routing retail_request/main_request di sini, gudang approval SELALU gudang utama
 * (App\Models\ProductStock::resolveWarehouseId(null)) - shipment (baik dari External API maupun
 * insert manual lama) selalu memakai gudang itu untuk sales_order_details.warehouse_id. Jadi
 * qcRequiredAtWarehouse()/opsRequiredAtWarehouse() di sini SELALU true (kedua tahap wajib di
 * gudang utama, sama seperti kaidah StockTransferApproval::qcRequiredAtWarehouse()/
 * opsRequiredAtWarehouse() untuk gudang utama).
 *
 * Direksi / Developer boleh menggantikan Staf QC & Gudang ATAU Kepala Operasional (berurut, tidak
 * boleh melompati tahap) - sama seperti Stock Transfer.
 */
class ShipmentApproval
{
    public static function isQcApproved(SalesOrder $so): bool
    {
        return (int) ($so->qc_approved_by ?? 0) > 0;
    }

    public static function isOpsApproved(SalesOrder $so): bool
    {
        return (int) ($so->ops_approved_by ?? 0) > 0;
    }

    public static function isRejected(SalesOrder $so): bool
    {
        return (int) ($so->rejected_by ?? 0) > 0;
    }

    public static function isFullyApproved(SalesOrder $so): bool
    {
        return self::isQcApproved($so) && self::isOpsApproved($so);
    }

    public static function canApproveQc(SalesOrder $so): bool
    {
        return ! self::isQcApproved($so) && ! self::isRejected($so);
    }

    public static function canApproveOps(SalesOrder $so): bool
    {
        return self::isQcApproved($so) && ! self::isOpsApproved($so) && ! self::isRejected($so);
    }

    /**
     * Boleh ditulis ulang dari sumber eksternal (PMO) lewat POST /shipments/shipped ATAU
     * Sinkronisasi Pengiriman (App\Synchronization\Steps\ShipmentFlow\SyncShipmentsStep) — masih
     * status 1 "Pending" DAN belum ada satu pun approval/reject tercatat. Begitu proses approval
     * mulai berjalan (sebagian atau seluruhnya), atau status sudah maju/Ditolak, kedua sumber
     * eksternal itu TIDAK BOLEH lagi menimpa baris ini — dipakai bersama supaya aturannya sama
     * persis di kedua channel, tidak ditulis ulang berbeda di masing-masing.
     */
    public static function isEditableFromExternalSource(SalesOrder $so): bool
    {
        return (int) $so->status === 1
            && ! self::isQcApproved($so)
            && ! self::isOpsApproved($so)
            && ! self::isRejected($so);
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

    /**
     * Gudang aktif user = gudang utama tempat approval berlaku — hanya dipakai untuk tahap Ops,
     * lihat catatan "DIPUTUSKAN 2026-09-23" di docblock kelas ini. JANGAN dipanggil untuk tahap QC.
     */
    public static function isAtWarehouseForApproval($user, int $warehouseId, int $activeWarehouseId): bool
    {
        if ($warehouseId <= 0 || $activeWarehouseId <= 0 || $activeWarehouseId !== $warehouseId) {
            return false;
        }
        if (self::isElevatedApprover($user)) {
            return true;
        }

        $assignedWh = Staff::assignedWarehouseIds($user);

        return $assignedWh === [] || in_array($warehouseId, $assignedWh, true);
    }

    /**
     * Tahap yang boleh dijalankan $user untuk shipment ini di gudang $warehouseId.
     *
     * @return 'qc'|'ops'|null
     */
    public static function resolveActorRole($user, int $warehouseId, SalesOrder $so): ?string
    {
        if (! $user || $warehouseId <= 0) {
            return null;
        }

        $staffId = (int) ($user->staff_id ?? 0);
        if ($staffId <= 0) {
            return null;
        }

        if (self::isElevatedApprover($user)) {
            if (self::canApproveQc($so)) {
                return 'qc';
            }
            if (self::canApproveOps($so)) {
                return 'ops';
            }

            return null;
        }

        $isKepala = StockTransferApproval::isKepalaOfWarehouse($staffId, $warehouseId);
        $isQc = StockTransferApproval::isQcAssignedToWarehouse($user, $warehouseId);

        // Dual role (QC & Kepala di gudang yang sama): QC dulu, baru Ops.
        if ($isKepala && $isQc) {
            if (self::canApproveQc($so)) {
                return 'qc';
            }
            if (self::canApproveOps($so)) {
                return 'ops';
            }

            return null;
        }
        if ($isKepala) {
            return self::canApproveOps($so) ? 'ops' : null;
        }
        if ($isQc) {
            return self::canApproveQc($so) ? 'qc' : null;
        }

        return null;
    }
}
