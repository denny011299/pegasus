<?php

namespace App\ExternalApi\Support;

use App\ExternalApi\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Menerjemahkan exception menjadi bentuk error baku External API.
 *
 * Dipasang di bootstrap/app.php dan hanya berlaku untuk permintaan di bawah
 * /api. Rute web admin tetap memakai penanganan error bawaan Laravel.
 *
 * Tujuannya satu: sistem pihak ketiga selalu menerima bentuk yang sama
 * ({success:false, error:{code, message}}) apa pun yang terjadi di server,
 * termasuk saat terjadi kegagalan yang tidak diantisipasi.
 */
final class ExceptionRenderer
{
    public static function render(\Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                ApiResponse::ERROR_VALIDATION,
                'Data yang dikirim tidak lolos validasi.',
                422,
                $e->errors(),
            );
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return ApiResponse::error(
                self::codeForStatus($status),
                $e->getMessage() !== '' ? $e->getMessage() : self::messageForStatus($status),
                $status,
            );
        }

        // Pesan exception asli tidak pernah dibocorkan ke klien; detailnya
        // tetap masuk log aplikasi lewat penanganan bawaan Laravel. Saat debug
        // aktif, pesan aslinya ikut ditampilkan agar pengembangan tidak buta.
        return ApiResponse::error(
            ApiResponse::ERROR_SERVER,
            'Terjadi kesalahan pada server.',
            500,
            config('app.debug') ? ['exception' => $e->getMessage()] : null,
        );
    }

    private static function codeForStatus(int $status): string
    {
        return match ($status) {
            401 => ApiResponse::ERROR_UNAUTHENTICATED,
            404 => ApiResponse::ERROR_NOT_FOUND,
            422 => ApiResponse::ERROR_VALIDATION,
            default => $status >= 500 ? ApiResponse::ERROR_SERVER : 'request_failed',
        };
    }

    private static function messageForStatus(int $status): string
    {
        return match ($status) {
            401 => 'Autentikasi gagal.',
            403 => 'Akses ditolak.',
            404 => 'Endpoint tidak ditemukan.',
            405 => 'Metode HTTP tidak didukung untuk endpoint ini.',
            default => 'Permintaan tidak dapat diproses.',
        };
    }
}
