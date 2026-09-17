<?php

namespace App\Support\StockOpname;

use App\Models\StockOpname;
use App\Models\StockOpnameBahan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Deteksi Stock Opname terbuka (status=1 draft/menunggu) per gudang + domain.
 * Dipakai soft-block mutasi stok — jangan campur dengan ProductionPendingStockRestorer.
 */
class OpenOpnameGuard
{
    public const DOMAIN_PRODUCT = 'product';

    public const DOMAIN_SUPPLIES = 'supplies';

    /**
     * Header opname open di gudang + domain (tanggal default hari ini).
     *
     * @return Collection<int, StockOpname|StockOpnameBahan>
     */
    public function blockingOpnames(int $warehouseId, string $domain, ?Carbon $date = null): Collection
    {
        return $this->openQuery($warehouseId, $domain, $date)->get();
    }

    public function isBlocked(int $warehouseId, string $domain, ?Carbon $date = null): bool
    {
        if ($this->openQuery($warehouseId, $domain, $date)->exists()) {
            return true;
        }

        return OpnamePageLock::isLive($warehouseId, $domain);
    }

    /**
     * Sama seperti blockingOpnames, dengan lockForUpdate (panggil di dalam transaksi).
     *
     * @return Collection<int, StockOpname|StockOpnameBahan>
     */
    public function lockOpenOpnames(int $warehouseId, string $domain, ?Carbon $date = null): Collection
    {
        return $this->openQuery($warehouseId, $domain, $date)->lockForUpdate()->get();
    }

    /**
     * @return array{type: string, id: int, code: string}|null
     */
    public function firstBlocker(int $warehouseId, string $domain): ?array
    {
        $row = $this->blockingOpnames($warehouseId, $domain)->first();
        if ($row) {
            if ($domain === self::DOMAIN_SUPPLIES) {
                return [
                    'type' => 'bahan',
                    'id' => (int) $row->stob_id,
                    'code' => (string) ($row->stob_code ?? ''),
                ];
            }

            return [
                'type' => 'produk',
                'id' => (int) $row->sto_id,
                'code' => (string) ($row->sto_code ?? ''),
            ];
        }

        $lock = OpnamePageLock::liveRow($warehouseId, $domain);
        if ($lock) {
            return [
                'type' => 'page_lock',
                'id' => 0,
                'code' => 'Input: '.(string) ($lock->staff_name ?: 'User'),
            ];
        }

        return null;
    }

    /**
     * Snapshot status open opname untuk indikator UI (header lamp / FAB).
     *
     * @return array{
     *   product: array{open: bool, code: ?string, id: ?int, url: string, page_lock: bool, held_by: ?string},
     *   supplies: array{open: bool, code: ?string, id: ?int, url: string, page_lock: bool, held_by: ?string},
     *   any_open: bool
     * }
     */
    public function statusForWarehouse(int $warehouseId): array
    {
        $product = $warehouseId > 0 ? $this->firstBlocker($warehouseId, self::DOMAIN_PRODUCT) : null;
        $supplies = $warehouseId > 0 ? $this->firstBlocker($warehouseId, self::DOMAIN_SUPPLIES) : null;
        $productLock = $warehouseId > 0 ? OpnamePageLock::status($warehouseId, self::DOMAIN_PRODUCT) : ['locked' => false];
        $suppliesLock = $warehouseId > 0 ? OpnamePageLock::status($warehouseId, self::DOMAIN_SUPPLIES) : ['locked' => false];

        $productOpen = $product !== null || ! empty($productLock['locked']);
        $suppliesOpen = $supplies !== null || ! empty($suppliesLock['locked']);

        return [
            'product' => [
                'open' => $productOpen,
                'code' => $product['code'] ?? null,
                'id' => $product['id'] ?? null,
                'url' => url('/stockOpname'),
                'page_lock' => ! empty($productLock['locked']),
                'held_by' => $productLock['held_by'] ?? null,
            ],
            'supplies' => [
                'open' => $suppliesOpen,
                'code' => $supplies['code'] ?? null,
                'id' => $supplies['id'] ?? null,
                'url' => url('/stockOpnameBahan'),
                'page_lock' => ! empty($suppliesLock['locked']),
                'held_by' => $suppliesLock['held_by'] ?? null,
            ],
            'any_open' => $productOpen || $suppliesOpen,
        ];
    }

    private function openQuery(int $warehouseId, string $domain, ?Carbon $date = null): Builder
    {
        $date = $date ?? Carbon::today();
        $dateStr = $date->toDateString();

        if ($domain === self::DOMAIN_SUPPLIES) {
            $q = StockOpnameBahan::query()
                ->where('status', 1)
                ->where('warehouse_id', $warehouseId)
                ->whereDate('stob_date', $dateStr);

            if (Schema::hasColumn('stock_opname_bahans', 'is_old_version')) {
                $q->where('is_old_version', false);
            }

            return $q->orderBy('stob_id');
        }

        $q = StockOpname::query()
            ->where('status', 1)
            ->where('warehouse_id', $warehouseId)
            ->whereDate('sto_date', $dateStr);

        if (Schema::hasColumn('stock_opnames', 'is_old_version')) {
            $q->where('is_old_version', false);
        }

        return $q->orderBy('sto_id');
    }
}
