<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variants', 'safety_stock')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->integer('safety_stock')->default(0)->after('product_variant_alert');
            });
        }
        if (! Schema::hasColumn('product_variants', 'safety_unit_id')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->integer('safety_unit_id')->nullable()->after('safety_stock');
            });
        }

        if (! Schema::hasColumn('product_stocks', 'ps_safety_stock')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->integer('ps_safety_stock')->default(0)->after('ps_stock');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variants', 'safety_unit_id')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('safety_unit_id');
            });
        }
        if (Schema::hasColumn('product_variants', 'safety_stock')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('safety_stock');
            });
        }

        if (Schema::hasColumn('product_stocks', 'ps_safety_stock')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->dropColumn('ps_safety_stock');
            });
        }
    }
};
