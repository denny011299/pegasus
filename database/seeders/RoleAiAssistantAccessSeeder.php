<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Sisipkan modul "Asisten AI" (view) ke role Developer / Direksi / Superadmin.
 * role_id = -1 tetap bypass RoleAccess tanpa perlu baris ini.
 *
 * Default filter nama role; set ROLE_AI_SEED_ALL=1 untuk semua role aktif.
 */
class RoleAiAssistantAccessSeeder extends Seeder
{
    public function run(): void
    {
        $module = [
            'name' => 'Asisten AI',
            // Chat: cukup view; create/edit/delete/others ikut biar "Izinkan Semua" konsisten.
            'akses' => ['create', 'edit', 'delete', 'view', 'others'],
        ];

        $seedAll = filter_var(env('ROLE_AI_SEED_ALL', false), FILTER_VALIDATE_BOOLEAN);

        $query = Role::query()->where('status', 1)->orderBy('role_id');

        if (! $seedAll) {
            $query->where(function ($q) {
                $q->where('role_name', 'like', '%Developer%')
                    ->orWhere('role_name', 'Direksi')
                    ->orWhere('role_name', 'like', '%Superadmin%')
                    ->orWhere('role_name', 'like', '%Super Admin%');
            });
        }

        $roles = $query->get();

        if ($roles->isEmpty()) {
            $roles = Role::query()
                ->where('status', 1)
                ->where(function ($q) {
                    $q->where('role_name', 'like', '%Developer%')
                        ->orWhere('role_name', 'Direksi');
                })
                ->get();
        }

        foreach ($roles as $role) {
            $access = json_decode($role->role_access ?? '[]', true);
            if (! is_array($access)) {
                $access = [];
            }

            $needle = strtolower($module['name']);
            $found = false;
            foreach ($access as $i => $row) {
                $name = strtolower(trim((string) ($row['name'] ?? '')));
                if ($name === $needle) {
                    $access[$i]['name'] = $module['name'];
                    $access[$i]['akses'] = $module['akses'];
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $access[] = $module;
            }

            $role->role_access = json_encode(array_values($access), JSON_UNESCAPED_UNICODE);
            $role->save();
        }
    }
}
