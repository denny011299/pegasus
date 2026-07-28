<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom referensi id PMO.
     *
     * Id PMO dan id Pegasus adalah dua ruang id yang berbeda (unit_id 1 di PMO
     * = "Dos", sedangkan di Pegasus = "Kilogram"), jadi id PMO tidak boleh
     * ditulis ke primary key lokal. Kolom ini yang menjembatani keduanya.
     *
     * Hanya dua kolom: PMO hanya menerbitkan id untuk satuan dan produk.
     * Kategori dicocokkan lewat nama, varian lewat (product_id, sku).
     *
     * Nullable — baris lama tetap sah sampai diadopsi oleh sinkronisasi
     * pertama. NULL boleh berulang pada unique index MySQL.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->integer('ref_unit_id')->nullable()->after('unit_id')
                ->comment('unit_id pada sistem PMO');
            $table->unique('ref_unit_id', 'units_ref_unit_id_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('ref_product_id')->nullable()->after('product_id')
                ->comment('ref_product_id pada sistem PMO');
            $table->unique('ref_product_id', 'products_ref_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropUnique('units_ref_unit_id_unique');
            $table->dropColumn('ref_unit_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_ref_product_id_unique');
            $table->dropColumn('ref_product_id');
        });
    }
};
