<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplies_variants', 'lead_time_days')) {
            Schema::table('supplies_variants', function (Blueprint $table) {
                $table->unsignedInteger('lead_time_days')->default(0)->after('supplies_variant_price');
            });
        }
        if (! Schema::hasColumn('supplies_variants', 'safety_stock')) {
            Schema::table('supplies_variants', function (Blueprint $table) {
                $table->unsignedInteger('safety_stock')->default(0)->after('lead_time_days');
            });
        }
        if (! Schema::hasColumn('product_variants', 'lead_time_days')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->unsignedInteger('lead_time_days')->default(0)->after('product_variant_alert');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplies_variants', 'safety_stock')) {
            Schema::table('supplies_variants', fn (Blueprint $table) => $table->dropColumn('safety_stock'));
        }
        if (Schema::hasColumn('supplies_variants', 'lead_time_days')) {
            Schema::table('supplies_variants', fn (Blueprint $table) => $table->dropColumn('lead_time_days'));
        }
        if (Schema::hasColumn('product_variants', 'lead_time_days')) {
            Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn('lead_time_days'));
        }
    }
};
