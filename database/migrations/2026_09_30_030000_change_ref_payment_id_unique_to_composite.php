<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ref_payment_id berhenti unik sendirian (GitHub #208).
     *
     * PMO mengirim satu group pembayaran Sales sebagai beberapa POST dengan ref_payment_id yang
     * SAMA — satu call per sales (staff_id) penerima, karena satu baris cash_sales hanya bisa
     * mencatat satu staff_id. Unique index lama membuat call kedua dianggap kiriman ulang
     * (idempotent_replay) dan datanya hilang tanpa error: sudah terverifikasi 2 call jadi hanya
     * 1 baris cash_sales.
     *
     * Kunci idempotensi diperbaiki menjadi ref_payment_id + penerima:
     *   - cash_armadas : ref_payment_id + customer_id (armada_code diterjemahkan ke customer_id)
     *   - cash_sales   : ref_payment_id + staff_id
     *
     * Beda dengan migration ref_nota_id yang ditulis ulang di tempat: migration
     * 2026_07_29_030000 yang membuat unique index lama SUDAH berjalan di main/production
     * (bagian dari fase1 payments API), jadi tidak boleh diedit — diperbaiki lewat migration
     * baru di sini, mengikuti alur normal.
     */
    public function up(): void
    {
        Schema::table('cash_armadas', function (Blueprint $table) {
            $table->dropUnique('cash_armadas_ref_payment_id_unique');
            $table->unique(['ref_payment_id', 'customer_id'], 'cash_armadas_ref_payment_id_customer_id_unique');
        });

        Schema::table('cash_sales', function (Blueprint $table) {
            $table->dropUnique('cash_sales_ref_payment_id_unique');
            $table->unique(['ref_payment_id', 'staff_id'], 'cash_sales_ref_payment_id_staff_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cash_armadas', function (Blueprint $table) {
            $table->dropUnique('cash_armadas_ref_payment_id_customer_id_unique');
            $table->unique('ref_payment_id', 'cash_armadas_ref_payment_id_unique');
        });

        Schema::table('cash_sales', function (Blueprint $table) {
            $table->dropUnique('cash_sales_ref_payment_id_staff_id_unique');
            $table->unique('ref_payment_id', 'cash_sales_ref_payment_id_unique');
        });
    }
};
