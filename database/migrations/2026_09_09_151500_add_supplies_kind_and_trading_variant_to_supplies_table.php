<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplies')) {
            return;
        }

        Schema::table('supplies', function (Blueprint $table) {
            if (! Schema::hasColumn('supplies', 'supplies_kind')) {
                // supply = bahan mentah biasa; trading = stok masuk ke product_variant
                $table->string('supplies_kind', 20)->default('supply')->after('supplies_name');
            }
            if (! Schema::hasColumn('supplies', 'trading_product_variant_id')) {
                $table->unsignedInteger('trading_product_variant_id')->nullable()->after('supplies_kind');
            }
        });

        // Data lama = bahan mentah
        DB::table('supplies')
            ->whereNull('supplies_kind')
            ->orWhere('supplies_kind', '')
            ->update(['supplies_kind' => 'supply']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplies')) {
            return;
        }

        Schema::table('supplies', function (Blueprint $table) {
            if (Schema::hasColumn('supplies', 'trading_product_variant_id')) {
                $table->dropColumn('trading_product_variant_id');
            }
            if (Schema::hasColumn('supplies', 'supplies_kind')) {
                $table->dropColumn('supplies_kind');
            }
        });
    }
};
