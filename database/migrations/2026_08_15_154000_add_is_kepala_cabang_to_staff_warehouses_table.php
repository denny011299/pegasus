<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('staff_warehouses', 'is_kepala_cabang')) {
            Schema::table('staff_warehouses', function (Blueprint $table) {
                $table->boolean('is_kepala_cabang')->default(false)->after('warehouse_id');
                $table->index(['warehouse_id', 'is_kepala_cabang'], 'staff_warehouses_warehouse_kepala_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('staff_warehouses', 'is_kepala_cabang')) {
            Schema::table('staff_warehouses', function (Blueprint $table) {
                $table->dropIndex('staff_warehouses_warehouse_kepala_index');
                $table->dropColumn('is_kepala_cabang');
            });
        }
    }
};
