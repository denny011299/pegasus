<?php

namespace App\Support\StockOpname;

use App\Models\StockOpnamePageLock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Exclusive lock halaman Input Stok Opname (-1) per gudang + domain.
 * Real-time: heartbeat 10s / TTL 35s; acquire atomic via unique (warehouse_id, domain).
 */
class OpnamePageLock
{
    public const TTL_SECONDS = 35;

    public const DOMAIN_PRODUCT = OpenOpnameGuard::DOMAIN_PRODUCT;

    public const DOMAIN_SUPPLIES = OpenOpnameGuard::DOMAIN_SUPPLIES;

    public static function tableReady(): bool
    {
        return Schema::hasTable('stock_opname_page_locks');
    }

    /**
     * @return array{ok: bool, token?: string, held_by?: string, staff_id?: int, taken_over?: bool}
     */
    public static function acquire(
        int $warehouseId,
        string $domain,
        int $staffId,
        string $staffName,
        ?string $token = null
    ): array {
        if ($warehouseId <= 0 || $staffId <= 0 || ! self::tableReady()) {
            return ['ok' => false, 'held_by' => 'Sistem'];
        }

        $domain = self::normalizeDomain($domain);
        $token = $token ?: (string) Str::uuid();
        $now = now();

        return DB::transaction(function () use ($warehouseId, $domain, $staffId, $staffName, $token, $now) {
            self::purgeExpired($warehouseId, $domain);

            $row = StockOpnamePageLock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('domain', $domain)
                ->lockForUpdate()
                ->first();

            if ($row && ! self::rowIsLive($row)) {
                $row->delete();
                $row = null;
            }

            if ($row) {
                // Holder yang sama (refresh / tab baru) → takeover token
                if ((int) $row->staff_id === $staffId) {
                    $row->token = $token;
                    $row->staff_name = $staffName !== '' ? $staffName : $row->staff_name;
                    $row->locked_at = $now;
                    $row->last_seen_at = $now;
                    $row->save();
                    OpenOpnameStatusSignal::bump($warehouseId);

                    return ['ok' => true, 'token' => $token, 'taken_over' => true];
                }

                return [
                    'ok' => false,
                    'held_by' => (string) ($row->staff_name ?: 'User lain'),
                    'staff_id' => (int) $row->staff_id,
                ];
            }

            try {
                $lock = new StockOpnamePageLock();
                $lock->warehouse_id = $warehouseId;
                $lock->domain = $domain;
                $lock->staff_id = $staffId;
                $lock->staff_name = $staffName !== '' ? $staffName : 'User';
                $lock->token = $token;
                $lock->locked_at = $now;
                $lock->last_seen_at = $now;
                $lock->save();
            } catch (QueryException $e) {
                // Race unique — baca ulang holder
                $again = StockOpnamePageLock::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('domain', $domain)
                    ->first();
                if ($again && self::rowIsLive($again) && (int) $again->staff_id === $staffId) {
                    $again->token = $token;
                    $again->last_seen_at = $now;
                    $again->save();
                    OpenOpnameStatusSignal::bump($warehouseId);

                    return ['ok' => true, 'token' => $token, 'taken_over' => true];
                }

                return [
                    'ok' => false,
                    'held_by' => (string) ($again->staff_name ?? 'User lain'),
                    'staff_id' => (int) ($again->staff_id ?? 0),
                ];
            }

            OpenOpnameStatusSignal::bump($warehouseId);

            return ['ok' => true, 'token' => $token];
        });
    }

    /**
     * @return array{ok: bool, reason?: string}
     */
    public static function heartbeat(string $token): array
    {
        if ($token === '' || ! self::tableReady()) {
            return ['ok' => false, 'reason' => 'missing'];
        }

        $row = StockOpnamePageLock::query()->where('token', $token)->first();
        if (! $row) {
            return ['ok' => false, 'reason' => 'not_holder'];
        }
        if (! self::rowIsLive($row)) {
            $row->delete();
            OpenOpnameStatusSignal::bump((int) $row->warehouse_id);

            return ['ok' => false, 'reason' => 'expired'];
        }

        $row->last_seen_at = now();
        $row->save();

        return ['ok' => true];
    }

    public static function release(string $token): bool
    {
        if ($token === '' || ! self::tableReady()) {
            return false;
        }

        $row = StockOpnamePageLock::query()->where('token', $token)->first();
        if (! $row) {
            return false;
        }

        $wh = (int) $row->warehouse_id;
        $row->delete();
        if ($wh > 0) {
            OpenOpnameStatusSignal::bump($wh);
        }

        return true;
    }

    public static function isLive(int $warehouseId, string $domain): bool
    {
        return self::liveRow($warehouseId, $domain) !== null;
    }

    public static function liveRow(int $warehouseId, string $domain): ?StockOpnamePageLock
    {
        if ($warehouseId <= 0 || ! self::tableReady()) {
            return null;
        }

        $domain = self::normalizeDomain($domain);
        self::purgeExpired($warehouseId, $domain);

        $row = StockOpnamePageLock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('domain', $domain)
            ->first();

        if (! $row || ! self::rowIsLive($row)) {
            if ($row) {
                $row->delete();
            }

            return null;
        }

        return $row;
    }

    /**
     * @return array{locked: bool, held_by: ?string, staff_id: ?int}
     */
    public static function status(int $warehouseId, string $domain): array
    {
        $row = self::liveRow($warehouseId, $domain);
        if (! $row) {
            return ['locked' => false, 'held_by' => null, 'staff_id' => null];
        }

        return [
            'locked' => true,
            'held_by' => (string) ($row->staff_name ?: 'User lain'),
            'staff_id' => (int) $row->staff_id,
        ];
    }

    /** Null = boleh tulis; string = pesan error. */
    public static function messageIfNotWritable(int $warehouseId, string $domain, int $staffId): ?string
    {
        $row = self::liveRow($warehouseId, $domain);
        if (! $row) {
            return null;
        }
        if ($staffId > 0 && (int) $row->staff_id === $staffId) {
            return null;
        }

        $name = (string) ($row->staff_name ?: 'User lain');

        return 'Ada user '.$name.' yang sedang membuka halaman Stock Opname.';
    }

    private static function rowIsLive(StockOpnamePageLock $row): bool
    {
        if (! $row->last_seen_at) {
            return false;
        }

        return $row->last_seen_at->greaterThan(now()->subSeconds(self::TTL_SECONDS));
    }

    private static function purgeExpired(int $warehouseId, string $domain): void
    {
        $cutoff = now()->subSeconds(self::TTL_SECONDS);
        StockOpnamePageLock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('domain', $domain)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $cutoff);
            })
            ->delete();
    }

    private static function normalizeDomain(string $domain): string
    {
        return $domain === self::DOMAIN_SUPPLIES
            ? self::DOMAIN_SUPPLIES
            : self::DOMAIN_PRODUCT;
    }
}
