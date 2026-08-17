<?php

namespace App\ExternalApi\Errors;

/**
 * Katalog kode error External API — SATU-SATUNYA tempat kode error didefinisikan, dipakai
 * lapisan platform (autentikasi, ExceptionRenderer) maupun setiap controller endpoint.
 *
 * Seluruh kode UPPER_SNAKE_CASE (dikonfirmasi pemilik produk 2026-08-12 — migrasi PENUH dari
 * kode lowercase_snake yang dipakai modul-modul sebelumnya: units/armada/sales/produk/stok/
 * payments/shipment semuanya disesuaikan, TERMASUK yang sudah dipublikasikan sebelum keputusan
 * ini, bukan cuma kode baru mulai sekarang).
 *
 * Daftar ini TERUS BERTAMBAH seiring endpoint baru dibangun — tambahkan konstanta baru di sini,
 * jangan menulis string literal kode error langsung di controller mana pun.
 *
 * `message()` cuma disediakan untuk kode yang pesannya benar-benar template tetap dengan
 * placeholder (`<nama>`) — kode lain (mis. DUPLICATE_REF_ID) sengaja TIDAK didaftarkan di
 * TEMPLATES karena pesannya dibangun bebas per controller (menyebut kolom/nilai yang beda-beda
 * per endpoint), bukan satu kalimat tetap.
 */
final class ErrorCatalog
{
    /* ---------------------------------------------------------------- */
    /* Platform — autentikasi, validasi, dan kegagalan umum              */
    /* ---------------------------------------------------------------- */

    public const UNAUTHENTICATED = 'UNAUTHENTICATED';

    public const INVALID_API_KEY = 'INVALID_API_KEY';

    public const API_KEY_REVOKED = 'API_KEY_REVOKED';

    public const API_KEY_EXPIRED = 'API_KEY_EXPIRED';

    public const APPLICATION_DISABLED = 'APPLICATION_DISABLED';

    /** Saklar "Endpoint Aktif" endpoint ini di halaman Status API Eksternal dimatikan administrator. */
    public const API_ENDPOINT_DISABLED = 'API_ENDPOINT_DISABLED';

    public const NOT_FOUND = 'NOT_FOUND';

    public const VALIDATION_FAILED = 'VALIDATION_FAILED';

    public const SERVER_ERROR = 'SERVER_ERROR';

    /** Fallback ExceptionRenderer untuk status HTTP 4xx yang tidak punya kode khusus. */
    public const REQUEST_FAILED = 'REQUEST_FAILED';

    /* ---------------------------------------------------------------- */
    /* Bisnis — dipakai lintas modul (units/armada/sales/produk/shipment) */
    /* ---------------------------------------------------------------- */

    /** POST create dengan kolom rujukan eksternal yang sudah dipakai baris lain. */
    public const DUPLICATE_REF_ID = 'DUPLICATE_REF_ID';

    /* ---------------------------------------------------------------- */
    /* Data Master — gudang                                              */
    /* ---------------------------------------------------------------- */

    public const DUPLICATE_NAME = 'DUPLICATE_NAME';

    public const WAREHOUSE_HAS_STOCK = 'WAREHOUSE_HAS_STOCK';

    /* ---------------------------------------------------------------- */
    /* Pembayaran                                                         */
    /* ---------------------------------------------------------------- */

    public const PAYMENT_NOT_ACCEPTABLE = 'PAYMENT_NOT_ACCEPTABLE';

    /* ---------------------------------------------------------------- */
    /* Shipment                                                           */
    /* ---------------------------------------------------------------- */

    public const INSUFFICIENT_STOCK = 'INSUFFICIENT_STOCK';

    public const SHIPMENT_DETAIL_MISMATCH = 'SHIPMENT_DETAIL_MISMATCH';

    /** GET /shipments/{ref_shipment_id} — ref_shipment_id tidak ditemukan. */
    public const SHIPMENT_NOT_FOUND = 'SHIPMENT_NOT_FOUND';

    /** PATCH /shipments/{ref_shipment_id}/change-status — label status yang dikirim bukan salah satu dari 4 label yang disepakati kontrak. */
    public const INVALID_STATUS = 'INVALID_STATUS';

    /** PATCH /shipments/{ref_shipment_id}/change-status — label valid, tapi transisi dari status shipment saat ini ke label itu belum diizinkan. */
    public const INVALID_STATUS_TRANSITION = 'INVALID_STATUS_TRANSITION';

    /**
     * Pesan baku bertemplate, placeholder `<nama>` diganti lewat $params.
     *
     * @var array<string, string>
     */
    private const TEMPLATES = [
        self::SHIPMENT_NOT_FOUND => 'Pengiriman dengan referensi <ref_shipment_id> tidak ditemukan.',
        self::INVALID_STATUS => 'Field status tidak valid. Hanya menerima [<valid_statuses>]',
        self::INVALID_STATUS_TRANSITION => 'Perubahan status dari "<current_status>" ke "<target_status>" belum diizinkan. Transisi yang didukung saat ini: <allowed_transitions>.',
    ];

    /**
     * @param  array<string, string>  $params
     */
    public static function message(string $code, array $params = []): string
    {
        $template = self::TEMPLATES[$code] ?? $code;

        foreach ($params as $key => $value) {
            $template = str_replace('<'.$key.'>', $value, $template);
        }

        return $template;
    }
}
