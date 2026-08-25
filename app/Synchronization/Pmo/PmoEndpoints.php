<?php

namespace App\Synchronization\Pmo;

/**
 * Menerjemahkan kunci endpoint (mis. "products") ke path PMO sebenarnya
 * (mis. "/getProducts") berdasarkan config/synchronization.php.
 *
 * Dipakai bersama oleh PmoApi dan PmoSnapshotStore supaya keduanya membaca
 * path endpoint dari satu sumber yang sama, bukan menuliskannya ulang
 * masing-masing.
 */
class PmoEndpoints
{
    /**
     * @throws PmoException
     */
    public static function resolve(string $key): string
    {
        // Diambil langsung dari array supaya kunci yang mengandung titik
        // tetap terbaca apa adanya oleh config().
        $endpoints = (array) config('synchronization.endpoints', []);
        $endpoint = (string) ($endpoints[$key] ?? '');

        if ($endpoint === '') {
            throw new PmoException(
                'Endpoint PMO "'.$key.'" belum terdaftar di config/synchronization.php.'
            );
        }

        return $endpoint;
    }

    /**
     * Nama kunci pembungkus daftar baris pada respons endpoint ini —
     * "items" untuk hampir semua endpoint, kecuali yang didaftarkan lain di
     * config/synchronization.php ("endpoint_items_keys"). Dikonfirmasi
     * 2026-08-22: /getArmada membalas "data", bukan "items".
     */
    public static function itemsKey(string $key): string
    {
        $overrides = (array) config('synchronization.endpoint_items_keys', []);

        return (string) ($overrides[$key] ?? 'items');
    }
}
