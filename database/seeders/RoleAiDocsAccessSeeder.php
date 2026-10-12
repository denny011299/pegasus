<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Beri modul "Dokumentasi AI" (view) ke role yang sudah punya "Asisten AI".
 * Idempotent; role_id = -1 tetap bypass RoleAccess.
 */
class RoleAiDocsAccessSeeder extends Seeder
{
    public function run(): void
    {
        $module = ['name' => 'Dokumentasi AI', 'akses' => ['view']];

        foreach (Role::query()->where('status', 1)->get() as $role) {
            $access = json_decode($role->role_access ?? '[]', true);
            if (! is_array($access)) {
                continue;
            }

            $names = array_map(fn ($r) => strtolower(trim((string) ($r['name'] ?? ''))), $access);
            if (! in_array('asisten ai', $names, true) || in_array('dokumentasi ai', $names, true)) {
                continue;
            }

            $access[] = $module;
            $role->role_access = json_encode(array_values($access), JSON_UNESCAPED_UNICODE);
            $role->save();
        }
    }
}
