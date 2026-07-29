<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplies_stocks', 'warehouse_id')) {
            Schema::table('supplies_stocks', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('unit_id');
                $table->index('warehouse_id', 'supplies_stocks_warehouse_id_index');
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
            DB::table('supplies_stocks')
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $mainWarehouseId]);
        }

        $templates = DB::table('supplies_stocks')
            ->where('status', 1)
            ->when($mainWarehouseId, fn ($q) => $q->where('warehouse_id', $mainWarehouseId))
            ->select('supplies_id', 'unit_id')
            ->distinct()
            ->get();

        if ($templates->isEmpty()) {
            $templates = collect();
            $supplies = DB::table('supplies')->where('status', 1)->get(['supplies_id', 'supplies_unit']);
            foreach ($supplies as $s) {
                $units = json_decode($s->supplies_unit ?? '[]', true) ?: [];
                foreach ($units as $unitId) {
                    $templates->push((object) [
                        'supplies_id' => $s->supplies_id,
                        'unit_id' => $unitId,
                    ]);
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
                $exists = DB::table('supplies_stocks')
                    ->where('warehouse_id', $warehouseId)
                    ->where('supplies_id', $row->supplies_id)
                    ->where('unit_id', $row->unit_id)
                    ->where('status', 1)
                    ->exists();
                if ($exists) {
                    continue;
                }
                $chunk[] = [
                    'supplies_id' => $row->supplies_id,
                    'unit_id' => $row->unit_id,
                    'warehouse_id' => $warehouseId,
                    'ss_stock' => 0,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => null,
                ];
                if (count($chunk) >= 500) {
                    DB::table('supplies_stocks')->insert($chunk);
                    $chunk = [];
                }
            }
        }
        if (! empty($chunk)) {
            DB::table('supplies_stocks')->insert($chunk);
        }

        if ($mainWarehouseId) {
            DB::table('supplies_stocks')
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $mainWarehouseId]);
        }

        // Dedup for unique
        $dupGroups = DB::select("
            SELECT warehouse_id, supplies_id, unit_id
            FROM supplies_stocks
            WHERE warehouse_id IS NOT NULL
            GROUP BY warehouse_id, supplies_id, unit_id
            HAVING COUNT(*) > 1
        ");
        foreach ($dupGroups as $group) {
            $rows = DB::table('supplies_stocks')
                ->where('warehouse_id', $group->warehouse_id)
                ->where('supplies_id', $group->supplies_id)
                ->where('unit_id', $group->unit_id)
                ->orderByDesc('status')
                ->orderByDesc('ss_stock')
                ->orderBy('ss_id')
                ->get();
            $keepId = $rows->first()->ss_id ?? null;
            foreach ($rows as $row) {
                if ((int) $row->ss_id === (int) $keepId) {
                    continue;
                }
                DB::table('supplies_stocks')->where('ss_id', $row->ss_id)->update([
                    'status' => 0,
                    'warehouse_id' => null,
                    'updated_at' => $now,
                ]);
            }
        }

        $indexExists = collect(DB::select('SHOW INDEX FROM supplies_stocks WHERE Key_name = ?', [
            'supplies_stocks_warehouse_supplies_unit_unique',
        ]))->isNotEmpty();

        if (! $indexExists) {
            Schema::table('supplies_stocks', function (Blueprint $table) {
                $table->unique(
                    ['warehouse_id', 'supplies_id', 'unit_id'],
                    'supplies_stocks_warehouse_supplies_unit_unique'
                );
            });
        }
    }

    public function down(): void
    {
        $indexExists = collect(DB::select('SHOW INDEX FROM supplies_stocks WHERE Key_name = ?', [
            'supplies_stocks_warehouse_supplies_unit_unique',
        ]))->isNotEmpty();

        Schema::table('supplies_stocks', function (Blueprint $table) use ($indexExists) {
            if ($indexExists) {
                $table->dropUnique('supplies_stocks_warehouse_supplies_unit_unique');
            }
            if (Schema::hasColumn('supplies_stocks', 'warehouse_id')) {
                $smIndexes = collect(DB::select('SHOW INDEX FROM supplies_stocks WHERE Key_name = ?', [
                    'supplies_stocks_warehouse_id_index',
                ]));
                if ($smIndexes->isNotEmpty()) {
                    $table->dropIndex('supplies_stocks_warehouse_id_index');
                }
                $table->dropColumn('warehouse_id');
            }
        });
    }
};
