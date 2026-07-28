<?php

namespace App\ExternalApi\Auth;

use App\ExternalApi\ApiKeyManager;
use App\ExternalApi\Http\ApiResponse;
use App\Models\ExternalApiKey;
use App\Models\ExternalApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Autentikasi External API.
 *
 * Sengaja berdiri sendiri, terpisah dari login pengguna. Login admin memakai
 * session (`Session::get('user')`) lewat middleware checkLogin/checkAccess;
 * External API tidak menyentuh session sama sekali dan hanya bergantung pada
 * header kunci. Dua jalur ini tidak boleh saling meminjam: kunci API tidak
 * pernah membuka halaman admin, dan sesi admin tidak pernah membuka API.
 *
 * Aplikasi & kunci yang lolos diselipkan ke atribut Request supaya endpoint
 * bisnis di fase berikutnya bisa mengambilnya lewat helper di kelas ini.
 */
class ExternalApiAuthenticator
{
    public const REQUEST_APPLICATION = 'external_api_application';
    public const REQUEST_KEY = 'external_api_key';

    public function __construct(private readonly ApiKeyManager $keys)
    {
    }

    public function authenticate(Request $request): AuthenticationResult
    {
        $plainKey = $this->readKey($request);

        if (!$plainKey) {
            return AuthenticationResult::failure(
                ApiResponse::ERROR_UNAUTHENTICATED,
                'Header ' . $this->keys->header() . ' wajib diisi.',
            );
        }

        $prefix = $this->keys->extractPrefix($plainKey);
        if (!$prefix) {
            return $this->invalidKey();
        }

        /** @var ExternalApiKey|null $key */
        $key = ExternalApiKey::where('key_prefix', '=', $prefix)
            ->where('status', '=', 1)
            ->first();

        if (!$key) {
            return $this->invalidKey();
        }

        // Perbandingan constant-time. Dijalankan sebelum pemeriksaan status
        // agar pemegang kunci salah tidak bisa menyimpulkan apa pun tentang
        // keberadaan kunci dari pesan error yang berbeda-beda.
        if (!$this->keys->matches($plainKey, $key->key_hash)) {
            return $this->invalidKey();
        }

        $status = (new ExternalApiKey())->effectiveStatus($key);

        if ($status === ExternalApiKey::STATUS_REVOKED) {
            return AuthenticationResult::failure(
                ApiResponse::ERROR_KEY_REVOKED,
                'API Key sudah dicabut.',
            );
        }

        if ($status === ExternalApiKey::STATUS_EXPIRED) {
            return AuthenticationResult::failure(
                ApiResponse::ERROR_KEY_EXPIRED,
                'API Key sudah kedaluwarsa.',
            );
        }

        /** @var ExternalApplication|null $application */
        $application = ExternalApplication::where('external_application_id', '=', $key->external_application_id)
            ->where('status', '=', 1)
            ->first();

        // Aplikasi terhapus diperlakukan sama dengan kunci tidak dikenal: dari
        // sisi klien, kunci itu memang sudah tidak berlaku.
        if (!$application) {
            return $this->invalidKey();
        }

        if ($application->application_status !== ExternalApplication::STATUS_ACTIVE) {
            return AuthenticationResult::failure(
                ApiResponse::ERROR_APPLICATION_DISABLED,
                'Aplikasi eksternal sedang dinonaktifkan.',
                403,
            );
        }

        $this->touchLastUsed($key);

        $request->attributes->set(self::REQUEST_APPLICATION, $application);
        $request->attributes->set(self::REQUEST_KEY, $key);

        return AuthenticationResult::success($application, $key);
    }

    /** Aplikasi pemilik kunci pada request yang sudah lolos autentikasi. */
    public static function application(Request $request): ?ExternalApplication
    {
        return $request->attributes->get(self::REQUEST_APPLICATION);
    }

    /** Kunci yang dipakai pada request yang sudah lolos autentikasi. */
    public static function key(Request $request): ?ExternalApiKey
    {
        return $request->attributes->get(self::REQUEST_KEY);
    }

    /**
     * Kunci boleh dikirim lewat header khusus atau Authorization: Bearer.
     * Keduanya lazim dipakai klien server-to-server.
     */
    private function readKey(Request $request): ?string
    {
        $key = trim((string) $request->header($this->keys->header(), ''));

        if ($key === '') {
            $bearer = (string) $request->bearerToken();
            $key = trim($bearer);
        }

        return $key === '' ? null : $key;
    }

    /**
     * Satu pesan seragam untuk semua sebab "kunci tidak dikenal", supaya
     * respons tidak bisa dipakai menebak kunci mana yang benar-benar ada.
     */
    private function invalidKey(): AuthenticationResult
    {
        return AuthenticationResult::failure(
            ApiResponse::ERROR_INVALID_KEY,
            'API Key tidak valid.',
        );
    }

    /**
     * Catat pemakaian terakhir tanpa menyentuh updated_at, dan lewati kalau
     * baru saja dicatat — kolom ini hanya untuk informasi di halaman admin,
     * tidak sepadan dengan satu UPDATE di setiap permintaan.
     */
    private function touchLastUsed(ExternalApiKey $key): void
    {
        if ($key->last_used_at && Carbon::parse($key->last_used_at)->gt(now()->subMinute())) {
            return;
        }

        ExternalApiKey::where('external_api_key_id', '=', $key->external_api_key_id)
            ->update(['last_used_at' => now()]);
    }
}
