<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Kepala Operasional perlu Pengiriman|view agar bisa buka list + ACC tahap Ops
 * (approveShipment di-gate actor role, tapi route/page butuh view).
 * Tanpa delete — hapus hanya untuk role yang punya ability delete.
 */
class RolePengirimanKepalaOpsSeeder extends Seeder
{
    public function run(): void
    {
        $module = [
            'name' => 'Pengiriman',
            'akses' => ['view'],
        ];

        $roles = Role::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('role_name', 'like', '%Kepala Operasional%')
                    ->orWhere('role_name', 'like', '%Kepala Ops%');
            })
            ->get();

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
                    // Jangan cabut ability yang sudah ada; pastikan view tetap ada.
                    $akses = array_values(array_unique(array_merge(
                        array_map('strtolower', (array) ($row['akses'] ?? [])),
                        ['view']
                    )));
                    $access[$i]['name'] = $module['name'];
                    $access[$i]['akses'] = $akses;
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
