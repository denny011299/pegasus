<?php

namespace App\Support\StockOpname;

use App\Models\Staff;
use App\Models\StockOpname;
use App\Models\StockOpnameBahan;
use App\Models\Warehouse;
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

    /**
     * Semua dokumen opname open (status=1, hari ini) lintas gudang — untuk monitor.
     *
     * @return array<int, array<string, mixed>>
     */
    public function liveOpenDocumentsSnapshot(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $dateStr = $date->toDateString();
        $out = [];

        $productQ = StockOpname::query()
            ->where('status', 1)
            ->whereDate('sto_date', $dateStr);
        if (Schema::hasColumn('stock_opnames', 'is_old_version')) {
            $productQ->where('is_old_version', false);
        }
        $products = $productQ->orderBy('warehouse_id')->orderBy('sto_id')->get();

        $suppliesQ = StockOpnameBahan::query()
            ->where('status', 1)
            ->whereDate('stob_date', $dateStr);
        if (Schema::hasColumn('stock_opname_bahans', 'is_old_version')) {
            $suppliesQ->where('is_old_version', false);
        }
        $supplies = $suppliesQ->orderBy('warehouse_id')->orderBy('stob_id')->get();

        $whIds = $products->pluck('warehouse_id')
            ->merge($supplies->pluck('warehouse_id'))
            ->unique()
            ->filter()
            ->all();
        $staffIds = $products->pluck('staff_id')
            ->merge($products->pluck('created_by'))
            ->merge($supplies->pluck('staff_id'))
            ->merge($supplies->pluck('created_by'))
            ->unique()
            ->filter()
            ->all();

        $whNames = $whIds
            ? Warehouse::whereIn('id', $whIds)->pluck('warehouse_name', 'id')
            : collect();
        $staffNames = $staffIds
            ? Staff::whereIn('staff_id', $staffIds)->pluck('staff_name', 'staff_id')
            : collect();

        foreach ($products as $row) {
            $wid = (int) ($row->warehouse_id ?? 0);
            $sid = (int) ($row->created_by ?: $row->staff_id ?: 0);
            $out[] = [
                'domain' => self::DOMAIN_PRODUCT,
                'domain_label' => 'Produk',
                'id' => (int) $row->sto_id,
                'code' => (string) ($row->sto_code ?? ''),
                'warehouse_id' => $wid,
                'warehouse_name' => (string) ($whNames[$wid] ?? 'Gudang #'.$wid),
                'staff_id' => $sid,
                'staff_name' => (string) ($staffNames[$sid] ?? '—'),
                'is_draft' => (bool) ($row->is_draft ?? false),
                'date' => $dateStr,
                'url' => url('/detailStockOpname/'.$row->sto_id),
            ];
        }

        foreach ($supplies as $row) {
            $wid = (int) ($row->warehouse_id ?? 0);
            $sid = (int) ($row->created_by ?: $row->staff_id ?: 0);
            $out[] = [
                'domain' => self::DOMAIN_SUPPLIES,
                'domain_label' => 'Bahan Mentah',
                'id' => (int) $row->stob_id,
                'code' => (string) ($row->stob_code ?? ''),
                'warehouse_id' => $wid,
                'warehouse_name' => (string) ($whNames[$wid] ?? 'Gudang #'.$wid),
                'staff_id' => $sid,
                'staff_name' => (string) ($staffNames[$sid] ?? '—'),
                'is_draft' => (bool) ($row->is_draft ?? false),
                'date' => $dateStr,
                'url' => url('/detailStockOpnameBahan/'.$row->stob_id),
            ];
        }

        return $out;
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
