<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('production_plannings')) {
            return;
        }
        if (! Schema::hasColumn('production_plannings', 'warehouse_id')) {
            Schema::table('production_plannings', function (Blueprint $table) {
                $table->unsignedInteger('warehouse_id')->nullable()->after('pp_date');
                $table->index(['warehouse_id', 'status', 'pp_status'], 'pp_wh_status_idx');
            });
        }

        // Backfill: PP lama tanpa gudang → gudang utama aktif (jika ada).
        $mainId = null;
        if (Schema::hasTable('warehouses') && Schema::hasTable('warehouse_types')) {
            $mainId = DB::table('warehouses')
                ->join('warehouse_types', 'warehouse_types.id', '=', 'warehouses.warehouse_type_id')
                ->where('warehouses.status', 1)
                ->where('warehouse_types.is_main_warehouse', 1)
                ->orderBy('warehouses.id')
                ->value('warehouses.id');
        }
        if ($mainId) {
            DB::table('production_plannings')
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => (int) $mainId]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('production_plannings')) {
            return;
        }
        if (Schema::hasColumn('production_plannings', 'warehouse_id')) {
            Schema::table('production_plannings', function (Blueprint $table) {
                $table->dropIndex('pp_wh_status_idx');
                $table->dropColumn('warehouse_id');
            });
        }
    }
};
