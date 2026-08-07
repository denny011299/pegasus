<?php

namespace App\Models;

use App\Support\UnitStockSorter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockAlertSupplies extends Model
{
    use HasFactory;
    protected $table = "stock_alerts";
    protected $primaryKey = "stal_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStockAlertSupplies($data = []){
        $data = array_merge([
            "mode"=>1,//1=low stock, 2= out of stock
            "warehouse_id"=>null,
        ], $data);
        $warehouseId = SuppliesStock::resolveWarehouseId($data['warehouse_id'] ?? null);
        if (! $warehouseId) {
            return collect();
        }

        $result = Supplies::where('supplies.status', '=', 1)->orderBy('created_at', 'asc')->get();
        if ($result->isEmpty()) {
            return $result;
        }

        $suppliesIds = $result->pluck('supplies_id')->map(fn ($id) => (int) $id)->all();
        $decodeUnit = static fn ($val) => is_array($val) ? $val : (json_decode($val ?? '[]', true) ?: []);

        $unitIdSet = [];
        foreach ($result as $supply) {
            foreach ($decodeUnit($supply->getAttributes()['supplies_unit'] ?? null) as $unitId) {
                $unitIdSet[(int) $unitId] = true;
            }
            if ($supply->supplies_default_unit) {
                $unitIdSet[(int) $supply->supplies_default_unit] = true;
            }
        }

        $relations = SuppliesRelation::where('status', 1)
            ->whereIn('supplies_id', $suppliesIds)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($relations as $rel) {
            $unitIdSet[(int) $rel->su_id_1] = true;
            $unitIdSet[(int) $rel->su_id_2] = true;
        }

        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        foreach ($relations as $rel) {
            $u1 = $unitsMap->get((int) $rel->su_id_1);
            $u2 = $unitsMap->get((int) $rel->su_id_2);
            if ($u1) {
                $rel->pr_unit_id_1 = $u1->unit_id;
                $rel->pr_unit_name_1 = $u1->unit_short_name;
            }
            if ($u2) {
                $rel->pr_unit_id_2 = $u2->unit_id;
                $rel->pr_unit_name_2 = $u2->unit_short_name;
            }
        }

        $relationsBySupplies = $relations->groupBy('supplies_id');

        $stocks = SuppliesStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->whereIn('supplies_id', $suppliesIds)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('supplies_id');

        $variants = SuppliesVariant::where('supplies_variants.status', 1)
            ->whereIn('supplies_variants.supplies_id', $suppliesIds)
            ->leftJoin('suppliers as sp', 'sp.supplier_id', '=', 'supplies_variants.supplier_id')
            ->select('supplies_variants.*', 'sp.supplier_name')
            ->orderBy('supplies_variants.supplies_variant_id')
            ->get()
            ->groupBy('supplies_id');

        $usageQuery = DB::table('log_stocks as l')
            ->where('l.status', 1)
            ->where('l.log_type', 2)
            ->whereIn('l.log_item_id', $suppliesIds)
            ->whereBetween('l.log_date', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
            ->where(function ($q) {
                $q->where(function ($out) {
                    $out->where('l.log_category', 2)
                        ->where('l.log_notes', 'like', '%Pengurangan bahan untuk produksi%');
                })->orWhere(function ($reversal) {
                    $reversal->where('l.log_category', 1)
                        ->where('l.log_notes', 'like', '%Pengembalian stok bahan akibat pembatalan produksi%');
                });
            });
        if (Schema::hasColumn('log_stocks', 'warehouse_id')) {
            $isMainWarehouse = DB::table('warehouses as w')
                ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
                ->where('w.id', $warehouseId)
                ->where('wt.is_main_warehouse', 1)
                ->exists();
            $usageQuery->where(function ($q) use ($warehouseId, $isMainWarehouse) {
                $q->where('l.warehouse_id', $warehouseId);
                if ($isMainWarehouse) {
                    $q->orWhereNull('l.warehouse_id');
                }
            });
        }
        $usageBySupplies = $usageQuery
            ->select('l.log_item_id', 'l.unit_id')
            ->selectRaw(
                'SUM(CASE WHEN l.log_notes LIKE ? THEN -ABS(l.log_jumlah) ELSE ABS(l.log_jumlah) END) as net_qty',
                ['%Pengembalian stok bahan akibat pembatalan produksi%']
            )
            ->groupBy('l.log_item_id', 'l.unit_id')
            ->get()
            ->groupBy('log_item_id');

        foreach ($result as $value) {
            $unitIds = $decodeUnit($value->getAttributes()['supplies_unit'] ?? null);
            $value->supplies_unit = $unitIds;
            $value->units = collect($unitIds)
                ->map(fn ($id) => $unitsMap->get((int) $id))
                ->filter()
                ->values();
            $value->relation = ($relationsBySupplies->get($value->supplies_id) ?? collect())->values();

            $stockRows = ($stocks->get($value->supplies_id) ?? collect())->map(function ($stockRow) use ($value, $unitsMap) {
                $stockRow->supplies_name = $value->supplies_name;
                $unit = $unitsMap->get((int) $stockRow->unit_id);
                $stockRow->unit_name = $unit->unit_name ?? '';
                $stockRow->unit_short_name = $unit->unit_short_name ?? '';
                return $stockRow;
            });

            if ($value->relation->isNotEmpty()) {
                $stockRows = UnitStockSorter::sort($stockRows, $value->relation);
            }

            $value->stock = $stockRows->values();
            $defaultUnit = $unitsMap->get((int) $value->supplies_default_unit);
            $value->default_unit = $defaultUnit ? $defaultUnit->unit_name : '-';
            $defaultUnitId = (int) $value->supplies_default_unit;
            $relationRows = $value->relation;

            $netUsage = 0.0;
            foreach ($usageBySupplies->get($value->supplies_id, collect()) as $usage) {
                $netUsage += self::convertQty(
                    (float) $usage->net_qty,
                    (int) $usage->unit_id,
                    $defaultUnitId,
                    $relationRows
                );
            }
            $avgDaily = max(0, $netUsage) / 30;

            $currentStock = 0.0;
            foreach ($stockRows as $stockRow) {
                $currentStock += self::convertQty(
                    (float) ($stockRow->ss_stock ?? 0),
                    (int) $stockRow->unit_id,
                    $defaultUnitId,
                    $relationRows
                );
            }

            $leadTime = max(0, (int) ($value->lead_time_days ?? 0));
            $safety = max(0, (int) ($value->safety_stock ?? 0));
            // Strict client spec: rekomendasi = titik pesan ulang saja (tanpa kurangi stok).
            $reorderPoint = (int) ceil(($avgDaily * $leadTime) + $safety);
            $recommended = $reorderPoint;
            $supplierOptions = ($variants->get($value->supplies_id) ?? collect())
                ->map(fn ($variant) => [
                    'supplies_variant_id' => (int) $variant->supplies_variant_id,
                    'variant_name' => $variant->supplies_variant_name,
                    'supplier_id' => $variant->supplier_id ? (int) $variant->supplier_id : null,
                    'supplier_name' => $variant->supplier_name ?: '-',
                    'price' => (float) ($variant->supplies_variant_price ?? 0),
                ])->values();

            $value->warehouse_id = (int) $warehouseId;
            $value->avg_daily = round($avgDaily, 4);
            $value->current_stock = round($currentStock, 4);
            $value->lead_time_days = $leadTime;
            $value->safety_stock = $safety;
            $value->reorder_point = $reorderPoint;
            $value->recommended_order = round($recommended, 4);
            $value->minim_order = round($recommended, 4);
            $value->supplier_options = $supplierOptions;
        }
        
        return $result;
    }

    private static function convertQty(float $qty, int $fromUnitId, int $toUnitId, $relations): float
    {
        if ($fromUnitId === $toUnitId) {
            return $qty;
        }

        $queue = [[$fromUnitId, 1.0]];
        $visited = [];
        while ($queue !== []) {
            [$unitId, $factor] = array_shift($queue);
            if ($unitId === $toUnitId) {
                return $qty * $factor;
            }
            if (isset($visited[$unitId])) {
                continue;
            }
            $visited[$unitId] = true;

            foreach ($relations as $rel) {
                $parent = (int) $rel->su_id_1;
                $child = (int) $rel->su_id_2;
                $value = (float) $rel->sr_value_2;
                if ($value <= 0) {
                    continue;
                }
                if ($unitId === $parent && ! isset($visited[$child])) {
                    $queue[] = [$child, $factor * $value];
                } elseif ($unitId === $child && ! isset($visited[$parent])) {
                    $queue[] = [$parent, $factor / $value];
                }
            }
        }

        return 0.0;
    }
}
