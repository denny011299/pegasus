<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class checkAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission = null): Response
    {
        if (!$permission) {
            return $next($request);
        }

        $user = Session::get('user');
        $akses = collect(json_decode($user->role_access));

        $hasAccess = $akses->firstWhere('name', $permission);

        if (!$hasAccess) {
            abort(403, 'Unauthorized');
        }
        
        return $next($request);
    }
}
