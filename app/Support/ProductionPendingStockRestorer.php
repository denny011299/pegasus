<?php

namespace App\Support;

use App\Models\LogStock;
use App\Models\Production;
use App\Models\ProductStock;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kembalikan stok yang kepotong dari ACC produksi gagal
 * sementara header productions masih pending (status = 1).
 *
 * Lihat: docs/production-acc-stock-safety.md
 */
class ProductionPendingStockRestorer
{
    /**
     * @return array{
     *   productions: int,
     *   logs_reverted: int,
     *   details: list<array{production_code: string, logs: int}>
     * }
     */
    public function restoreAllPending(?string $onlyCode = null, bool $withHistory = false, ?int $staffId = null, bool $dryRun = false): array
    {
        $query = Production::query()->where('status', 1);
        if ($onlyCode !== null && $onlyCode !== '') {
            $query->where('production_code', $onlyCode);
        }

        $productions = $query->orderBy('production_id')->get();
        $summary = [
            'productions' => 0,
            'logs_reverted' => 0,
            'details' => [],
        ];

        foreach ($productions as $production) {
            $code = (string) $production->production_code;
            $count = $this->countActiveLogs($code);
            if ($count === 0) {
                continue;
            }

            if ($dryRun) {
                $summary['productions']++;
                $summary['logs_reverted'] += $count;
                $summary['details'][] = [
                    'production_code' => $code,
                    'logs' => $count,
                ];
                continue;
            }

            $reverted = DB::transaction(function () use ($code, $withHistory, $staffId) {
                return $this->revertProductionCode($code, $withHistory, $staffId);
            });

            if ($reverted > 0) {
                $summary['productions']++;
                $summary['logs_reverted'] += $reverted;
                $summary['details'][] = [
                    'production_code' => $code,
                    'logs' => $reverted,
                ];
            }
        }

        return $summary;
    }

    /**
     * Hapus log_stocks orphan pada produksi pending TANPA mengembalikan stok.
     * Dipakai setelah opname sudah mengunci angka — supaya tidak ada "lock"
     * cekLog / history potongan palsu, tanpa mengganggu supplies_stocks.
     *
     * @return array{
     *   productions: int,
     *   logs_cleared: int,
     *   details: list<array{production_code: string, logs: int}>
     * }
     */
    public function clearPendingMaterialLocks(?string $onlyCode = null, bool $dryRun = false): array
    {
        $query = Production::query()->where('status', 1);
        if ($onlyCode !== null && $onlyCode !== '') {
            $query->where('production_code', $onlyCode);
        }

        $productions = $query->orderBy('production_id')->get();
        $summary = [
            'productions' => 0,
            'logs_cleared' => 0,
            'details' => [],
        ];

        foreach ($productions as $production) {
            $code = (string) $production->production_code;
            $logs = LogStock::where('log_kode', $code)
                ->where('status', 1)
                ->orderByDesc('log_id')
                ->get();

            if ($logs->isEmpty()) {
                continue;
            }

            $summary['productions']++;
            $summary['logs_cleared'] += $logs->count();
            $summary['details'][] = [
                'production_code' => $code,
                'logs' => $logs->count(),
            ];

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($logs) {
                foreach ($logs as $log) {
                    $log->delete();
                }
            });
        }

        return $summary;
    }

    /**
     * Orphan log dihapus setelah stok dikembalikan.
     * Jika $withHistory: tulis log kompensasi agar riwayat stok terlihat.
     *
     * PERINGATAN: jangan pakai jika opname sudah mengunci angka setelah potongan.
     */
    public function revertProductionCode(string $productionCode, bool $withHistory = false, ?int $staffId = null): int
    {
        $logs = LogStock::where('log_kode', $productionCode)
            ->where('status', 1)
            ->orderByDesc('log_id')
            ->get();

        if ($logs->isEmpty()) {
            return 0;
        }

        foreach ($logs as $log) {
            $qty = (int) $log->log_jumlah;
            $reversed = $this->applyReverseStock($log, $qty);

            if ($withHistory && $reversed) {
                $this->insertCompensationLog($log, $qty, $staffId);
            }

            $log->delete();
        }

        return $logs->count();
    }

    private function countActiveLogs(string $productionCode): int
    {
        return (int) LogStock::where('log_kode', $productionCode)
            ->where('status', 1)
            ->count();
    }

    private function applyReverseStock(LogStock $log, int $qty): bool
    {
        $warehouseId = (int) ($log->warehouse_id ?? 0);

        if ((int) $log->log_type === 2) {
            $ssQuery = SuppliesStock::withoutGlobalScope('active_warehouse')
                ->where('supplies_id', $log->log_item_id)
                ->where('unit_id', $log->unit_id)
                ->where('status', 1);
            if ($warehouseId > 0) {
                $ssQuery->where('warehouse_id', $warehouseId);
            }
            $ss = $ssQuery->first();
            if (! $ss) {
                return false;
            }
            // cat 2 = keluar/bongkar → kembalikan (+); cat 1 = hasil konversi → kurangi (−)
            if ((int) $log->log_category === 2) {
                $ss->ss_stock = (int) $ss->ss_stock + $qty;
            } elseif ((int) $log->log_category === 1) {
                $ss->ss_stock = (int) $ss->ss_stock - $qty;
            } else {
                return false;
            }
            $ss->save();

            return true;
        }

        if ((int) $log->log_type === 1) {
            $psQuery = ProductStock::withoutGlobalScope('active_warehouse')
                ->where('product_variant_id', $log->log_item_id)
                ->where('unit_id', $log->unit_id)
                ->where('status', 1);
            if ($warehouseId > 0) {
                $psQuery->where('warehouse_id', $warehouseId);
            }
            $ps = $psQuery->first();
            if (! $ps) {
                return false;
            }
            if ((int) $log->log_category === 1) {
                $ps->ps_stock = (int) $ps->ps_stock - $qty;
            } elseif ((int) $log->log_category === 2) {
                $ps->ps_stock = (int) $ps->ps_stock + $qty;
            } else {
                return false;
            }
            $ps->save();

            return true;
        }

        return false;
    }

    /**
     * Log kompensasi supaya riwayat Stok Produk / Bahan Mentah terlihat.
     * Category dibalik: keluar orphan → masuk perbaikan, masuk orphan → keluar perbaikan.
     */
    private function insertCompensationLog(LogStock $original, int $qty, ?int $staffId): void
    {
        $origCat = (int) $original->log_category;
        if (! in_array($origCat, [1, 2], true)) {
            return;
        }

        $compensateCat = $origCat === 2 ? 1 : 2;
        $typeLabel = (int) $original->log_type === 2 ? 'bahan mentah' : 'produk';

        $row = new LogStock();
        $row->log_date = now();
        $row->log_kode = $original->log_kode;
        $row->log_type = $original->log_type;
        $row->log_category = $compensateCat;
        $row->log_item_id = $original->log_item_id;
        $row->log_notes = 'Pengembalian stok (perbaikan ACC pending gagal) '
            . $original->log_kode
            . ' — ' . $typeLabel;
        $row->log_jumlah = $qty;
        $row->unit_id = $original->unit_id;
        if (Schema::hasColumn($row->getTable(), 'warehouse_id')) {
            $row->warehouse_id = $original->warehouse_id;
        }
        $row->status = 1;
        $row->staff_id = $staffId;
        $row->save();
    }
}
