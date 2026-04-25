<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class RoleAccess
{
    public const ABILITIES = ['view', 'create', 'edit', 'delete', 'others'];

    public static function collect($user): Collection
    {
        if (!$user || !isset($user->role_access)) {
            return collect();
        }

        $decoded = json_decode($user->role_access ?? '[]');
        if (!is_array($decoded)) {
            return collect();
        }

        return collect($decoded);
    }

    public static function isSuperAdmin($user): bool
    {
        return $user && (int) ($user->role_id ?? 0) === -1;
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
}
