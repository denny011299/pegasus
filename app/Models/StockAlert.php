<?php

namespace App\Models;

use App\Support\ProductUnitStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $hasMinOrderCol = Schema::hasColumn('product_stocks', 'ps_min_order');
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
                Schema::hasColumn('product_variants', 'retail_unit')
                    ? 'pv.retail_unit'
                    : DB::raw('NULL as retail_unit'),
                'pv.product_id as pv_product_id',
                'p.product_name',
                'p.category_id',
            ])
            ->get();

        if ($stockRows->isEmpty()) {
            return collect();
        }

        $isEceranWarehouse = ! DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.id', $warehouseId)
            ->where('wt.is_main_warehouse', 1)
            ->exists();

        $variantIds = $stockRows->pluck('product_variant_id')->unique()->values()->all();
        $unitIds = $stockRows->pluck('unit_id')
            ->merge($stockRows->pluck('variant_unit_id'))
            ->merge($stockRows->pluck('retail_unit'))
            ->unique()
            ->filter()
            ->values()
            ->all();
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
            $salesQuery->where(function ($q) use ($warehouseId, $isEceranWarehouse) {
                $q->where('sod.warehouse_id', $warehouseId);
                if (! $isEceranWarehouse) {
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
            $retailUnitId = (int) ($first->retail_unit ?? 0);
            $variantUnitId = (int) ($first->variant_unit_id ?? 0);

            // Threshold alert: ps_alert_stock di unit alert (fallback product_variant_alert @ variant unit)
            $alertQty = (float) ($first->product_variant_alert ?? 0);
            $alertUnitId = $variantUnitId > 0 ? $variantUnitId : 0;
            if ($hasAlertCol) {
                $alertRow = $rows->first(fn ($r) => (int) ($r->ps_alert_stock ?? 0) > 0);
                if ($alertRow) {
                    $alertQty = (float) $alertRow->ps_alert_stock;
                    $alertUnitId = (int) $alertRow->unit_id;
                }
            }

            // Gudang eceran: tampil satuan eceran. Gudang utama: satuan default varian.
            $displayUnitId = ($isEceranWarehouse && $retailUnitId > 0)
                ? $retailUnitId
                : ($variantUnitId > 0 ? $variantUnitId : ($alertUnitId > 0 ? $alertUnitId : $retailUnitId));
            if ($displayUnitId <= 0) {
                continue;
            }

            if ($alertUnitId > 0 && $alertUnitId !== $displayUnitId) {
                $alertQty = ProductUnitStock::convertQty($alertQty, $alertUnitId, $displayUnitId, $vid);
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

            // Stok Saat Ini = qty baris satuan tampilan, tanpa konversi sisa unit lain (hindari 53.33 DOS).
            $currentStock = 0.0;
            foreach ($rows as $stockRow) {
                if ((int) $stockRow->unit_id === $displayUnitId) {
                    $currentStock += (float) ($stockRow->ps_stock ?? 0);
                }
            }

            $displayUnit = $unitsMap->get($displayUnitId);
            $salesQty = 0.0;
            foreach ($salesByVariant->get($vid, collect()) as $sale) {
                $salesQty += ProductUnitStock::convertQty(
                    (float) $sale->qty,
                    (int) $sale->unit_id,
                    $displayUnitId,
                    $vid
                );
            }

            $safetyStock = (float) ($first->safety_stock ?? 0);
            $safetyUnitId = (int) ($first->safety_unit_id ?? $variantUnitId);
            if (Schema::hasColumn('product_stocks', 'ps_safety_stock')) {
                $safetyRow = $rows->first(fn ($r) => (float) ($r->ps_safety_stock ?? 0) > 0);
                if ($safetyRow) {
                    $safetyStock = (float) $safetyRow->ps_safety_stock;
                    $safetyUnitId = (int) $safetyRow->unit_id;
                }
            }
            if ($safetyUnitId > 0 && $safetyUnitId !== $displayUnitId) {
                $safetyStock = ProductUnitStock::convertQty($safetyStock, $safetyUnitId, $displayUnitId, $vid);
            }

            $avgDaily = max(0, $salesQty) / 30;
            $leadTimeDays = max(0, (int) ($first->lead_time_days ?? 0));
            // Strict client spec: rekomendasi = titik pesan ulang saja (tanpa kurangi stok).
            $reorderPoint = (int) ceil(($avgDaily * $leadTimeDays) + max(0, $safetyStock));
            $recommendedOrder = $reorderPoint;
            $storedMinOrder = null;
            if ($hasMinOrderCol) {
                $minOrderRow = $rows->first(fn ($r) => $r->ps_min_order !== null);
                if ($minOrderRow) {
                    $minStored = (float) $minOrderRow->ps_min_order;
                    $minUnitId = (int) $minOrderRow->unit_id;
                    if ($minUnitId > 0 && $minUnitId !== $displayUnitId) {
                        $minStored = ProductUnitStock::convertQty($minStored, $minUnitId, $displayUnitId, $vid);
                    }
                    $storedMinOrder = (int) round($minStored);
                }
            }

            // Ada ps_min_order → (ps_min_order − stok); else (peringatan stok − stok)
            $orderThreshold = $storedMinOrder !== null ? $storedMinOrder : $alertQty;
            $calculatedMinOrder = (int) max(0, round($orderThreshold - $currentStock));

            $item = (object) [
                'product_variant_id' => $vid,
                'product_id' => (int) $first->pv_product_id,
                'product_name' => $first->product_name,
                'product_variant_name' => $first->product_variant_name,
                'product_variant_sku' => $first->product_variant_sku,
                'product_variant_barcode' => $first->product_variant_barcode,
                'product_variant_alert' => round($alertQty, 4),
                'unit_id' => $displayUnitId,
                'product_unit' => $displayUnit->unit_name ?? ($displayUnit->unit_short_name ?? '-'),
                'retail_unit_id' => $retailUnitId > 0 ? $retailUnitId : null,
                'is_eceran_warehouse' => $isEceranWarehouse,
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
                // Pemesanan Min. tampil = max(0, threshold − stok); threshold = ps_min_order ?? alert
                'minim_order' => $calculatedMinOrder,
                'min_order' => (int) round($orderThreshold),
                'min_order_manual' => $storedMinOrder,
                'min_order_is_manual' => $storedMinOrder !== null,
            ];

            $result->push($item);
        }

        return $result->values();
    }

    /**
     * Update stok alert (ps_alert_stock) untuk satu produk/varian pada gudang tertentu.
     * Gudang eceran: simpan di baris retail_unit (nilai UI = eceran).
     * Input: product_id, product_variant_id, alert_stock, alert_unit_id, warehouse_id
     */
    function updateStockAlert($data = [])
    {
        $variantId   = (int) ($data['product_variant_id'] ?? 0);
        $alertStock  = isset($data['alert_stock']) && $data['alert_stock'] !== '' ? (int) $data['alert_stock'] : 0;
        $alertUnitId = (int) ($data['alert_unit_id'] ?? 0);
        $warehouseId = ProductStock::resolveWarehouseId($data['warehouse_id'] ?? null);

        if ($variantId <= 0 || $warehouseId <= 0) {
            return response()->json(['success' => false, 'message' => 'Data tidak lengkap (variant/warehouse)']);
        }

        $variant = ProductVariant::where('product_variant_id', $variantId)->first();
        $variantUnitId = (int) ($variant->unit_id ?? 0);
        $retailUnitId = Schema::hasColumn('product_variants', 'retail_unit')
            ? (int) ($variant->retail_unit ?? 0)
            : 0;

        $isEceranWarehouse = ! DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.id', $warehouseId)
            ->where('wt.is_main_warehouse', 1)
            ->exists();

        if ($isEceranWarehouse && $retailUnitId > 0) {
            $alertUnitId = $retailUnitId;
        } elseif ($alertUnitId <= 0) {
            $alertUnitId = $variantUnitId;
        }
        if ($alertUnitId <= 0) {
            $alertUnitId = (int) ProductStock::withoutGlobalScope('active_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->where('status', 1)
                ->value('unit_id');
        }

        $hasAlertCol = Schema::hasColumn('product_stocks', 'ps_alert_stock');

        if ($hasAlertCol && $alertUnitId > 0) {
            ProductStock::withoutGlobalScope('active_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->where('status', 1)
                ->update(['ps_alert_stock' => 0]);

            $affected = ProductStock::withoutGlobalScope('active_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->where('unit_id', $alertUnitId)
                ->where('status', 1)
                ->update(['ps_alert_stock' => $alertStock]);

            // Pastikan baris retail ada di gudang eceran
            if ($affected === 0 && $isEceranWarehouse && $retailUnitId > 0) {
                $productId = (int) ($variant->product_id ?? ($data['product_id'] ?? 0));
                if ($productId > 0) {
                    DB::table('product_stocks')->insert([
                        'product_variant_id' => $variantId,
                        'product_id' => $productId,
                        'unit_id' => $retailUnitId,
                        'warehouse_id' => $warehouseId,
                        'ps_stock' => 0,
                        'ps_alert_stock' => $alertStock,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Log::info('[updateStockAlert] ps_alert_stock updated', [
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'unit_id' => $alertUnitId,
                'alert_stock' => $alertStock,
                'is_eceran' => $isEceranWarehouse,
            ]);
        }

        // product_variant_alert disimpan dalam variant unit (konversi dari eceran jika perlu)
        $variantAlertQty = $alertStock;
        if ($isEceranWarehouse && $retailUnitId > 0 && $variantUnitId > 0 && $retailUnitId !== $variantUnitId) {
            $variantAlertQty = (int) round(ProductUnitStock::convertQty(
                (float) $alertStock,
                $retailUnitId,
                $variantUnitId,
                $variantId
            ));
        }
        ProductVariant::where('product_variant_id', $variantId)
            ->update(['product_variant_alert' => $variantAlertQty]);

        return response()->json(['success' => true, 'message' => 'Peringatan stok berhasil diperbarui']);
    }

    /**
     * Update dasar pemesanan min (ps_min_order). Tampil di UI = max(0, nilai ini − stok).
     * Jika null, UI memakai peringatan stok − stok.
     * Gudang eceran: simpan di baris retail_unit.
     * Input: product_variant_id, min_order, min_order_unit_id, warehouse_id
     */
    function updateMinOrder($data = [])
    {
        $variantId = (int) ($data['product_variant_id'] ?? 0);
        $minOrder = isset($data['min_order']) && $data['min_order'] !== '' ? (int) $data['min_order'] : null;
        $minOrderUnitId = (int) ($data['min_order_unit_id'] ?? 0);
        $warehouseId = ProductStock::resolveWarehouseId($data['warehouse_id'] ?? null);

        if ($variantId <= 0 || $warehouseId <= 0) {
            return response()->json(['success' => false, 'message' => 'Data tidak lengkap (variant/warehouse)']);
        }

        if ($minOrder !== null && $minOrder < 0) {
            return response()->json(['success' => false, 'message' => 'Pemesanan minimum tidak valid']);
        }

        if (! Schema::hasColumn('product_stocks', 'ps_min_order')) {
            return response()->json(['success' => false, 'message' => 'Kolom ps_min_order belum tersedia']);
        }

        $variant = ProductVariant::where('product_variant_id', $variantId)->first();
        $retailUnitId = Schema::hasColumn('product_variants', 'retail_unit')
            ? (int) ($variant->retail_unit ?? 0)
            : 0;
        $isEceranWarehouse = ! DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.id', $warehouseId)
            ->where('wt.is_main_warehouse', 1)
            ->exists();

        if ($isEceranWarehouse && $retailUnitId > 0) {
            $minOrderUnitId = $retailUnitId;
        } elseif ($minOrderUnitId <= 0) {
            $minOrderUnitId = (int) ($variant->unit_id ?? 0);
        }

        DB::table('product_stocks')
            ->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $variantId)
            ->where('status', 1)
            ->update(['ps_min_order' => null]);

        $affected = 0;
        if ($minOrder !== null) {
            $target = DB::table('product_stocks')
                ->where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->where('status', 1)
                ->when($minOrderUnitId > 0, fn ($q) => $q->where('unit_id', $minOrderUnitId))
                ->orderBy('ps_id')
                ->first(['ps_id']);

            if (! $target) {
                $target = DB::table('product_stocks')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->where('status', 1)
                    ->orderBy('ps_id')
                    ->first(['ps_id']);
            }

            if ($target) {
                $affected = DB::table('product_stocks')
                    ->where('ps_id', $target->ps_id)
                    ->update(['ps_min_order' => $minOrder]);
            }
        }

        Log::info('[updateMinOrder] done', [
            'variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'min_order' => $minOrder,
            'unit_id' => $minOrderUnitId,
            'is_eceran' => $isEceranWarehouse,
            'affected' => $affected,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pemesanan minimum berhasil diperbarui',
        ]);
    }

    function insertStockAlert($data = [])
    {
        return response()->json(['success' => false, 'message' => 'Not implemented']);
    }

    function deleteStockAlert($data = [])
    {
        return response()->json(['success' => false, 'message' => 'Not implemented']);
    }
}
