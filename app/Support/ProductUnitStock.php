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
            $sorted->pluck('unit_id')->map(fn ($id) => (int) $id)->all()
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
            'unit_order' => array_map(fn ($u) => (int) $u['unit_id'], $units),
        ];
    }

    /**
     * Total stok tersedia setara di $targetUnitId.
     * Hanya hitung: stok satuan yang sama + bongkar dari satuan LEBIH BESAR (ancestor).
     * Stok satuan lebih kecil tidak dihitung naik (contoh: 173 DOS ≠ Jerigen).
     */
    public static function totalAvailable(int $warehouseId, int $productVariantId, int $targetUnitId): float
    {
        $stocks = self::stocks($warehouseId, $productVariantId);
        if ($stocks->isEmpty()) {
            return 0.0;
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
                fn ($r) => (int) $r->pr_unit_id_1 === (int) $current
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

        foreach ($items as $item) {
            $variantId = (int) ($item['product_variant_id'] ?? 0);
            $unitId = (int) ($item['unit_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0 || $qty <= 0) {
                continue;
            }

            $available = self::totalAvailable($warehouseId, $variantId, $unitId);
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
                fn ($r) => (int) $r->pr_unit_id_1 === (int) $current
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
        string $logNotes = 'Stock Transfer - keluar'
    ): array {
        if ($qty <= 0) {
            return ['ok' => true];
        }

        self::clearCache();
        if (! self::canFulfill($warehouseId, $productVariantId, $unitId, $qty)) {
            return ['ok' => false, 'message' => 'Stok tidak mencukupi'];
        }

        $rows = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $productVariantId)
            ->get();

        if ($rows->isEmpty()) {
            return ['ok' => false, 'message' => 'Stok tidak ditemukan'];
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
                fn ($r) => (int) $r->pr_unit_id_2 === $targetUnitId
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
                'unit_id' => $log['unit_id'],
                'warehouse_id' => $warehouseId,
            ]);
        }

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

        $row = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->first();

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
            ->mapWithKeys(fn ($u) => [
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
            ->flatMap(fn ($r) => [(int) $r->pr_unit_id_1, (int) $r->pr_unit_id_2])
            ->unique()
            ->values();
        $dummy = $unitIds->map(fn ($uid) => (object) ['unit_id' => $uid]);

        return UnitStockSorter::sort($dummy, $relations)
            ->map(fn ($row) => (int) $row->unit_id)
            ->unique()
            ->values()
            ->all();
    }
}
