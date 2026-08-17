<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customer_code jadi "universal id" untuk External API (/armada,
 * API-002 lanjutan): pemanggil menentukan sendiri nilainya lewat POST, dan
 * memakainya lagi sebagai path parameter pada PUT/DELETE. Supaya
 * pencariannya selalu tidak ambigu, kolom ini wajib unik.
 *
 * Aman dijalankan: tidak ada baris customer_code kembar atau kosong pada
 * data yang berjalan saat migrasi ini ditulis (diperiksa manual sebelum
 * dibuat). Customer::generateCustomerID() turut dikeraskan (lihat commit
 * yang sama) supaya pembuatan pelanggan lewat halaman admin tidak pernah
 * diam-diam bentrok dengan customer_code yang sudah dipakai lewat endpoint
 * eksternal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unique('customer_code', 'customers_customer_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_customer_code_unique');
        });
    }
};
