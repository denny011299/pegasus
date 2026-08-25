<?php

namespace App\Support;

use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

/**
 * Akses menu per gudang aktif — selaras sidebar ($canShow / allowsSidebarMenu)
 * plus aturan khusus: beberapa menu hanya gudang utama.
 */
class WarehouseMenuAccess
{
    /** Nama SubModules yang hanya boleh di gudang utama (is_main_warehouse = 1). */
    public const MAIN_ONLY_MENUS = [
        'Produksi',
    ];

    /**
     * Modul role yang bukan item sidebar gudang (tidak bisa di-whitelist di form Gudang).
     * Cek akses tetap via RoleAccess; jangan digagalkan oleh sidebar_menus gudang.
     */
    public const NON_SIDEBAR_MODULES = [
        'Safety Stock',
    ];

    public static function isMainOnlyMenu(string $module): bool
    {
        $needle = strtolower(trim($module));
        foreach (self::MAIN_ONLY_MENUS as $name) {
            if (strtolower($name) === $needle) {
                return true;
            }
        }

        return false;
    }

    public static function isNonSidebarModule(string $module): bool
    {
        $needle = strtolower(trim($module));
        foreach (self::NON_SIDEBAR_MODULES as $name) {
            if (strtolower($name) === $needle) {
                return true;
            }
        }

        return false;
    }

    public static function isMainWarehouse(?Warehouse $warehouse): bool
    {
        if (! $warehouse) {
            return false;
        }

        $type = $warehouse->relationLoaded('type')
            ? $warehouse->type
            : $warehouse->type()->first();

        return $type && (int) ($type->is_main_warehouse ?? 0) === 1;
    }

    public static function resolveActiveWarehouse(): ?Warehouse
    {
        $id = (int) (Session::get('active_warehouse_id') ?? 0);
        if ($id <= 0 || ! Schema::hasTable('warehouses')) {
            return null;
        }

        return Warehouse::query()
            ->with(['type' => fn ($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse')])
            ->find($id);
    }

    /**
     * Apakah gudang (aktif / diberikan) boleh membuka modul ini.
     * Tanpa gudang aktif → true (sama seperti sidebar bila !$activeWh).
     */
    public static function allows(string $module, ?Warehouse $warehouse = null): bool
    {
        $module = trim($module);
        if ($module === '') {
            return true;
        }

        // Safety Stock dll. bukan menu sidebar gudang — akses cukup dari role.
        if (self::isNonSidebarModule($module)) {
            return true;
        }

        $warehouse ??= self::resolveActiveWarehouse();
        if (! $warehouse) {
            return true;
        }

        if (self::isMainOnlyMenu($module) && ! self::isMainWarehouse($warehouse)) {
            return false;
        }

        return $warehouse->allowsSidebarMenu($module);
    }

    /**
     * Buang menu main-only dari whitelist bila tipe gudang bukan utama.
     *
     * @param  array<int, string>|null  $menus
     * @return array<int, string>|null
     */
    public static function stripMainOnlyMenusUnlessMain(?array $menus, $warehouseTypeId): ?array
    {
        if ($menus === null || $menus === []) {
            return $menus;
        }

        $typeId = (int) $warehouseTypeId;
        $isMain = false;
        if ($typeId > 0 && Schema::hasTable('warehouse_types')) {
            $isMain = WarehouseType::query()
                ->whereKey($typeId)
                ->where('is_main_warehouse', 1)
                ->exists();
        }

        if ($isMain) {
            return $menus;
        }

        $blocked = array_map('strtolower', self::MAIN_ONLY_MENUS);
        $clean = array_values(array_filter(
            $menus,
            static fn ($m) => ! in_array(strtolower(trim((string) $m)), $blocked, true)
        ));

        return $clean === [] ? null : $clean;
    }
}
