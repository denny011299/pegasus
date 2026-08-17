<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_supply_returns') && ! Schema::hasColumn('customer_supply_returns', 'qc_staff_id')) {
            Schema::table('customer_supply_returns', function (Blueprint $table) {
                $table->integer('qc_staff_id')->nullable()->after('created_by');
                $table->index('qc_staff_id');
            });
        }

        if (Schema::hasTable('customer_product_returns') && ! Schema::hasColumn('customer_product_returns', 'qc_staff_id')) {
            Schema::table('customer_product_returns', function (Blueprint $table) {
                $table->integer('qc_staff_id')->nullable()->after('created_by');
                $table->index('qc_staff_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_supply_returns') && Schema::hasColumn('customer_supply_returns', 'qc_staff_id')) {
            Schema::table('customer_supply_returns', function (Blueprint $table) {
                $table->dropIndex(['qc_staff_id']);
                $table->dropColumn('qc_staff_id');
            });
        }

        if (Schema::hasTable('customer_product_returns') && Schema::hasColumn('customer_product_returns', 'qc_staff_id')) {
            Schema::table('customer_product_returns', function (Blueprint $table) {
                $table->dropIndex(['qc_staff_id']);
                $table->dropColumn('qc_staff_id');
            });
        }
    }
};
