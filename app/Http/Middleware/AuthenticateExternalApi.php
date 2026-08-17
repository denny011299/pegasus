<?php

namespace App\Http\Middleware;

use App\ExternalApi\Auth\ExternalApiAuthenticator;
use App\ExternalApi\Docs\ApiEndpointDoc;
use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\ExternalApi\Support\ApiEndpointSettings;
use App\ExternalApi\Support\ExternalApiPath;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang seluruh rute External API.
 *
 * Terpisah dari checkLogin/checkAccess yang menjaga halaman admin: middleware
 * ini tidak menyentuh session dan selalu menjawab JSON, tidak pernah redirect
 * ke halaman login.
 */
class AuthenticateExternalApi
{
    public function __construct(
        private readonly ExternalApiAuthenticator $authenticator,
        private readonly ApiEndpointSettings $endpointSettings,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Saklar "Endpoint Aktif" di halaman Status API Eksternal — per
        // ENDPOINT, bukan per versi: mematikan satu endpoint tidak menyentuh
        // endpoint lain pada versi yang sama. Dicek sebelum autentikasi kunci
        // supaya endpoint yang dinonaktifkan menolak SELURUH permintaan ke
        // situ secara seragam, termasuk yang membawa API Key valid — bukan
        // cuma memblokir pemegang kunci yang salah.
        $doc = $this->matchedDoc($request);

        if ($doc !== null && ! $this->endpointSettings->isActive($doc->key())) {
            return ApiResponse::error(
                ErrorCatalog::API_ENDPOINT_DISABLED,
                'Endpoint "'.$doc->title().'" sedang dinonaktifkan sementara.',
                503,
            );
        }

        $result = $this->authenticator->authenticate($request);

        if (! $result->authenticated) {
            return ApiResponse::error(
                $result->errorCode,
                $result->errorMessage,
                $result->httpStatus,
            );
        }

        return $next($request);
    }

    /**
     * Dokumentasi endpoint yang cocok dengan rute yang baru saja dicocokkan
     * Laravel untuk permintaan ini, kalau ada. Path route (mis.
     * "api/external/v1/shipments/{ref_shipment_id}/change-status") dipotong
     * awalan versinya lalu dibandingkan dengan ApiEndpointDoc::path() —
     * lihat catatan akurasi path di ApiEndpointSettings::findDocForRequest().
     */
    private function matchedDoc(Request $request): ?ApiEndpointDoc
    {
        $version = ExternalApiPath::versionFrom($request);
        $route = $request->route();

        if ($version === null || $route === null) {
            return null;
        }

        $fullPath = '/'.ltrim($route->uri(), '/');
        $prefix = ExternalApiPath::versionPrefix($version);
        $relativePath = str_starts_with($fullPath, $prefix) ? substr($fullPath, strlen($prefix)) : $fullPath;

        return $this->endpointSettings->findDocForRequest($version, $request->method(), $relativePath);
    }
}
