<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_supply_returns') && ! Schema::hasColumn('customer_supply_returns', 'return_group')) {
            Schema::table('customer_supply_returns', function (Blueprint $table) {
                $table->string('return_group', 40)->nullable()->after('return_number')->index();
            });
        }

        if (Schema::hasTable('customer_product_returns') && ! Schema::hasColumn('customer_product_returns', 'return_group')) {
            Schema::table('customer_product_returns', function (Blueprint $table) {
                $table->string('return_group', 40)->nullable()->after('return_number')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_supply_returns') && Schema::hasColumn('customer_supply_returns', 'return_group')) {
            Schema::table('customer_supply_returns', function (Blueprint $table) {
                $table->dropIndex(['return_group']);
                $table->dropColumn('return_group');
            });
        }

        if (Schema::hasTable('customer_product_returns') && Schema::hasColumn('customer_product_returns', 'return_group')) {
            Schema::table('customer_product_returns', function (Blueprint $table) {
                $table->dropIndex(['return_group']);
                $table->dropColumn('return_group');
            });
        }
    }
};
