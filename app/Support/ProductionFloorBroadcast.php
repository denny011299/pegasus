<?php

namespace App\Support;

use App\Events\ProductionFloorChanged;

/**
 * Debounce 1 ping/gudang per request — hindari flood saat multi-row save.
 */
final class ProductionFloorBroadcast
{
    /** @var array<int, true> */
    private static array $queued = [];

    public static function ping(?int $warehouseId, string $reason = 'changed'): void
    {
        $wh = (int) $warehouseId;
        if ($wh <= 0) {
            return;
        }
        if (isset(self::$queued[$wh])) {
            return;
        }
        self::$queued[$wh] = true;

        $fire = static function () use ($wh, $reason): void {
            try {
                event(new ProductionFloorChanged($wh, $reason));
            } catch (\Throwable $e) {
                report($e);
            }
        };

        // Setelah response HTTP — client yang menyimpan juga sempat subscribe.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            $fire();

            return;
        }

        app()->terminating($fire);
    }
}
