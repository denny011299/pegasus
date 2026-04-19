<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class checkAccess
{
    /**
     * @param  string|null  $spec  "NamaModul" atau "NamaModul|view|create|edit|delete|others" (tanpa pipe = view)
     */
    public function handle(Request $request, Closure $next, ?string $spec = null): Response
    {
        if ($spec === null || $spec === '') {
            return $next($request);
        }

        $module = trim($spec);
        $ability = 'view';
        if (str_contains($spec, '|')) {
            [$module, $ability] = explode('|', $spec, 2);
            $module = trim($module);
            $ability = trim(strtolower($ability));
        }

        $user = Session::get('user');
        if (!RoleAccess::can($user, $module, $ability)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
