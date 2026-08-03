<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_delivery_orders_details')) {
            return;
        }
        if (! Schema::hasColumn('sales_delivery_orders_details', 'unit_id')) {
            Schema::table('sales_delivery_orders_details', function (Blueprint $table) {
                $table->unsignedInteger('unit_id')->nullable()->after('sdod_qty');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_delivery_orders_details')) {
            return;
        }
        if (Schema::hasColumn('sales_delivery_orders_details', 'unit_id')) {
            Schema::table('sales_delivery_orders_details', function (Blueprint $table) {
                $table->dropColumn('unit_id');
            });
        }
    }
};
