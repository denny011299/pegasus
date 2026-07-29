<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * product_stocks per gudang:
     * - stok lama di-assign ke gudang utama (tipe is_main_warehouse)
     * - gudang aktif lain di-seed qty 0 untuk kombinasi yang sama
     */
    public function up(): void
    {
        if (! Schema::hasColumn('product_stocks', 'warehouse_id')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('unit_id');
                $table->index('warehouse_id', 'product_stocks_warehouse_id_index');
            });
        }

        $mainWarehouseId = DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.status', 1)
            ->where('wt.status', 1)
            ->where('wt.is_main_warehouse', 1)
            ->orderBy('w.id')
            ->value('w.id');

        if (! $mainWarehouseId) {
            $mainWarehouseId = DB::table('warehouses')
                ->where('status', 1)
                ->orderBy('id')
                ->value('id');
        }

        if ($mainWarehouseId) {
            DB::table('product_stocks')
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $mainWarehouseId]);
        }

        $templates = DB::table('product_stocks')
            ->where('status', 1)
            ->when($mainWarehouseId, fn ($q) => $q->where('warehouse_id', $mainWarehouseId))
            ->select('product_variant_id', 'product_id', 'unit_id')
            ->distinct()
            ->get();

        if ($templates->isEmpty()) {
            $templates = collect();
            $products = DB::table('products')->where('status', 1)->get(['product_id', 'product_unit']);
            foreach ($products as $product) {
                $units = json_decode($product->product_unit ?? '[]', true) ?: [];
                $variants = DB::table('product_variants')
                    ->where('product_id', $product->product_id)
                    ->where('status', 1)
                    ->pluck('product_variant_id');
                foreach ($variants as $variantId) {
                    foreach ($units as $unitId) {
                        $templates->push((object) [
                            'product_variant_id' => $variantId,
                            'product_id' => $product->product_id,
                            'unit_id' => $unitId,
                        ]);
                    }
                }
            }
        }

        $otherWarehouseIds = DB::table('warehouses')
            ->where('status', 1)
            ->when($mainWarehouseId, fn ($q) => $q->where('id', '!=', $mainWarehouseId))
            ->pluck('id');

        $now = now();
        $chunk = [];
        foreach ($otherWarehouseIds as $warehouseId) {
            foreach ($templates as $row) {
                $exists = DB::table('product_stocks')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $row->product_variant_id)
                    ->where('unit_id', $row->unit_id)
                    ->where('status', 1)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $chunk[] = [
                    'product_variant_id' => $row->product_variant_id,
                    'product_id' => $row->product_id,
                    'unit_id' => $row->unit_id,
                    'warehouse_id' => $warehouseId,
                    'ps_stock' => 0,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => null,
                ];

                if (count($chunk) >= 500) {
                    DB::table('product_stocks')->insert($chunk);
                    $chunk = [];
                }
            }
        }

        if (! empty($chunk)) {
            DB::table('product_stocks')->insert($chunk);
        }

        if ($mainWarehouseId) {
            DB::table('product_stocks')
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $mainWarehouseId]);
        }

        // Dedup sebelum unique: keep status=1 / stock tertinggi / ps_id terkecil
        $dupGroups = DB::select("
            SELECT warehouse_id, product_variant_id, unit_id
            FROM product_stocks
            WHERE warehouse_id IS NOT NULL
            GROUP BY warehouse_id, product_variant_id, unit_id
            HAVING COUNT(*) > 1
        ");

        foreach ($dupGroups as $group) {
            $rows = DB::table('product_stocks')
                ->where('warehouse_id', $group->warehouse_id)
                ->where('product_variant_id', $group->product_variant_id)
                ->where('unit_id', $group->unit_id)
                ->orderByDesc('status')
                ->orderByDesc('ps_stock')
                ->orderBy('ps_id')
                ->get();

            $keepId = $rows->first()->ps_id ?? null;
            foreach ($rows as $row) {
                if ((int) $row->ps_id === (int) $keepId) {
                    continue;
                }
                // Lepas dari unique key (soft-deleted duplicate)
                DB::table('product_stocks')->where('ps_id', $row->ps_id)->update([
                    'status' => 0,
                    'warehouse_id' => null,
                    'updated_at' => $now,
                ]);
            }
        }

        $indexExists = collect(DB::select('SHOW INDEX FROM product_stocks WHERE Key_name = ?', [
            'product_stocks_warehouse_variant_unit_unique',
        ]))->isNotEmpty();

        if (! $indexExists) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->unique(
                    ['warehouse_id', 'product_variant_id', 'unit_id'],
                    'product_stocks_warehouse_variant_unit_unique'
                );
            });
        }
    }

    public function down(): void
    {
        $indexExists = collect(DB::select('SHOW INDEX FROM product_stocks WHERE Key_name = ?', [
            'product_stocks_warehouse_variant_unit_unique',
        ]))->isNotEmpty();

        Schema::table('product_stocks', function (Blueprint $table) use ($indexExists) {
            if ($indexExists) {
                $table->dropUnique('product_stocks_warehouse_variant_unit_unique');
            }
            if (Schema::hasColumn('product_stocks', 'warehouse_id')) {
                $smIndexes = collect(DB::select('SHOW INDEX FROM product_stocks WHERE Key_name = ?', [
                    'product_stocks_warehouse_id_index',
                ]));
                if ($smIndexes->isNotEmpty()) {
                    $table->dropIndex('product_stocks_warehouse_id_index');
                }
                $table->dropColumn('warehouse_id');
            }
        });
    }
};
