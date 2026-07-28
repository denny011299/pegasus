<?php

namespace App\ExternalApi\Support;

use Illuminate\Http\Request;

/**
 * Perhitungan alamat External API.
 *
 * Semua yang berhubungan dengan bentuk alamat dikumpulkan di sini supaya
 * awalannya benar-benar hanya ditulis sekali, yaitu di
 * config('externalapi.base_path'). Rute, penjaga penanganan error, Base URL di
 * halaman dokumentasi, dan alamat contoh tiap endpoint semuanya lewat kelas
 * ini — jadi memindahkan API tinggal mengubah satu nilai config.
 */
final class ExternalApiPath
{
    /** Awalan tanpa garis miring di ujung, contoh: "api/external". */
    public static function basePath(): string
    {
        return trim((string) config('externalapi.base_path', 'api/external'), '/');
    }

    /** Jumlah segmen awalan, dipakai untuk membaca versi dari alamat. */
    public static function baseSegmentCount(): int
    {
        $base = self::basePath();

        return $base === '' ? 0 : count(explode('/', $base));
    }

    /** Awalan lengkap satu versi, contoh: "/api/external/v1". */
    public static function versionPrefix(string $version): string
    {
        return '/' . self::basePath() . '/' . trim($version, '/');
    }

    /** Alamat lengkap satu endpoint, contoh: "/api/external/v1/products". */
    public static function endpoint(string $version, string $path): string
    {
        return self::versionPrefix($version) . '/' . ltrim($path, '/');
    }

    /** Base URL yang ditampilkan di dokumentasi, lengkap dengan domain. */
    public static function baseUrl(string $version): string
    {
        return rtrim((string) config('app.url'), '/') . self::versionPrefix($version);
    }

    /** Apakah permintaan ini ditujukan ke External API. */
    public static function matches(Request $request): bool
    {
        return $request->is(self::basePath() . '/*');
    }

    /**
     * Versi yang diminta, dibaca dari segmen tepat sesudah awalan.
     *
     * Dihitung dari panjang awalan, bukan dari posisi tetap, supaya tetap
     * benar walau base_path diubah menjadi lebih panjang atau lebih pendek.
     */
    public static function versionFrom(Request $request): ?string
    {
        return $request->segments()[self::baseSegmentCount()] ?? null;
    }
}
