<?php

namespace App\ExternalApi\Auth;

use App\Models\ExternalApiKey;
use App\Models\ExternalApplication;

/**
 * Hasil pemeriksaan satu API Key.
 *
 * Kegagalan membawa kode error yang sudah cocok dengan ApiResponse, sehingga
 * middleware tinggal meneruskannya tanpa menerjemahkan ulang.
 */
final class AuthenticationResult
{
    private function __construct(
        public readonly bool $authenticated,
        public readonly ?ExternalApplication $application = null,
        public readonly ?ExternalApiKey $key = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly int $httpStatus = 401,
    ) {
    }

    public static function success(ExternalApplication $application, ExternalApiKey $key): self
    {
        return new self(true, $application, $key);
    }

    public static function failure(string $errorCode, string $errorMessage, int $httpStatus = 401): self
    {
        return new self(false, null, null, $errorCode, $errorMessage, $httpStatus);
    }
}
