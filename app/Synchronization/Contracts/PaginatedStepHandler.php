<?php

namespace App\Synchronization\Contracts;

use App\Synchronization\Pmo\PmoException;
use App\Synchronization\SyncStepResult;

/**
 * Kontrak tambahan untuk langkah yang menarik datanya dari PMO halaman demi
 * halaman (tandai lewat SyncStep::$paginated = true), supaya progresnya
 * bisa ditampilkan langsung di wizard alih-alih menunggu seluruh halaman
 * selesai dalam satu permintaan.
 *
 * handle() (dari SyncStepHandler) tetap wajib berfungsi penuh sebagai jalur
 * sekali-jalan — dipakai mis. oleh test atau tinker. fetchPage()+finalize()
 * cuma jalur lain menuju hasil yang sama, dipakai wizard untuk menampilkan
 * progres per halaman.
 */
interface PaginatedStepHandler extends SyncStepHandler
{
    /**
     * Ambil TEPAT SATU halaman dari PMO dan tambahkan ke buffer sesi milik
     * langkah ini. $page=1 selalu memulai buffer baru (menimpa sisa
     * percobaan sebelumnya yang belum selesai).
     *
     * $query datang dari SyncStep::$queryFields yang diisi operator di wizard
     * (lihat Wizard.js:collectQueryFields()) — dikirim ULANG pada SETIAP
     * panggilan halaman, bukan hanya $page=1, karena tiap halaman adalah
     * request PMO baru. Langkah yang tidak mendeklarasikan queryFields
     * menerima array kosong dan boleh mengabaikannya sepenuhnya.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed> Info progres: page, total_pages, rows_so_far, is_last_page.
     *
     * @throws PmoException
     */
    public function fetchPage(int $page, array $query = []): array;

    /**
     * Pindahkan buffer sesi ke potret sungguhan (bisa dibaca langkah lain)
     * lalu jalankan validasi yang sama seperti handle(), dipanggil setelah
     * halaman terakhir selesai diambil lewat fetchPage().
     *
     * @throws PmoException
     */
    public function finalize(): SyncStepResult;
}
