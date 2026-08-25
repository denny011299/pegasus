<?php

namespace App\Synchronization\Contracts;

use App\Synchronization\Pmo\PmoException;

/**
 * Kontrak tambahan untuk langkah yang bentuknya "antrean konfirmasi manual",
 * bukan "jalankan sekali, lihat hasil" seperti langkah biasa (tandai lewat
 * SyncStep::$reviewQueue = true). Wizard menampilkan daftar barisnya secara
 * langsung di pane langkah ini (bukan halaman terpisah) dan operator
 * menyelesaikan tiap baris satu per satu lewat resolveReview().
 *
 * handle() (dari SyncStepHandler) tetap wajib ada dan dipanggil lewat tombol
 * "Jalankan Sinkronisasi" seperti biasa — untuk langkah semacam ini artinya
 * cuma "hitung ulang & catat berapa baris yang masih menunggu", bukan
 * memproses apa pun (tidak ada PMO yang dipanggil di sini). Tidak wajib
 * sampai nol baris tersisa — dibiarkan menggantung adalah keadaan akhir yang
 * sah, bukan kegagalan.
 */
interface ReviewQueueStepHandler extends SyncStepHandler
{
    /**
     * Daftar baris yang masih menunggu konfirmasi, siap ditampilkan apa
     * adanya di wizard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingReviews(): array;

    /**
     * Selesaikan satu baris antrean.
     *
     * @return array<string, mixed> Daftar terbaru (sama bentuknya dengan pendingReviews()) untuk digambar ulang.
     *
     * @throws \InvalidArgumentException  aksi tidak dikenal, baris tidak ditemukan/sudah selesai, atau customer_id tidak valid.
     * @throws PmoException
     */
    public function resolveReview(int $reviewId, string $action, ?int $customerId): array;
}
