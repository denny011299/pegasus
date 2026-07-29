<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menu UI = Pengiriman, tabel = sales_orders (bukan SO terpisah)
        if (! Schema::hasColumn('sales_orders', 'retail_warehouse_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('retail_warehouse_id')->nullable()->after('so_customer');
                $table->index('retail_warehouse_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_orders', 'retail_warehouse_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropIndex(['retail_warehouse_id']);
                $table->dropColumn('retail_warehouse_id');
            });
        }
    }
};
