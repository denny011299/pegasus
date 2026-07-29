<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referensi pembayaran dari sistem eksternal.
     *
     * External API menjanjikan POST pembayaran yang idempoten: permintaan yang
     * sama dikirim dua kali tidak boleh melahirkan dua transaksi kas. Penanda
     * itu dikirim pemanggil sebagai ref_payment_id, dan sampai sekarang tidak
     * ada tempat menyimpannya — tanpa kolom ini, permintaan ulang akan menjadi
     * pembayaran ganda.
     *
     * Pola kolomnya sengaja dibuat sama dengan units.ref_unit_id dan
     * products.ref_product_id yang sudah ada untuk sinkronisasi PMO: id milik
     * sistem luar tidak pernah ditulis ke primary key lokal, melainkan
     * disimpan di kolomnya sendiri.
     *
     * Nullable — seluruh transaksi kas yang sudah ada (498 armada, 334 sales)
     * memang tidak berasal dari API dan tetap sah bernilai NULL. Pada MySQL,
     * NULL boleh berulang di unique index, jadi keunikan hanya mengikat baris
     * yang benar-benar datang dari luar.
     *
     * varchar, bukan integer: penanda ini milik sistem lain dan bentuknya
     * ditentukan mereka (bisa UUID, bisa berawalan huruf).
     */
    public function up(): void
    {
        Schema::table('cash_armadas', function (Blueprint $table) {
            $table->string('ref_payment_id', 100)->nullable()->after('cr_id')
                ->comment('Referensi pembayaran dari sistem eksternal');
            $table->unique('ref_payment_id', 'cash_armadas_ref_payment_id_unique');
        });

        Schema::table('cash_sales', function (Blueprint $table) {
            $table->string('ref_payment_id', 100)->nullable()->after('cs_id')
                ->comment('Referensi pembayaran dari sistem eksternal');
            $table->unique('ref_payment_id', 'cash_sales_ref_payment_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cash_armadas', function (Blueprint $table) {
            $table->dropUnique('cash_armadas_ref_payment_id_unique');
            $table->dropColumn('ref_payment_id');
        });

        Schema::table('cash_sales', function (Blueprint $table) {
            $table->dropUnique('cash_sales_ref_payment_id_unique');
            $table->dropColumn('ref_payment_id');
        });
    }
};
