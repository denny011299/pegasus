<?php

namespace App\Http\Middleware;

use App\Support\AppMaintenance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance aplikasi — logout semua session & blokir akses (login termasuk).
 * Toggle: APP_MAINTENANCE_MODE di .env atau `php artisan app:maintenance on|off`.
 */
class EnforceAppMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AppMaintenance::enabled()) {
            return $next($request);
        }

        if ($request->is('up')) {
            return $next($request);
        }

        if (Session::isStarted()) {
            Session::invalidate();
            Session::regenerateToken();
        }

        $message = AppMaintenance::message();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => -1,
                'message' => $message,
                'maintenance' => true,
            ], 503);
        }

        return response()->view('maintenance', [
            'message' => $message,
        ], 503);
    }
}
