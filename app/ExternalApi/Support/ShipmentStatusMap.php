<?php

namespace App\ExternalApi\Support;

/**
 * Pemetaan status internal sales_orders <-> ipm_status pada API Contract v1
 * ("private docs/Open API/API_Integration_Specification_PMO_IPM_v1.md"), dipakai bersama oleh
 * ShipmentController::scheduled(), ::shipped(), ::changeStatus(), dan ::cancel().
 *
 * Kosakata ipm_status (ditentukan kontrak, DIKONFIRMASI pemilik produk 2026-08-12):
 *   -1 = Dibatalkan, 1 = Dijadwalkan, 2 = Berjalan, 3 = Belum terkirim, 4 = Sudah terkirim
 *
 * Ini SENGAJA bukan sales_orders.status apa adanya - kosakata itu berbeda:
 *   sales_orders.status: 1 = Created, 2 = Confirmed, 3 = Completed/Ditolak, 4 = Dijadwalkan,
 *   5 = Belum Terkirim (API), 6 = Sudah Terkirim (API), 7 = Dibatalkan (API) - lihat migrasi
 *   2026_08_12_110000_* dan 2026_08_12_120000_*.
 *
 * Angka 4 kebetulan dipakai di KEDUA sisi tapi artinya BEDA (internal 4 = "Dijadwalkan lewat
 * API", ipm_status 4 = "Sudah terkirim") - jangan pernah membaca satu sebagai yang lain tanpa
 * lewat fromInternal()/ipmForLabel().
 *
 * -1 "Dibatalkan" SENGAJA tidak masuk LABEL_TO_IPM/validLabels() - itu daftar khusus label yang
 * boleh dikirim PATCH .../change-status (force, tanpa efek samping). Pembatalan (dengan efek
 * samping mengembalikan stok + reason) HANYA lewat PUT .../cancel, lihat ShipmentController::
 * cancel()/App\Support\SalesOrderCancellation.
 */
class ShipmentStatusMap
{
    public const IPM_CANCELLED = -1;
    public const IPM_SCHEDULED = 1;
    public const IPM_RUNNING = 2;
    public const IPM_NOT_DELIVERED = 3;
    public const IPM_DELIVERED = 4;

    private const LABELS = [
        self::IPM_CANCELLED => 'Dibatalkan',
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
     * DIUBAH sejak flow "shipment-approval-flow v2" (2026-09, lihat
     * cdocs/docs/specs/shipment-external-api-approval-flow.md) - status 1 dan 3 dipetakan ULANG,
     * dipisah dari 4 yang sebelumnya dianggap setara:
     *   - 1 ("Pending" - HASIL SATU-SATUNYA insert/update POST /shipments/shipped sekarang,
     *     menunggu approval 2 tahap admin Staf QC & Gudang -> Kepala Operasional sebelum stok
     *     dipotong) -> ipm_status 2 "Berjalan". PMO tidak perlu tahu detail approval QC/Ops di
     *     sisi IPM - dari sudut pandang PMO prosesnya "berjalan" sampai nanti benar-benar
     *     Sudah/Belum terkirim atau Dibatalkan.
     *   - 2 (Confirmed, approval 2 tahap selesai + stok dipotong lewat
     *     SalesOrderApproval::confirm()) -> ipm_status 2 "Berjalan" JUGA - sengaja SAMA dengan 1,
     *     PMO tidak membedakan "menunggu approval" dari "sudah disetujui semua".
     *   - 3 (Ditolak - hasil reject di salah satu tahap approval, ATAU dihapus lewat alur admin
     *     biasa) -> ipm_status -1 "Dibatalkan" (sebelumnya null/tidak ada padanan).
     *   - 4 (Dijadwalkan - LEGACY, PUT /shipments/scheduled yang menghasilkannya sudah
     *     dinonaktifkan, data lama saja) -> TETAP ipm_status 1 "Dijadwalkan", tidak diubah.
     *   - 5, 6 (dipaksa lewat PATCH /shipments/{ref}/change-status, lihat ShipmentController) ->
     *     ipm_status 3 "Belum terkirim" dan 4 "Sudah terkirim" berturut-turut.
     *   - 7 (dibatalkan lewat PUT /shipments/{ref}/cancel) -> ipm_status -1 "Dibatalkan".
     */
    public static function fromInternal(int $internalStatus): ?int
    {
        return match ($internalStatus) {
            1, 2 => self::IPM_RUNNING,
            3, 7 => self::IPM_CANCELLED,
            4 => self::IPM_SCHEDULED,
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
