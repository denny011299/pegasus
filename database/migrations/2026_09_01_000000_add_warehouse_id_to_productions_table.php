<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('productions') || Schema::hasColumn('productions', 'warehouse_id')) {
            return;
        }

        Schema::table('productions', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->default(1)->after('production_created_by');
            $table->index('warehouse_id', 'productions_warehouse_id_index');
        });

        $mainId = (int) (DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.status', 1)
            ->where('wt.is_main_warehouse', 1)
            ->orderBy('w.id')
            ->value('w.id') ?? 1);

        DB::table('productions')
            ->whereNull('warehouse_id')
            ->orWhere('warehouse_id', 0)
            ->update(['warehouse_id' => $mainId > 0 ? $mainId : 1]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('productions') || ! Schema::hasColumn('productions', 'warehouse_id')) {
            return;
        }

        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex('productions_warehouse_id_index');
            $table->dropColumn('warehouse_id');
        });
    }
};
