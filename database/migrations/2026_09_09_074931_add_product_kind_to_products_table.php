<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || Schema::hasColumn('products', 'product_kind')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            // product = Daftar/Stok Produk; chemical = Daftar/Stok Bahan Kimia (satu pipeline)
            $table->string('product_kind', 20)->default('product')->after('product_name');
            $table->index('product_kind', 'products_product_kind_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'product_kind')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_product_kind_index');
            $table->dropColumn('product_kind');
        });
    }
};
