<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rujukan eksternal untuk bahan mentah/kemasan, sama persis pola units.ref_unit_id dan
 * products.ref_product_id — nullable (banyak bahan dibuat lewat halaman admin, tidak pernah
 * terhubung ke sistem PMO) dan unik (satu ref_supplies_id cuma boleh menunjuk satu baris supplies).
 * Dipakai App\Http\Controllers\ExternalApi\V1\MasterSuppliesController (Data Bahan) sebagai id
 * BODY create dan PATH ubah/hapus/connect, sama seperti ref_product_id pada Data Produk.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplies') && ! Schema::hasColumn('supplies', 'ref_supplies_id')) {
            Schema::table('supplies', function (Blueprint $table) {
                $table->integer('ref_supplies_id')->nullable()->after('supplies_id')
                    ->comment('supplies_id pada sistem PMO');
                $table->unique('ref_supplies_id', 'supplies_ref_supplies_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplies') && Schema::hasColumn('supplies', 'ref_supplies_id')) {
            Schema::table('supplies', function (Blueprint $table) {
                $table->dropUnique('supplies_ref_supplies_id_unique');
                $table->dropColumn('ref_supplies_id');
            });
        }
    }
};
