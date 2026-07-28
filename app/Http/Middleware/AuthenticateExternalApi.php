<?php

namespace App\Http\Middleware;

use App\ExternalApi\Auth\ExternalApiAuthenticator;
use App\ExternalApi\Http\ApiResponse;
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
    public function __construct(private readonly ExternalApiAuthenticator $authenticator)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
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
}
