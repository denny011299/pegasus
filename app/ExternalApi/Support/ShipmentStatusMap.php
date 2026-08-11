<?php

namespace App\ExternalApi\Support;

/**
 * Pemetaan status internal sales_orders <-> ipm_status pada API Contract v1
 * ("private docs/Open API/API_Integration_Specification_PMO_IPM_v1.md"), dipakai bersama oleh
 * ShipmentController::scheduled(), ::shipped(), dan ::changeStatus().
 *
 * Kosakata ipm_status (ditentukan kontrak, DIKONFIRMASI pemilik produk 2026-08-12):
 *   1 = Dijadwalkan, 2 = Berjalan, 3 = Belum terkirim, 4 = Sudah terkirim
 *
 * Ini SENGAJA bukan sales_orders.status apa adanya - kosakata itu berbeda:
 *   sales_orders.status: 1 = Created, 2 = Confirmed, 3 = Completed/Ditolak, 4 = Dijadwalkan,
 *   5 = Belum Terkirim (API), 6 = Sudah Terkirim (API) - lihat migrasi
 *   2026_08_12_110000_add_not_delivered_and_delivered_statuses_to_sales_orders.
 *
 * Angka 4 kebetulan dipakai di KEDUA sisi tapi artinya BEDA (internal 4 = "Dijadwalkan lewat
 * API", ipm_status 4 = "Sudah terkirim") - jangan pernah membaca satu sebagai yang lain tanpa
 * lewat fromInternal()/ipmForLabel().
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

    /** Label (body PATCH .../change-status) -> ipm_status. Kebalikan dari LABELS di atas. */
    private const LABEL_TO_IPM = [
        'Dijadwalkan' => self::IPM_SCHEDULED,
        'Berjalan' => self::IPM_RUNNING,
        'Belum terkirim' => self::IPM_NOT_DELIVERED,
        'Sudah terkirim' => self::IPM_DELIVERED,
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
     *   - 5, 6 (dipaksa lewat PATCH /shipments/{ref}/change-status, lihat ShipmentController) ->
     *     ipm_status 3 "Belum terkirim" dan 4 "Sudah terkirim" berturut-turut.
     *   - 3 (Ditolak/dihapus) BELUM ada padanan di kosakata ipm_status ini (kontraknya tidak
     *     punya status "dibatalkan" - lihat /shipments/cancel yang belum dibangun) -> null.
     */
    public static function fromInternal(int $internalStatus): ?int
    {
        return match ($internalStatus) {
            1, 4 => self::IPM_SCHEDULED,
            2 => self::IPM_RUNNING,
            5 => self::IPM_NOT_DELIVERED,
            6 => self::IPM_DELIVERED,
            default => null,
        };
    }

    public static function label(int $ipmStatus): string
    {
        return self::LABELS[$ipmStatus] ?? 'Tidak diketahui';
    }

    /**
     * Label (body PATCH .../change-status) -> ipm_status, null kalau bukan salah satu dari 4
     * label yang disepakati kontrak - pemanggil menerjemahkannya jadi galat INVALID_STATUS.
     */
    public static function ipmForLabel(string $label): ?int
    {
        return self::LABEL_TO_IPM[$label] ?? null;
    }

    /** @return array<int, string> keempat label yang sah, urutan sesuai kontrak. */
    public static function validLabels(): array
    {
        return array_keys(self::LABEL_TO_IPM);
    }
}
