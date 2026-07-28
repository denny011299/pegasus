<?php

namespace App\ExternalApi\Http;

use Illuminate\Http\JsonResponse;

/**
 * Bentuk baku respons External API.
 *
 * Aplikasi ini belum punya standar respons API — endpoint internal yang ada
 * mengembalikan bermacam bentuk (angka 1, {status:-1}, koleksi Eloquent polos)
 * karena hanya dipakai jQuery milik sendiri. Bentuk itu tidak layak dipakai
 * pihak ketiga, jadi External API memakai standar tersendiri yang ditetapkan
 * di kelas ini dan TIDAK mengubah apa pun di API internal.
 *
 * Sukses:
 *   { "success": true, "data": ..., "meta": { ... } }
 *
 * Gagal:
 *   { "success": false, "error": { "code": "...", "message": "...", "details": ... } }
 *
 * `code` adalah string stabil yang boleh dijadikan pegangan klien; `message`
 * boleh berubah sewaktu-waktu karena ditujukan untuk dibaca manusia.
 */
class ApiResponse
{
    /** Kode error baku milik lapisan platform. */
    public const ERROR_UNAUTHENTICATED = 'unauthenticated';
    public const ERROR_INVALID_KEY = 'invalid_api_key';
    public const ERROR_KEY_REVOKED = 'api_key_revoked';
    public const ERROR_KEY_EXPIRED = 'api_key_expired';
    public const ERROR_APPLICATION_DISABLED = 'application_disabled';
    public const ERROR_NOT_FOUND = 'not_found';
    public const ERROR_VALIDATION = 'validation_failed';
    public const ERROR_SERVER = 'server_error';

    /**
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success($data = null, array $meta = [], int $httpStatus = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $httpStatus);
    }

    /**
     * @param  mixed  $details
     */
    public static function error(string $code, string $message, int $httpStatus = 400, $details = null): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
        ], $httpStatus);
    }

    /**
     * Respons daftar terpaginasi.
     *
     * Standar paginasi External API: nomor halaman (`page` & `per_page`) dengan
     * ringkasan di `meta.pagination`. Dipakai lewat helper ini supaya seluruh
     * endpoint di masa depan memberi bentuk yang sama tanpa perlu menyalin
     * susunan meta-nya satu per satu.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @param  callable|null  $transform  pemeta satu baris menjadi array
     */
    public static function paginated($paginator, ?callable $transform = null): JsonResponse
    {
        $items = collect($paginator->items());
        if ($transform) {
            $items = $items->map($transform);
        }

        return self::success($items->values()->all(), [
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }
}
