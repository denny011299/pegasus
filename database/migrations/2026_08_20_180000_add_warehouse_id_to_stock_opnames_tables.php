<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stock Opname (Produk + Bahan) per gudang.
 * Dokumen baru mengikat warehouse_id dari gudang aktif saat dibuat.
 * List datatable difilter per gudang aktif. ACC sudah membaca sto/stob->warehouse_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_opnames', 'warehouse_id')) {
            Schema::table('stock_opnames', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('category_id');
                $table->index('warehouse_id', 'stock_opnames_warehouse_id_index');
            });
        }

        if (! Schema::hasColumn('stock_opname_bahans', 'warehouse_id')) {
            Schema::table('stock_opname_bahans', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('staff_id');
                $table->index('warehouse_id', 'stock_opname_bahans_warehouse_id_index');
            });
        }

        $mainWarehouseId = $this->mainWarehouseId();
        if ($mainWarehouseId > 0) {
            if (Schema::hasColumn('stock_opnames', 'warehouse_id')) {
                DB::table('stock_opnames')
                    ->whereNull('warehouse_id')
                    ->update(['warehouse_id' => $mainWarehouseId]);
            }
            if (Schema::hasColumn('stock_opname_bahans', 'warehouse_id')) {
                DB::table('stock_opname_bahans')
                    ->whereNull('warehouse_id')
                    ->update(['warehouse_id' => $mainWarehouseId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_opnames', 'warehouse_id')) {
            Schema::table('stock_opnames', function (Blueprint $table) {
                $table->dropIndex('stock_opnames_warehouse_id_index');
                $table->dropColumn('warehouse_id');
            });
        }

        if (Schema::hasColumn('stock_opname_bahans', 'warehouse_id')) {
            Schema::table('stock_opname_bahans', function (Blueprint $table) {
                $table->dropIndex('stock_opname_bahans_warehouse_id_index');
                $table->dropColumn('warehouse_id');
            });
        }
    }

    private function mainWarehouseId(): int
    {
        if (! Schema::hasTable('warehouses') || ! Schema::hasTable('warehouse_types')) {
            return 0;
        }

        return (int) (DB::table('warehouses')
            ->join('warehouse_types', 'warehouse_types.id', '=', 'warehouses.warehouse_type_id')
            ->where('warehouses.status', 1)
            ->where('warehouse_types.status', 1)
            ->where('warehouse_types.is_main_warehouse', 1)
            ->orderBy('warehouses.id')
            ->value('warehouses.id') ?? 0);
    }
};
