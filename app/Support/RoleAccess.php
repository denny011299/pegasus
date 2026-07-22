<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class RoleAccess
{
    public const ABILITIES = ['view', 'create', 'edit', 'delete', 'others'];

    /** @var array<int, string|null> */
    private static array $roleAccessCache = [];

    /**
     * Ambil role_access dari tabel roles (sumber kebenaran),
     * bukan salinan stale di session user.
     */
    public static function collect($user): Collection
    {
        if (!$user) {
            return collect();
        }

        $json = self::resolveRoleAccessJson($user);
        $decoded = json_decode($json ?? '[]');
        if (!is_array($decoded)) {
            return collect();
        }

        return collect($decoded);
    }

    public static function resolveRoleAccessJson($user): string
    {
        $roleId = (int) ($user->role_id ?? 0);

        // Superadmin / role khusus tanpa baris di roles
        if ($roleId <= 0) {
            return (string) ($user->role_access ?? '[]');
        }

        if (!array_key_exists($roleId, self::$roleAccessCache)) {
            $role = Role::query()
                ->where('role_id', $roleId)
                ->where('status', 1)
                ->first(['role_id', 'role_access']);

            self::$roleAccessCache[$roleId] = $role
                ? (string) ($role->role_access ?? '[]')
                : (string) ($user->role_access ?? '[]');
        }

        return self::$roleAccessCache[$roleId] ?? '[]';
    }

    public static function isSuperAdmin($user): bool
    {
        return $user && (int) ($user->role_id ?? 0) === -1;
    }

    /**
     * Superadmin / Developer boleh lihat semua gudang di navbar.
     */
    public static function canSeeAllWarehouses($user): bool
    {
        if (!$user) {
            return false;
        }

        if (self::isSuperAdmin($user)) {
            return true;
        }

        $roleName = strtolower(trim((string) ($user->role_name ?? '')));
        if ($roleName === '' && !empty($user->role_id)) {
            $role = Role::query()
                ->where('role_id', (int) $user->role_id)
                ->first(['role_name']);
            $roleName = strtolower(trim((string) ($role->role_name ?? '')));
        }

        return str_contains($roleName, 'developer')
            || str_contains($roleName, 'superadmin')
            || str_contains($roleName, 'super admin');
    }

    public static function can($user, string $module, string $ability): bool
    {
        $ability = strtolower(trim($ability));
        if (!in_array($ability, self::ABILITIES, true)) {
            return false;
        }

        if (self::isSuperAdmin($user)) {
            return true;
        }

        $moduleNeedle = strtolower(trim($module));
        $entry = self::collect($user)->first(function ($item) use ($moduleNeedle) {
            $name = strtolower(trim((string) ($item->name ?? '')));
            return $name === $moduleNeedle;
        });
        if (!$entry) {
            return false;
        }

        $list = $entry->akses ?? [];
        if (!is_array($list)) {
            $list = (array) $list;
        }

        $list = array_map(static fn ($a) => strtolower((string) $a), $list);

        return in_array($ability, $list, true);
    }

    public static function canAny($user, array $modules, string $ability): bool
    {
        foreach ($modules as $module) {
            if (self::can($user, (string) $module, $ability)) {
                return true;
            }
        }

        return false;
    }

    public static function userFromSession(): mixed
    {
        return Session::get('user');
    }

    /** Clear request-level cache (berguna setelah update permission). */
    public static function clearCache(): void
    {
        self::$roleAccessCache = [];
    }
}
