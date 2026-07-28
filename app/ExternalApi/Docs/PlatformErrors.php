<?php

namespace App\ExternalApi\Docs;

use App\ExternalApi\Http\ApiResponse;

/**
 * Error yang bisa muncul di endpoint mana pun karena berasal dari lapisan
 * platform, bukan dari logika bisnis satu endpoint.
 *
 * Ditampilkan sekali di bagian "Respons Error" halaman dokumentasi, supaya
 * setiap kelas dokumentasi endpoint tidak perlu menyalin ulang daftar ini.
 */
final class PlatformErrors
{
    /**
     * @return array<int, array{code:string, http_status:int, message:string, cause:string}>
     */
    public static function all(): array
    {
        return [
            [
                'code' => ApiResponse::ERROR_UNAUTHENTICATED,
                'http_status' => 401,
                'message' => 'Header API Key wajib diisi.',
                'cause' => 'Permintaan dikirim tanpa header API Key sama sekali.',
            ],
            [
                'code' => ApiResponse::ERROR_INVALID_KEY,
                'http_status' => 401,
                'message' => 'API Key tidak valid.',
                'cause' => 'Kunci tidak dikenali, salah ketik, atau aplikasinya sudah dihapus.',
            ],
            [
                'code' => ApiResponse::ERROR_KEY_REVOKED,
                'http_status' => 401,
                'message' => 'API Key sudah dicabut.',
                'cause' => 'Kunci dicabut administrator. Minta kunci baru.',
            ],
            [
                'code' => ApiResponse::ERROR_KEY_EXPIRED,
                'http_status' => 401,
                'message' => 'API Key sudah kedaluwarsa.',
                'cause' => 'Tanggal kedaluwarsa kunci sudah lewat. Minta kunci baru.',
            ],
            [
                'code' => ApiResponse::ERROR_APPLICATION_DISABLED,
                'http_status' => 403,
                'message' => 'Aplikasi eksternal sedang dinonaktifkan.',
                'cause' => 'Aplikasi pemilik kunci dinonaktifkan administrator.',
            ],
            [
                'code' => ApiResponse::ERROR_NOT_FOUND,
                'http_status' => 404,
                'message' => 'Data tidak ditemukan.',
                'cause' => 'Alamat endpoint keliru, atau data yang diminta tidak ada.',
            ],
            [
                'code' => ApiResponse::ERROR_VALIDATION,
                'http_status' => 422,
                'message' => 'Data yang dikirim tidak lolos validasi.',
                'cause' => 'Ada field wajib yang kosong atau formatnya salah. Rinciannya ada di error.details.',
            ],
            [
                'code' => ApiResponse::ERROR_SERVER,
                'http_status' => 500,
                'message' => 'Terjadi kesalahan pada server.',
                'cause' => 'Kegagalan tak terduga di sisi Pegasus. Hubungi administrator.',
            ],
        ];
    }
}
