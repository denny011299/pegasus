<?php

namespace App\Support\StockOpname;

use Illuminate\Support\Facades\Cache;

/**
 * Versi status opname per gudang — client poll cepat; bump saat open/close
 * supaya semua browser lihat perubahan tanpa delay panjang.
 */
class OpenOpnameStatusSignal
{
    public static function bump(?int $warehouseId = null): int
    {
        $wh = (int) ($warehouseId ?? 0);
        $global = (int) Cache::get('opname_status_rev_global', 0) + 1;
        Cache::forever('opname_status_rev_global', $global);

        if ($wh > 0) {
            $key = 'opname_status_rev_'.$wh;
            $rev = (int) Cache::get($key, 0) + 1;
            Cache::forever($key, $rev);

            return $rev;
        }

        return $global;
    }

    public static function rev(int $warehouseId): int
    {
        $wh = (int) Cache::get('opname_status_rev_'.$warehouseId, 0);
        $global = (int) Cache::get('opname_status_rev_global', 0);

        return max($wh, $global);
    }
}
