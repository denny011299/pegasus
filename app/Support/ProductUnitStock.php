<?php

namespace App\Support;

use App\Models\LogStock;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Helper BARU untuk cek stok produk per gudang + konversi product_relations.
 * Tidak mengubah ProductionController / StockController yang sudah jalan.
 *
 * Relasi:
 *  - pr_unit_id_1 = satuan atas (lebih besar)
 *  - pr_unit_id_2 = satuan bawah (lebih kecil)
 *  - 1 × unit_1 = pr_unit_value_2 × unit_2
 */
class ProductUnitStock
{
    /** @var array<int, Collection> */
    protected static array $relationsCache = [];

    /** @var array<string, Collection> */
    protected static array $stocksCache = [];

    public static function clearCache(): void
    {
        self::$relationsCache = [];
        self::$stocksCache = [];
    }

    /**
     * Urutan unit_id besar → kecil untuk satu variant.
     *
     * @return array<int, int>
     */
    public static function orderedUnitIds(int $productVariantId): array
    {
        return self::orderedUnitIdsFromRelations(self::relations($productVariantId));
    }

    /**
     * Stok per unit di gudang, sudah terurut besar → kecil + nama unit.
     *
     * @return array{stock_text: string, units: array<int, array>, unit_order: array<int, int>}
     */
    public static function snapshot(int $warehouseId, int $productVariantId): array
    {
        $relations = self::relations($productVariantId);
        $stocks = self::stocks($warehouseId, $productVariantId);

        if ($stocks->isEmpty()) {
            return [
                'stock_text' => '0',
                'units' => [],
                'unit_order' => self::orderedUnitIdsFromRelations($relations),
            ];
        }

        $sorted = UnitStockSorter::sort($stocks, $relations);
        $unitNames = self::unitNames(
            $sorted->pluck('unit_id')->map(fn($id) => (int) $id)->all()
        );

        $units = [];
        $parts = [];
        foreach ($sorted as $s) {
            $uid = (int) $s->unit_id;
            $qty = (float) $s->ps_stock;
            $name = $unitNames[$uid] ?? ($s->unit_name ?? '-');
            $short = $s->unit_short_name ?? $name;
            $units[] = [
                'unit_id' => $uid,
                'unit_name' => $name,
                'unit_short_name' => $short,
                'ps_stock' => $qty,
                'ps_stock_text' => number_format($qty, 0, ',', '.'),
            ];
            $parts[] = number_format($qty, 0, ',', '.') . ' ' . $name;
        }

        return [
            'stock_text' => $parts !== [] ? implode(', ', $parts) : '0',
            'units' => $units,
            'unit_order' => array_map(fn($u) => (int) $u['unit_id'], $units),
        ];
    }

    /**
     * Pilihan satuan kirim sesuai tipe gudang.
     * Gudang utama: semua satuan dalam chain default (multi), konversi ke default saat terima.
     * Gudang eceran: hanya satuan eceran (retail_unit).
     */
    public static function sourceSnapshot(
        int $warehouseId,
        int $productVariantId,
        bool $sourceIsMain,
        int $defaultUnitId,
        int $retailUnitId
    ): array {
        $candidateIds = $sourceIsMain
            ? ($defaultUnitId > 0
                ? self::connectedUnitIds($productVariantId, $defaultUnitId)
                : [])
            : ($retailUnitId > 0 ? [$retailUnitId] : []);

        $stocks = self::stocks($warehouseId, $productVariantId);
        $directByUnit = $stocks
            ->groupBy(fn($row) => (int) $row->unit_id)
            ->map(fn($rows) => (float) $rows->sum('ps_stock'));
        $unitRows = $candidateIds === []
            ? collect()
            : Unit::query()
            ->whereIn('unit_id', $candidateIds)
            ->get(['unit_id', 'unit_name', 'unit_short_name'])
            ->keyBy('unit_id');

        $units = [];
        foreach ($candidateIds as $unitId) {
            $unit = $unitRows->get($unitId);
            if (! $unit) {
                continue;
            }

            $direct = (float) ($directByUnit->get($unitId) ?? 0);
            // Packing dua arah hanya untuk gudang utama multi-satuan.
            $available = self::totalAvailable(
                $warehouseId,
                $productVariantId,
                $unitId,
                $sourceIsMain
            );
            $units[] = [
                'unit_id' => (int) $unitId,
                'unit_name' => (string) ($unit->unit_name ?: $unit->unit_short_name ?: '-'),
                'unit_short_name' => (string) ($unit->unit_short_name ?: $unit->unit_name ?: '-'),
                'ps_stock' => $direct,
                'ps_stock_text' => number_format($direct, 0, ',', '.'),
                'available_qty' => $available,
            ];
        }

        return [
            'stock_text' => $units === []
                ? '0'
                : collect($units)->map(fn($unit) => number_format(
                    (float) $unit['available_qty'],
                    0,
                    ',',
                    '.'
                ) . ' ' . $unit['unit_name'])->implode(', '),
            'units' => $units,
            'unit_order' => array_map(fn($unit) => (int) $unit['unit_id'], $units),
        ];
    }

    /**
     * Total stok tersedia setara di $targetUnitId.
     * Mode normal: unit sama + bongkar ancestor. Mode packing: gabungkan seluruh
     * stok satu chain dan floor ke unit target.
     */
    public static function totalAvailable(
        int $warehouseId,
        int $productVariantId,
        int $targetUnitId,
        bool $allowPacking = false
    ): float {
        $stocks = self::stocks($warehouseId, $productVariantId);
        if ($stocks->isEmpty()) {
            return 0.0;
        }

        if ($allowPacking) {
            $stockByUnit = $stocks
                ->groupBy(fn($row) => (int) $row->unit_id)
                ->map(fn($rows) => (float) $rows->sum('ps_stock'))
                ->all();

            return (float) self::packingPlan(
                $stockByUnit,
                $productVariantId,
                $targetUnitId,
                PHP_INT_MAX
            )['available'];
        }

        $total = 0.0;
        foreach ($stocks as $s) {
            $fromUnitId = (int) $s->unit_id;
            $qty = (float) $s->ps_stock;
            if ($qty <= 0) {
                continue;
            }

            if ($fromUnitId === $targetUnitId) {
                $total += $qty;
                continue;
            }

            // Hanya ancestor (lebih besar) yang bisa dibongkar ke target
            if (self::isAncestorUnit($fromUnitId, $targetUnitId, $productVariantId)) {
                $total += self::convertQty($qty, $fromUnitId, $targetUnitId, $productVariantId);
            }
        }

        return $total;
    }

    /**
     * Apakah $ancestorUnitId ada di atas $descendantUnitId di chain product_relations?
     * (bisa dibongkar: ancestor → … → descendant)
     */
    public static function isAncestorUnit(
        int $ancestorUnitId,
        int $descendantUnitId,
        int $productVariantId
    ): bool {
        if ($ancestorUnitId === $descendantUnitId || $ancestorUnitId <= 0 || $descendantUnitId <= 0) {
            return false;
        }

        $relations = self::relations($productVariantId);
        $current = $ancestorUnitId;
        $guard = 0;

        while ($guard < 20) {
            $guard++;
            $rel = $relations->first(
                fn($r) => (int) $r->pr_unit_id_1 === (int) $current
            );
            if (! $rel) {
                return false;
            }
            $child = (int) $rel->pr_unit_id_2;
            if ($child === $descendantUnitId) {
                return true;
            }
            $current = $child;
        }

        return false;
    }

    public static function canFulfill(
        int $warehouseId,
        int $productVariantId,
        int $unitId,
        float $qty
    ): bool {
        if ($qty <= 0) {
            return true;
        }

        return self::totalAvailable($warehouseId, $productVariantId, $unitId) + 1e-9 >= $qty;
    }

    /**
     * Cek banyak item sekaligus.
     *
     * @param  array<int, array{product_variant_id:int, unit_id:int, qty:float|int, label?:string}>  $items
     * @return array{ok: bool, shortages: array<int, array>}
     */
    public static function checkItems(int $warehouseId, array $items): array
    {
        $shortages = [];
        $packingPools = [];

        foreach ($items as $item) {
            $variantId = (int) ($item['product_variant_id'] ?? 0);
            $unitId = (int) ($item['unit_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0 || $qty <= 0) {
                continue;
            }

            if ((bool) ($item['allow_packing'] ?? false)) {
                if (! isset($packingPools[$variantId])) {
                    $totalSmallest = 0.0;
                    foreach (self::stocks($warehouseId, $variantId) as $stock) {
                        $stockUnitId = (int) $stock->unit_id;
                        if (! self::canConvertUnits($stockUnitId, $unitId, $variantId)) {
                            continue;
                        }
                        $totalSmallest += max(0.0, (float) $stock->ps_stock)
                            * self::toSmallestMultiplier($stockUnitId, $variantId);
                    }
                    $packingPools[$variantId] = [
                        'total_smallest' => $totalSmallest,
                        'used_smallest' => 0.0,
                    ];
                }

                $targetMultiplier = self::toSmallestMultiplier($unitId, $variantId);
                $remainingSmallest = max(
                    0.0,
                    $packingPools[$variantId]['total_smallest']
                        - $packingPools[$variantId]['used_smallest']
                );
                $available = $targetMultiplier > 0
                    ? (float) floor(($remainingSmallest + 1e-9) / $targetMultiplier)
                    : 0.0;
                if ($available + 1e-9 >= $qty) {
                    $packingPools[$variantId]['used_smallest'] += $qty * $targetMultiplier;
                }
            } else {
                $available = self::totalAvailable($warehouseId, $variantId, $unitId);
            }
            if ($available + 1e-9 < $qty) {
                $shortages[] = [
                    'product_variant_id' => $variantId,
                    'unit_id' => $unitId,
                    'qty' => $qty,
                    'available' => $available,
                    'label' => $item['label'] ?? ('Variant #' . $variantId),
                ];
            }
        }

        return [
            'ok' => $shortages === [],
            'shortages' => $shortages,
        ];
    }

    /**
     * Konversi qty dari $fromUnitId ke $toUnitId via satuan terkecil di chain.
     * Jika unit tidak se-chain, kembalikan 0 (jangan anggap 1:1).
     */
    public static function convertQty(
        float $qty,
        int $fromUnitId,
        int $toUnitId,
        int $productVariantId
    ): float {
        if ($fromUnitId === $toUnitId) {
            return $qty;
        }

        $sameChain = self::isAncestorUnit($fromUnitId, $toUnitId, $productVariantId)
            || self::isAncestorUnit($toUnitId, $fromUnitId, $productVariantId);
        if (! $sameChain) {
            return 0.0;
        }

        $fromSmallest = self::toSmallestMultiplier($fromUnitId, $productVariantId);
        $toSmallest = self::toSmallestMultiplier($toUnitId, $productVariantId);
        if ($toSmallest <= 0) {
            return 0.0;
        }

        return ($qty * $fromSmallest) / $toSmallest;
    }

    public static function canConvertUnits(
        int $fromUnitId,
        int $toUnitId,
        int $productVariantId
    ): bool {
        if ($fromUnitId <= 0 || $toUnitId <= 0 || $productVariantId <= 0) {
            return false;
        }

        return $fromUnitId === $toUnitId
            || self::isAncestorUnit($fromUnitId, $toUnitId, $productVariantId)
            || self::isAncestorUnit($toUnitId, $fromUnitId, $productVariantId);
    }

    /**
     * Multiplier: 1 unit X = N unit terkecil (ikuti chain parent→child).
     */
    public static function toSmallestMultiplier(int $unitId, int $productVariantId): float
    {
        $relations = self::relations($productVariantId);
        $multiplier = 1.0;
        $current = $unitId;
        $guard = 0;

        while ($guard < 20) {
            $guard++;
            $rel = $relations->first(
                fn($r) => (int) $r->pr_unit_id_1 === (int) $current
            );
            if (! $rel) {
                break;
            }
            $value = (float) $rel->pr_unit_value_2;
            if ($value <= 0) {
                break;
            }
            $multiplier *= $value;
            $current = (int) $rel->pr_unit_id_2;
        }

        return $multiplier;
    }

    /**
     * Seluruh unit pada chain linear yang sama, terurut besar → kecil.
     *
     * @return array<int, int>
     */
    protected static function connectedUnitIds(int $productVariantId, int $anchorUnitId): array
    {
        if ($anchorUnitId <= 0) {
            return [];
        }

        $ordered = self::orderedUnitIds($productVariantId);
        if (! in_array($anchorUnitId, $ordered, true)) {
            return [];
        }

        return array_values(array_filter(
            $ordered,
            fn($unitId) => self::canConvertUnits(
                (int) $unitId,
                $anchorUnitId,
                $productVariantId
            )
        ));
    }

    protected static function relations(int $productVariantId): Collection
    {
        if (! isset(self::$relationsCache[$productVariantId])) {
            self::$relationsCache[$productVariantId] = ProductRelation::query()
                ->where('status', 1)
                ->where('product_variant_id', $productVariantId)
                ->get([
                    'pr_id',
                    'product_variant_id',
                    'pr_unit_id_1',
                    'pr_unit_value_1',
                    'pr_unit_id_2',
                    'pr_unit_value_2',
                ]);
        }

        return self::$relationsCache[$productVariantId];
    }

    protected static function stocks(int $warehouseId, int $productVariantId): Collection
    {
        $key = $warehouseId . ':' . $productVariantId;
        if (! isset(self::$stocksCache[$key])) {
            if ($warehouseId <= 0) {
                self::$stocksCache[$key] = collect();
            } else {
                self::$stocksCache[$key] = ProductStock::withoutGlobalScope('active_warehouse')
                    ->where('status', 1)
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $productVariantId)
                    ->get(['ps_id', 'product_variant_id', 'unit_id', 'ps_stock', 'warehouse_id']);
            }
        }

        return self::$stocksCache[$key];
    }

    /**
     * Potong stok di gudang: satuan sama dulu, kurang → bongkar dari satuan lebih besar.
     * Persist + tulis LogStock (type 1 product).
     *
     * @return array{ok: bool, message?: string}
     */
    public static function deductQty(
        int $warehouseId,
        int $productVariantId,
        int $unitId,
        float $qty,
        string $logCode,
        string $logNotes = 'Stock Transfer - keluar',
        bool $allowPacking = false
    ): array {
        if ($qty <= 0) {
            return ['ok' => true];
        }

        $rows = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $productVariantId)
            ->lockForUpdate()
            ->get();

        if ($rows->isEmpty() && ! $allowPacking) {
            return ['ok' => false, 'message' => 'Stok tidak ditemukan'];
        }

        if ($allowPacking) {
            return self::deductPackedQty(
                $rows,
                $warehouseId,
                $productVariantId,
                $unitId,
                $qty,
                $logCode,
                $logNotes
            );
        }

        /** @var array<int, \App\Models\ProductStock> $byUnit */
        $byUnit = [];
        /** @var array<int, float> $virtual */
        $virtual = [];
        foreach ($rows as $row) {
            $uid = (int) $row->unit_id;
            $byUnit[$uid] = $row;
            $virtual[$uid] = (float) $row->ps_stock;
        }

        if (! isset($byUnit[$unitId])) {
            return ['ok' => false, 'message' => 'Satuan stok tidak ditemukan di gudang'];
        }

        $relations = self::relations($productVariantId);
        /** @var list<array{unit_id:int, qty:float, category:int, note:string}> $logs */
        $logs = [];

        $ensure = function (int $targetUnitId, float $need) use (
            &$ensure,
            &$virtual,
            &$logs,
            $relations,
            $byUnit
        ): bool {
            if (($virtual[$targetUnitId] ?? 0) + 1e-9 >= $need) {
                return true;
            }

            $rel = $relations->first(
                fn($r) => (int) $r->pr_unit_id_2 === $targetUnitId
            );
            if (! $rel) {
                return false;
            }

            $parentId = (int) $rel->pr_unit_id_1;
            $factor = (float) $rel->pr_unit_value_2;
            if ($factor <= 0 || ! isset($byUnit[$parentId])) {
                return false;
            }

            $shortage = $need - ($virtual[$targetUnitId] ?? 0);
            $needParent = (int) ceil($shortage / $factor - 1e-9);
            if ($needParent < 1) {
                $needParent = 1;
            }

            if (($virtual[$parentId] ?? 0) + 1e-9 < $needParent) {
                if (! $ensure($parentId, (float) $needParent)) {
                    return false;
                }
            }

            $takeParent = min($needParent, (int) floor(($virtual[$parentId] ?? 0) + 1e-9));
            if ($takeParent <= 0) {
                return false;
            }

            $virtual[$parentId] = ($virtual[$parentId] ?? 0) - $takeParent;
            $got = $takeParent * $factor;
            $virtual[$targetUnitId] = ($virtual[$targetUnitId] ?? 0) + $got;

            $logs[] = [
                'unit_id' => $parentId,
                'qty' => (float) $takeParent,
                'category' => 2,
                'note' => 'Stock Transfer - bongkar satuan',
            ];
            $logs[] = [
                'unit_id' => $targetUnitId,
                'qty' => (float) $got,
                'category' => 1,
                'note' => 'Stock Transfer - hasil bongkar',
            ];

            return ($virtual[$targetUnitId] ?? 0) + 1e-9 >= $need;
        };

        if (($virtual[$unitId] ?? 0) + 1e-9 < $qty) {
            if (! $ensure($unitId, $qty)) {
                return ['ok' => false, 'message' => 'Stok tidak mencukupi setelah konversi'];
            }
        }

        $virtual[$unitId] = ($virtual[$unitId] ?? 0) - $qty;
        $logs[] = [
            'unit_id' => $unitId,
            'qty' => $qty,
            'category' => 2,
            'note' => $logNotes,
        ];

        foreach ($byUnit as $uid => $row) {
            $newQty = round($virtual[$uid] ?? 0, 4);
            if (abs((float) $row->ps_stock - $newQty) < 1e-9) {
                continue;
            }
            $row->ps_stock = $newQty;
            $row->save();
        }

        $logger = new LogStock();
        foreach ($logs as $log) {
            if ($log['qty'] <= 0) {
                continue;
            }
            $logger->insertLog([
                'log_date' => now(),
                'log_kode' => $logCode,
                'log_type' => 1,
                'log_category' => $log['category'],
                'log_item_id' => $productVariantId,
                'log_notes' => $log['note'],
                'log_jumlah' => $log['qty'],
                'log_saldo' => round((float) ($virtual[$log['unit_id']] ?? 0), 4),
                'unit_id' => $log['unit_id'],
                'warehouse_id' => $warehouseId,
            ]);
        }

        self::clearCache();

        return ['ok' => true];
    }

    /**
     * Hitung packing dalam unit terkecil, lalu susun sisa secara greedy besar → kecil.
     *
     * @param  array<int, float|int>  $stockByUnit
     * @return array{ok:bool,available:int,before:array<int,float>,after:array<int,float>}
     */
    public static function packingPlan(
        array $stockByUnit,
        int $productVariantId,
        int $targetUnitId,
        int $requestedQty
    ): array {
        $unitIds = self::connectedUnitIds($productVariantId, $targetUnitId);
        if ($unitIds === [] || ! in_array($targetUnitId, $unitIds, true)) {
            return ['ok' => false, 'available' => 0, 'before' => [], 'after' => []];
        }

        $multipliers = [];
        foreach ($unitIds as $unitId) {
            $multiplier = self::toSmallestMultiplier($unitId, $productVariantId);
            if ($multiplier <= 0) {
                continue;
            }
            $multipliers[$unitId] = $multiplier;
        }

        return self::packingPlanFromMultipliers(
            $stockByUnit,
            $multipliers,
            $targetUnitId,
            $requestedQty
        );
    }

    /**
     * Pure calculation entry point untuk verifikasi packing tanpa akses database.
     *
     * @param  array<int, float|int>  $stockByUnit
     * @param  array<int, float|int>  $multipliers  1 unit = N unit dasar
     * @return array{ok:bool,available:int,before:array<int,float>,after:array<int,float>}
     */
    public static function packingPlanFromMultipliers(
        array $stockByUnit,
        array $multipliers,
        int $targetUnitId,
        int $requestedQty
    ): array {
        $totalSmallest = 0.0;
        $before = [];
        foreach ($multipliers as $unitId => $multiplier) {
            $multiplier = (float) $multiplier;
            if ($multiplier <= 0) {
                continue;
            }
            $qty = max(0.0, (float) ($stockByUnit[$unitId] ?? 0));
            $before[(int) $unitId] = $qty;
            $totalSmallest += $qty * $multiplier;
        }

        $targetMultiplier = (float) ($multipliers[$targetUnitId] ?? 0);
        if ($targetMultiplier <= 0) {
            return ['ok' => false, 'available' => 0, 'before' => $before, 'after' => []];
        }

        $available = (int) floor(($totalSmallest + 1e-9) / $targetMultiplier);
        if ($requestedQty > $available) {
            return ['ok' => false, 'available' => $available, 'before' => $before, 'after' => $before];
        }

        $remaining = $totalSmallest - ($requestedQty * $targetMultiplier);
        $after = [];
        foreach ($multipliers as $unitId => $multiplier) {
            $multiplier = (float) $multiplier;
            if ($multiplier <= 0) {
                continue;
            }
            $packed = (float) floor(($remaining + 1e-9) / $multiplier);
            $after[(int) $unitId] = $packed;
            $remaining -= $packed * $multiplier;
        }

        return ['ok' => true, 'available' => $available, 'before' => $before, 'after' => $after];
    }

    protected static function deductPackedQty(
        Collection $rows,
        int $warehouseId,
        int $productVariantId,
        int $unitId,
        float $qty,
        string $logCode,
        string $logNotes
    ): array {
        if (abs($qty - round($qty)) > 1e-9) {
            return ['ok' => false, 'message' => 'Qty kirim harus berupa bilangan bulat'];
        }

        $byUnitRows = $rows->groupBy(fn($row) => (int) $row->unit_id);
        $stockByUnit = $byUnitRows
            ->map(fn($unitRows) => (float) $unitRows->sum('ps_stock'))
            ->all();
        $plan = self::packingPlan(
            $stockByUnit,
            $productVariantId,
            $unitId,
            (int) round($qty)
        );
        if (! $plan['ok']) {
            return [
                'ok' => false,
                'message' => 'Stok tidak cukup. Tersedia: ' . $plan['available'],
            ];
        }

        $productId = (int) ($rows->first()?->product_id ?? 0);
        $createdBy = Session::get('user')->staff_id ?? null;
        $allUnitIds = array_values(array_unique(array_merge(
            array_keys($plan['before']),
            array_keys($plan['after']),
            [$unitId]
        )));

        foreach ($allUnitIds as $currentUnitId) {
            $unitRows = $byUnitRows->get($currentUnitId, collect());
            $targetQty = round((float) ($plan['after'][$currentUnitId] ?? 0), 4);
            $primary = $unitRows->first();
            if (! $primary) {
                $primary = ProductStock::withoutGlobalScope('active_warehouse')->create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'product_variant_id' => $productVariantId,
                    'unit_id' => $currentUnitId,
                    'ps_stock' => $targetQty,
                    'status' => 1,
                    'created_by' => $createdBy,
                ]);
            } else {
                $primary->ps_stock = $targetQty;
                $primary->save();
                $unitRows->skip(1)->each(function ($duplicate) {
                    $duplicate->ps_stock = 0;
                    $duplicate->save();
                });
            }
        }

        $targetMultiplier = self::toSmallestMultiplier($unitId, $productVariantId);
        $canonicalBefore = self::packingPlan(
            $stockByUnit,
            $productVariantId,
            $unitId,
            0
        )['after'];
        $logger = new LogStock();
        foreach ($allUnitIds as $currentUnitId) {
            $before = (float) ($plan['before'][$currentUnitId] ?? 0);
            $packed = (float) ($canonicalBefore[$currentUnitId] ?? 0);
            $delta = $packed - $before;
            if (abs($delta) < 1e-9) {
                continue;
            }
            $logger->insertLog([
                'log_date' => now(),
                'log_kode' => $logCode,
                'log_type' => 1,
                'log_category' => $delta > 0 ? 1 : 2,
                'log_item_id' => $productVariantId,
                'log_notes' => $delta > 0
                    ? 'Stock Transfer - hasil packing satuan'
                    : 'Stock Transfer - bahan packing satuan',
                'log_jumlah' => abs($delta),
                'log_saldo' => round((float) ($plan['after'][$currentUnitId] ?? 0), 4),
                'unit_id' => $currentUnitId,
                'warehouse_id' => $warehouseId,
            ]);
        }
        $logger->insertLog([
            'log_date' => now(),
            'log_kode' => $logCode,
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $productVariantId,
            'log_notes' => $logNotes . ' (packing '
                . number_format($qty * $targetMultiplier, 0, ',', '.')
                . ' unit dasar)',
            'log_jumlah' => $qty,
            'log_saldo' => round((float) ($plan['after'][$unitId] ?? 0), 4),
            'unit_id' => $unitId,
            'warehouse_id' => $warehouseId,
        ]);

        self::clearCache();

        return ['ok' => true];
    }

    /**
     * Tambah stok di gudang (satuan yang sama). Buat baris stok jika belum ada.
     *
     * @return array{ok: bool, message?: string}
     */
    public static function addQty(
        int $warehouseId,
        int $productId,
        int $productVariantId,
        int $unitId,
        float $qty,
        string $logCode,
        string $logNotes = 'Stock Transfer - masuk'
    ): array {
        if ($qty <= 0) {
            return ['ok' => true];
        }

        $rows = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->lockForUpdate()
            ->get();
        $row = $rows->first();

        if (! $row) {
            $row = new ProductStock();
            $row->warehouse_id = $warehouseId;
            $row->product_id = $productId;
            $row->product_variant_id = $productVariantId;
            $row->unit_id = $unitId;
            $row->ps_stock = 0;
            $row->status = 1;
            $row->created_by = Session::get('user')->staff_id ?? null;
            $row->save();
        } else {
            $row->ps_stock = (float) $rows->sum('ps_stock');
            $rows->skip(1)->each(function ($duplicate) {
                $duplicate->ps_stock = 0;
                $duplicate->save();
            });
        }

        $row->ps_stock = round((float) $row->ps_stock + $qty, 4);
        $row->save();

        (new LogStock())->insertLog([
            'log_date' => now(),
            'log_kode' => $logCode,
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $productVariantId,
            'log_notes' => $logNotes,
            'log_jumlah' => $qty,
            'log_saldo' => (float) $row->ps_stock,
            'unit_id' => $unitId,
            'warehouse_id' => $warehouseId,
        ]);

        self::clearCache();

        return ['ok' => true];
    }

    /**
     * @param  array<int, int>  $unitIds
     * @return array<int, string>
     */
    protected static function unitNames(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        return Unit::query()
            ->whereIn('unit_id', $unitIds)
            ->get(['unit_id', 'unit_name', 'unit_short_name'])
            ->mapWithKeys(fn($u) => [
                (int) $u->unit_id => (string) ($u->unit_name ?: $u->unit_short_name ?: '-'),
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    protected static function orderedUnitIdsFromRelations(Collection $relations): array
    {
        if ($relations->isEmpty()) {
            return [];
        }
        $unitIds = $relations
            ->flatMap(fn($r) => [(int) $r->pr_unit_id_1, (int) $r->pr_unit_id_2])
            ->unique()
            ->values();
        $dummy = $unitIds->map(fn($uid) => (object) ['unit_id' => $uid]);

        return UnitStockSorter::sort($dummy, $relations)
            ->map(fn($row) => (int) $row->unit_id)
            ->unique()
            ->values()
            ->all();
    }
}
