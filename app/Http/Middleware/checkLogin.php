<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class checkLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if(!Session::has('user')){
            return redirect('/login');
        }

        $this->refreshRoleOnHardReload($request);

        return $next($request);
    }

    /**
     * role_access dipatri ke session sejak login, jadi perubahan hak akses oleh admin
     * baru kepakai kalau staff itu login ulang. Sebagai jalan pintas tanpa perlu logout,
     * browser hard-refresh (Ctrl+Shift+R / Cmd+Shift+R) mengirim header Cache-Control/
     * Pragma: no-cache yang tidak dikirim saat refresh biasa (F5, max-age=0) atau request
     * AJAX biasa — jadi dipakai sebagai sinyal untuk menarik ulang role_access dari DB.
     */
    private function refreshRoleOnHardReload(Request $request): void
    {
        if ($request->ajax() || $request->wantsJson()) {
            return;
        }

        $cacheControl = strtolower((string) $request->header('Cache-Control'));
        $pragma = strtolower((string) $request->header('Pragma'));
        $isHardReload = str_contains($cacheControl, 'no-cache') || str_contains($pragma, 'no-cache');
        if (!$isHardReload) {
            return;
        }

        $user = Session::get('user');
        $roleId = (int) ($user->role_id ?? 0);
        if ($roleId <= 0) {
            return;
        }

        $role = Role::find($roleId);
        if ($role) {
            $user->role_access = $role->role_access;
            Session::put('user', $user);
        }
    }
}
