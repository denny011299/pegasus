<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_order_details', 'warehouse_id')) {
            Schema::table('sales_order_details', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('unit_id')->index();
            });
        }

        if (Schema::hasColumn('sales_orders', 'retail_warehouse_id')) {
            DB::statement('
                UPDATE sales_order_details sod
                INNER JOIN sales_orders so ON so.so_id = sod.so_id
                INNER JOIN product_variants pv ON pv.product_variant_id = sod.product_variant_id
                SET sod.warehouse_id = so.retail_warehouse_id
                WHERE sod.warehouse_id IS NULL
                  AND so.retail_warehouse_id IS NOT NULL
                  AND pv.retail_unit > 0
                  AND sod.unit_id = pv.retail_unit
            ');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_order_details', 'warehouse_id')) {
            Schema::table('sales_order_details', function (Blueprint $table) {
                $table->dropIndex(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            });
        }
    }
};
