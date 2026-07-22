<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleWarehouseAccessSeeder extends Seeder
{
    /**
     * Format sama dengan modul Kategori/Variasi:
     * {"name":"Gudang","akses":["create","edit","delete","view","others"]}
     *
     * Default: role Developer + Direksi (full master).
     * Pass --all via env ROLE_WAREHOUSE_SEED_ALL=1 untuk semua role aktif.
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Gudang',
                'akses' => ['create', 'edit', 'delete', 'view', 'others'],
            ],
            [
                'name' => 'Tipe Gudang',
                'akses' => ['create', 'edit', 'delete', 'view', 'others'],
            ],
        ];

        $seedAll = filter_var(env('ROLE_WAREHOUSE_SEED_ALL', false), FILTER_VALIDATE_BOOLEAN);

        $query = Role::query()->where('status', 1)->orderBy('role_id');

        if (!$seedAll) {
            $query->where(function ($q) {
                $q->where('role_name', 'like', '%Developer%')
                    ->orWhere('role_name', 'Direksi')
                    ->orWhere('role_name', 'like', '%Superadmin%')
                    ->orWhere('role_name', 'like', '%Super Admin%');
            });
        }

        $roles = $query->get();

        // Fallback: jika filter kosong, pastikan minimal Okejob Developer ter-update
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
            if (!is_array($access)) {
                $access = [];
            }

            foreach ($modules as $module) {
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

                if (!$found) {
                    // Sisipkan setelah Variasi jika ada, biar urutan mirip Master
                    $insertAt = null;
                    foreach ($access as $i => $row) {
                        if (strtolower(trim((string) ($row['name'] ?? ''))) === 'variasi') {
                            $insertAt = $i + 1;
                            break;
                        }
                    }
                    if ($insertAt === null) {
                        $access[] = $module;
                    } else {
                        array_splice($access, $insertAt, 0, [$module]);
                    }
                }
            }

            $role->role_access = json_encode(array_values($access), JSON_UNESCAPED_UNICODE);
            $role->save();

            $this->command?->info("Updated role_access: [{$role->role_id}] {$role->role_name}");
        }
    }
}
