<?php

namespace App\Support;

use App\Support\StockOpname\OpenOpnameGuard;

/**
 * Soft-block mutasi stok saat opname open (tolak aksi; tanpa antrian).
 */
class PendingStockSoftBlock
{
    public static function messageIfBlocked(int $warehouseId, string $domain = OpenOpnameGuard::DOMAIN_PRODUCT): ?string
    {
        if ($warehouseId <= 0) {
            return null;
        }

        $guard = app(OpenOpnameGuard::class);
        if (! $guard->isBlocked($warehouseId, $domain)) {
            return null;
        }

        $blocker = $guard->firstBlocker($warehouseId, $domain);
        if ($blocker && ($blocker['code'] ?? '') !== '') {
            return 'Gudang sedang Stock Opname ('.$blocker['code'].'). Mutasi stok ditolak sampai opname selesai.';
        }

        return 'Gudang sedang Stock Opname. Mutasi stok ditolak sampai opname selesai.';
    }

    /** Block kalau opname produk ATAU bahan open di gudang itu. */
    public static function messageIfAnyDomainBlocked(int $warehouseId): ?string
    {
        return self::messageIfBlocked($warehouseId, OpenOpnameGuard::DOMAIN_PRODUCT)
            ?? self::messageIfBlocked($warehouseId, OpenOpnameGuard::DOMAIN_SUPPLIES);
    }

    /**
     * @param  iterable<int|string|null>  $warehouseIds
     */
    public static function messageIfAnyWarehouseBlocked(
        iterable $warehouseIds,
        string $domain = OpenOpnameGuard::DOMAIN_PRODUCT
    ): ?string {
        $seen = [];
        foreach ($warehouseIds as $wid) {
            $id = (int) $wid;
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $msg = self::messageIfBlocked($id, $domain);
            if ($msg !== null) {
                return $msg;
            }
        }

        return null;
    }

    /**
     * @param  iterable<int|string|null>  $warehouseIds
     */
    public static function messageIfAnyWarehouseAnyDomainBlocked(iterable $warehouseIds): ?string
    {
        return self::messageIfAnyWarehouseBlocked($warehouseIds, OpenOpnameGuard::DOMAIN_PRODUCT)
            ?? self::messageIfAnyWarehouseBlocked($warehouseIds, OpenOpnameGuard::DOMAIN_SUPPLIES);
    }
}
