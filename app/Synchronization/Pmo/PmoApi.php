<?php

namespace App\Synchronization\Pmo;

/**
 * Titik masuk PMO yang ramah dibaca manusia — satu method per endpoint,
 * dipanggil langsung secara statis, tanpa perlu tahu apa itu PmoClient atau
 * dependency injection:
 *
 *   PmoApi::getProducts();
 *   PmoApi::getProduct(4328012026102327);
 *
 * Pola penamaan untuk tiap endpoint (kontraknya dikonfirmasi PMO
 * 2026-08-20 — lihat cdocs/integrations/202607260130-product-sync-flow.design.md
 * §8.3 dan §10):
 * - **Method jamak** (mis. getProducts(), getArmada()) = "many get": menarik
 *   SEMUA halaman sekaligus (PmoClient sendiri yang mengikuti
 *   pagination.total_pages), mengembalikan PmoResponse (->rows, ->meta,
 *   ->pages, ->url).
 * - **Method tunggal** (mis. getProduct()) = "single get": mengirim filter
 *   yang membuat PMO membalas maksimal satu baris (responsnya tidak
 *   membawa "pagination" sama sekali), mengembalikan baris itu langsung
 *   sebagai array, atau null kalau tidak ditemukan.
 *
 * Kelas ini TIDAK dipakai oleh alur sinkronisasi (ProductSyncFlow dkk.) —
 * langkah-langkah di sana lewat PmoSnapshotStore supaya satu endpoint hanya
 * ditarik sekali per sesi lalu dipakai bersama seluruh langkah (§6.1 pada
 * dokumen desain di atas). Kelas ini untuk pemanggilan langsung/ad-hoc:
 * tinker, controller lain, atau alur baru yang belum butuh potret bersama.
 *
 * Menambah endpoint baru: daftarkan path-nya di config/synchronization.php
 * ("endpoints"), lalu tambah satu method di sini mengikuti pola yang sama
 * persis seperti method-method di bawah.
 */
class PmoApi
{
    /**
     * Ambil SELURUH produk dari PMO (many-get, /getProducts), mengikuti
     * semua halaman sampai habis. $query bisa membawa filter tambahan bila
     * PMO menambahkannya nanti — jangan taruh "page" di sini, PmoClient
     * yang mengatur paginasinya sendiri.
     *
     * @param  array<string, mixed>  $query
     *
     * @throws PmoException
     */
    public static function getProducts(array $query = []): PmoResponse
    {
        return static::client()->fetchCollection(PmoEndpoints::resolve('products'), $query);
    }

    /**
     * Ambil SATU produk dari PMO lewat filter product_id (single-get,
     * /getProducts?product_id=...). Untuk kasus ini PMO membalas maksimal
     * 1 baris dan responsnya tidak membawa "pagination" sama sekali.
     *
     * @throws PmoException
     */
    public static function getProduct(int|string $productId): ?array
    {
        $response = static::client()->fetchCollection(
            PmoEndpoints::resolve('products'),
            ['product_id' => $productId]
        );

        return $response->rows[0] ?? null;
    }

    /**
     * Ambil SELURUH pengiriman dari PMO (many-get, /getShipments) dalam
     * satu rentang tanggal. PMO mewajibkan rentang ini (dikonfirmasi
     * 2026-08-20) — tidak ada mode "semua data tanpa filter" seperti
     * /getProducts, jadi $dateStart/$dateEnd wajib diisi eksplisit oleh
     * pemanggil.
     *
     * Belum ada SyncFlow yang memakai endpoint ini — bentuk baris & pemetaan
     * ke tabel Pegasus-nya belum didesain (§10 pada dokumen desain di atas).
     * Belum dikonfirmasi juga apakah PMO punya mode single-get untuk
     * pengiriman (nama parameter id-nya belum ada); kalau nanti
     * dikonfirmasi, tambah getShipment() di sini mengikuti pola
     * getProduct() di atas.
     *
     * @throws PmoException
     */
    public static function getShipments(string $dateStart, string $dateEnd): PmoResponse
    {
        return static::client()->fetchCollection(PmoEndpoints::resolve('shipments'), [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
        ]);
    }

    /**
     * Ambil SELURUH data armada dari PMO (many-get, /getArmada). Tidak ada
     * parameter selain "page" (yang sudah diatur otomatis oleh PmoClient) —
     * dikonfirmasi 2026-08-20.
     *
     * Sama seperti getShipments(): belum ada SyncFlow yang memakainya, dan
     * belum dikonfirmasi apakah ada mode single-get untuk armada.
     *
     * @throws PmoException
     */
    public static function getArmada(): PmoResponse
    {
        return static::client()->fetchCollection(PmoEndpoints::resolve('armada'));
    }

    private static function client(): PmoClient
    {
        return app(PmoClient::class);
    }
}
