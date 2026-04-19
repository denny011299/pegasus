<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parameter contoh: "Kas,Kas Operasional,view" — kemampuan (view/create/edit/delete/others) = segmen terakhir jika valid.
 * Tanpa kemampuan di akhir: default view untuk semua modul di daftar.
 */
class checkAccessAny
{
    public function handle(Request $request, Closure $next, string $spec = ''): Response
    {
        if ($spec === '') {
            return $next($request);
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $spec)), static fn ($p) => $p !== ''));
        if ($parts === []) {
            return $next($request);
        }

        $ability = 'view';
        $last = strtolower(end($parts));
        if (in_array($last, RoleAccess::ABILITIES, true)) {
            $ability = array_pop($parts);
        }

        $user = Session::get('user');
        foreach ($parts as $module) {
            if (RoleAccess::can($user, $module, $ability)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized');
    }
}
