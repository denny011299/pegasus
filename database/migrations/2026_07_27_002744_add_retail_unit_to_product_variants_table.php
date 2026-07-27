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
        if (! Schema::hasColumn('product_variants', 'retail_unit')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->integer('retail_unit')->nullable()->after('product_variant_stock');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variants', 'retail_unit')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('retail_unit');
            });
        }
    }
};
