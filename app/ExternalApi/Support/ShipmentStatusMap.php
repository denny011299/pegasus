<?php

namespace App\ExternalApi\Support;

/**
 * Pemetaan status internal sales_orders <-> ipm_status pada API Contract v1
 * ("private docs/Open API/API_Integration_Specification_PMO_IPM_v1.md"), dipakai bersama oleh
 * ShipmentController::scheduled() dan ::shipped().
 *
 * Kosakata ipm_status (ditentukan kontrak, DIKONFIRMASI pemilik produk 2026-08-12):
 *   1 = Dijadwalkan, 2 = Berjalan, 3 = Belum terkirim, 4 = Sudah terkirim
 *
 * Ini SENGAJA bukan sales_orders.status apa adanya - kosakata itu berbeda:
 *   sales_orders.status: 1 = Created, 2 = Confirmed, 3 = Completed/Ditolak, 4 = Dijadwalkan
 *
 * Angka 4 kebetulan dipakai di KEDUA sisi tapi artinya BEDA (internal 4 = "Dijadwalkan lewat
 * API", ipm_status 4 = "Sudah terkirim") - jangan pernah membaca satu sebagai yang lain tanpa
 * lewat fromInternal().
 */
class ShipmentStatusMap
{
    public const IPM_SCHEDULED = 1;
    public const IPM_RUNNING = 2;
    public const IPM_NOT_DELIVERED = 3;
    public const IPM_DELIVERED = 4;

    private const LABELS = [
        self::IPM_SCHEDULED => 'Dijadwalkan',
        self::IPM_RUNNING => 'Berjalan',
        self::IPM_NOT_DELIVERED => 'Belum terkirim',
        self::IPM_DELIVERED => 'Sudah terkirim',
    ];

    /**
     * sales_orders.status (internal) -> ipm_status (kontrak API).
     *
     * Hanya status yang benar-benar bisa dihasilkan endpoint Shipment yang dipetakan di sini:
     *   - 1 (Created, dibuat manual lewat halaman admin) dan 4 (Dijadwalkan, dibuat lewat
     *     /shipments/scheduled atau /shipments/shipped saat ref_shipment_id belum ada) DIANGGAP
     *     SETARA dari sudut pandang ipm_status - keduanya "belum di-ACC" -> ipm_status 1.
     *   - 2 (Confirmed, sudah di-ACC + stok dipotong lewat SalesOrderApproval::confirm()) ->
     *     ipm_status 2 "Berjalan".
     *   - 3 (Ditolak/dihapus) BELUM ada padanan di kosakata ipm_status ini (kontraknya tidak
     *     punya status "dibatalkan" - lihat /shipments/cancel yang belum dibangun) -> null.
     *   - ipm_status 3 "Belum terkirim" dan 4 "Sudah terkirim" belum bisa dihasilkan endpoint
     *     manapun yang sudah dibangun - menyusul di /shipments/status.
     */
    public static function fromInternal(int $internalStatus): ?int
    {
        return match ($internalStatus) {
            1, 4 => self::IPM_SCHEDULED,
            2 => self::IPM_RUNNING,
            default => null,
        };
    }

    public static function label(int $ipmStatus): string
    {
        return self::LABELS[$ipmStatus] ?? 'Tidak diketahui';
    }
}
