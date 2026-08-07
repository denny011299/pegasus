<?php

namespace App\Models;

use App\Support\ProductUnitStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockAlert extends Model
{
    use HasFactory;
    protected $table = "stock_alerts";
    protected $primaryKey = "stal_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStockAlert($data = [])
    {
        $data = array_merge([
            "mode" => 1, // 1=low stock, 2= out of stock
            "warehouse_id" => null,
        ], $data);

        $warehouseId = ProductStock::resolveWarehouseId($data["warehouse_id"] ?? null);
        if (! $warehouseId) {
            return collect();
        }

        $hasAlertCol = Schema::hasColumn('product_stocks', 'ps_alert_stock');
        $hasVariantSafetyStock = Schema::hasColumn('product_variants', 'safety_stock');
        $hasVariantSafetyUnit = Schema::hasColumn('product_variants', 'safety_unit_id');
        $hasVariantLeadTime = Schema::hasColumn('product_variants', 'lead_time_days');

        // Ambil stok hanya untuk gudang aktif
        $stockRows = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_stocks.status', 1)
            ->where('product_stocks.warehouse_id', $warehouseId)
            ->join('product_variants as pv', 'pv.product_variant_id', '=', 'product_stocks.product_variant_id')
            ->join('products as p', 'p.product_id', '=', 'pv.product_id')
            ->where('pv.status', 1)
            ->where('p.status', 1)
            ->select([
                'product_stocks.*',
                'pv.product_variant_id as pv_id',
                'pv.product_variant_name',
                'pv.product_variant_sku',
                'pv.product_variant_barcode',
                'pv.product_variant_alert',
                $hasVariantSafetyStock ? 'pv.safety_stock' : DB::raw('0 as safety_stock'),
                $hasVariantSafetyUnit ? 'pv.safety_unit_id' : DB::raw('NULL as safety_unit_id'),
                $hasVariantLeadTime ? 'pv.lead_time_days' : DB::raw('0 as lead_time_days'),
                'pv.unit_id as variant_unit_id',
                'pv.product_id as pv_product_id',
                'p.product_name',
                'p.category_id',
            ])
            ->get();

        if ($stockRows->isEmpty()) {
            return collect();
        }

        $variantIds = $stockRows->pluck('product_variant_id')->unique()->values()->all();
        $unitIds = $stockRows->pluck('unit_id')->merge($stockRows->pluck('variant_unit_id'))->unique()->filter()->values()->all();
        $categoryIds = $stockRows->pluck('category_id')->unique()->filter()->values()->all();

        $unitsMap = $unitIds !== []
            ? Unit::whereIn('unit_id', $unitIds)->get()->keyBy('unit_id')
            : collect();
        $categories = $categoryIds !== []
            ? Category::whereIn('category_id', $categoryIds)->pluck('category_name', 'category_id')
            : collect();

        $relationsByVariant = [];
        foreach ($variantIds as $vid) {
            $relationsByVariant[$vid] = (new ProductRelation())->getProductRelation([
                'product_variant_id' => $vid,
            ]);
        }

        $grouped = $stockRows->groupBy('product_variant_id');
        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();
        $salesQuery = DB::table('sales_order_details as sod')
            ->join('sales_orders as so', 'so.so_id', '=', 'sod.so_id')
            ->where('so.status', 2)
            ->where('sod.status', 1)
            ->whereIn('sod.product_variant_id', $variantIds)
            ->whereBetween(DB::raw('COALESCE(so.so_date, so.created_at)'), [$startDate, $endDate]);
        if (Schema::hasColumn('sales_order_details', 'warehouse_id')) {
            $isMainWarehouse = DB::table('warehouses as w')
                ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
                ->where('w.id', $warehouseId)
                ->where('wt.is_main_warehouse', 1)
                ->exists();
            $salesQuery->where(function ($q) use ($warehouseId, $isMainWarehouse) {
                $q->where('sod.warehouse_id', $warehouseId);
                if ($isMainWarehouse) {
                    $q->orWhereNull('sod.warehouse_id');
                }
            });
        }
        $salesByVariant = $salesQuery
            ->select('sod.product_variant_id', 'sod.unit_id')
            ->selectRaw('SUM(sod.sod_qty) as qty')
            ->groupBy('sod.product_variant_id', 'sod.unit_id')
            ->get()
            ->groupBy('product_variant_id');
        $result = collect();

        foreach ($grouped as $variantId => $rows) {
            $first = $rows->first();
            $vid = (int) $variantId;

            // Threshold alert: ps_alert_stock di unit alert (fallback product_variant_alert)
            $alertQty = (int) ($first->product_variant_alert ?? 0);
            $alertUnitId = (int) ($first->variant_unit_id ?? 0);
            if ($hasAlertCol) {
                $alertRow = $rows->first(fn ($r) => (int) ($r->ps_alert_stock ?? 0) > 0);
                if ($alertRow) {
                    $alertQty = (int) $alertRow->ps_alert_stock;
                    $alertUnitId = (int) $alertRow->unit_id;
                }
            }

            $stockList = $rows->map(function ($r) use ($unitsMap) {
                $u = $unitsMap->get($r->unit_id);
                $r->unit_name = $u->unit_name ?? '-';
                $r->unit_short_name = $u->unit_short_name ?? '-';
                $r->ps_stock = (float) ($r->ps_stock ?? 0);

                return $r;
            })->values();

            $relations = $relationsByVariant[$vid] ?? collect();
            if ($relations && count($relations)) {
                $stockList = \App\Support\UnitStockSorter::sort($stockList, $relations);
            }

            $alertUnit = $unitsMap->get($alertUnitId);
            $salesQty = 0.0;
            foreach ($salesByVariant->get($vid, collect()) as $sale) {
                $salesQty += ProductUnitStock::convertQty(
                    (float) $sale->qty,
                    (int) $sale->unit_id,
                    $alertUnitId,
                    $vid
                );
            }

            $currentStock = 0.0;
            foreach ($rows as $stockRow) {
                $currentStock += ProductUnitStock::convertQty(
                    (float) ($stockRow->ps_stock ?? 0),
                    (int) $stockRow->unit_id,
                    $alertUnitId,
                    $vid
                );
            }

            $safetyStock = (float) ($first->safety_stock ?? 0);
            $safetyUnitId = (int) ($first->safety_unit_id ?? $alertUnitId);
            if (Schema::hasColumn('product_stocks', 'ps_safety_stock')) {
                $safetyRow = $rows->first(fn ($r) => (float) ($r->ps_safety_stock ?? 0) > 0);
                if ($safetyRow) {
                    $safetyStock = (float) $safetyRow->ps_safety_stock;
                    $safetyUnitId = (int) $safetyRow->unit_id;
                }
            }
            if ($safetyUnitId !== $alertUnitId) {
                $safetyStock = ProductUnitStock::convertQty($safetyStock, $safetyUnitId, $alertUnitId, $vid);
            }

            $avgDaily = max(0, $salesQty) / 30;
            $leadTimeDays = max(0, (int) ($first->lead_time_days ?? 0));
            // Strict client spec: rekomendasi = titik pesan ulang saja (tanpa kurangi stok).
            $reorderPoint = (int) ceil(($avgDaily * $leadTimeDays) + max(0, $safetyStock));
            $recommendedOrder = $reorderPoint;
            $item = (object) [
                'product_variant_id' => $vid,
                'product_id' => (int) $first->pv_product_id,
                'product_name' => $first->product_name,
                'product_variant_name' => $first->product_variant_name,
                'product_variant_sku' => $first->product_variant_sku,
                'product_variant_barcode' => $first->product_variant_barcode,
                'product_variant_alert' => $alertQty,
                'unit_id' => $alertUnitId,
                'product_unit' => $alertUnit->unit_name ?? ($alertUnit->unit_short_name ?? '-'),
                'category_id' => $first->category_id,
                'product_category' => $categories->get($first->category_id) ?? '-',
                'relation' => $relations,
                'stock' => $stockList,
                'warehouse_id' => (int) $warehouseId,
                'avg_daily' => round($avgDaily, 4),
                'lead_time_days' => $leadTimeDays,
                'safety_stock' => round(max(0, $safetyStock), 4),
                'reorder_point' => $reorderPoint,
                'current_stock' => round($currentStock, 4),
                'recommended_order' => round($recommendedOrder, 4),
                'minim_order' => round($recommendedOrder, 4),
            ];

            $result->push($item);
        }

        return $result->values();
    }
}
