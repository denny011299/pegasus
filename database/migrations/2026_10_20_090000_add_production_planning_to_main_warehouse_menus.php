<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Whitelist menu gudang tersimpan per gudang; tanpa ini menu PP tersembunyi
    // di gudang utama yang whitelist-nya dibuat sebelum modul PP ada.
    public function up(): void
    {
        if (! Schema::hasTable('warehouses') || ! Schema::hasColumn('warehouses', 'sidebar_menus')) {
            return;
        }

        $mainTypeIds = Schema::hasTable('warehouse_types')
            ? DB::table('warehouse_types')->where('is_main_warehouse', 1)->pluck('id')->all()
            : [];
        if ($mainTypeIds === []) {
            return;
        }

        DB::table('warehouses')
            ->whereIn('warehouse_type_id', $mainTypeIds)
            ->whereNotNull('sidebar_menus')
            ->orderBy('id')
            ->eachById(function ($w) {
                $menus = json_decode($w->sidebar_menus ?? '[]', true);
                if (! is_array($menus) || $menus === [] || in_array('Production Planning', $menus, true)) {
                    return;
                }
                $menus[] = 'Production Planning';
                DB::table('warehouses')->where('id', $w->id)
                    ->update(['sidebar_menus' => json_encode(array_values($menus))]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('warehouses') || ! Schema::hasColumn('warehouses', 'sidebar_menus')) {
            return;
        }

        DB::table('warehouses')
            ->whereNotNull('sidebar_menus')
            ->orderBy('id')
            ->eachById(function ($w) {
                $menus = json_decode($w->sidebar_menus ?? '[]', true);
                if (! is_array($menus)) {
                    return;
                }
                $menus = array_values(array_filter($menus, fn ($m) => $m !== 'Production Planning'));
                DB::table('warehouses')->where('id', $w->id)
                    ->update(['sidebar_menus' => json_encode($menus)]);
            });
    }
};
