<?php

namespace App\Support;

use App\Support\StockOpname\OpenOpnameGuard;

/**
 * Soft-block mutasi non-queue (PO/SO/retur/…) saat opname open.
 */
class PendingStockSoftBlock
{
    public static function messageIfBlocked(int $warehouseId, string $domain = OpenOpnameGuard::DOMAIN_PRODUCT): ?string
    {
        if ($warehouseId <= 0) {
            return null;
        }
        $svc = app(PendingStockOperationService::class);
        // Opname kemarin sudah lewat tanggal → flush antrian usang dulu (tanpa cron).
        $svc->flushStaleIfUnblocked($warehouseId, $domain);

        $guard = app(OpenOpnameGuard::class);
        if (! $guard->isBlocked($warehouseId, $domain)) {
            return null;
        }

        return $svc->softBlockMessage($warehouseId, $domain);
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
}
