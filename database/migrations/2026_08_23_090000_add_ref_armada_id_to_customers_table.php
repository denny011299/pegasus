<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rujukan PMO untuk armada (kendaraan) yang disimpan sebagai baris customers
 * — sama pola dengan units.ref_unit_id/products.ref_product_id: id milik
 * sistem luar tidak pernah ditulis ke primary key lokal, disimpan di
 * kolomnya sendiri. BIGINT UNSIGNED dari awal (bukan INT) — sudah terbukti
 * id PMO 16 digit (lihat migrasi 2026_08_20_090000_widen_pmo_reference_id_columns).
 *
 * Sengaja TERPISAH dari customers.customer_code, yang sudah dipakai sebagai
 * id universal oleh modul lain (App\Http\Controllers\ExternalApi\V1\MasterArmadaController)
 * dengan sumber & aturan pembuatan yang sama sekali berbeda (diisi manual
 * oleh pemanggil eksternal, bukan dari PMO) — dua namespace id yang tidak
 * boleh tertukar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers') || Schema::hasColumn('customers', 'ref_armada_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('ref_armada_id')->nullable()->after('customer_code')
                ->comment('armada_id pada sistem PMO');
            $table->unique('ref_armada_id', 'customers_ref_armada_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'ref_armada_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_ref_armada_id_unique');
            $table->dropColumn('ref_armada_id');
        });
    }
};
