<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referensi opsional ke faktur/nota asal pembayaran (GitHub #207, direvisi #208).
     *
     * PMO menyebut faktur pelanggan sebagai "nota". Awalnya kolom ini ditambahkan di level
     * transaksi (cash_armadas/cash_sales), tapi #208 mengoreksi: nota adalah atribut TIAP ITEM
     * tunai, bukan satu transaksi — satu pembayaran bisa menggabungkan beberapa nota. Karena
     * migration ini belum pernah dijalankan di staging/production (masih di branch lokal, belum
     * di-PR), revisinya dilakukan dengan menulis ulang file yang sama, bukan menambah migration
     * susulan yang menambah lalu memindahkan kolom — supaya live database hanya menjalankan
     * migration ini sekali, dengan bentuk akhirnya.
     *
     * Kolomnya karena itu ada di cash_armada_details / cash_sales_details, mengikuti pola
     * ref_payment_id: id milik sistem luar, nullable karena opsional.
     */
    public function up(): void
    {
        Schema::table('cash_armada_details', function (Blueprint $table) {
            $table->string('ref_nota_id', 100)->nullable()->after('crd_notes')
                ->comment('Referensi nota/faktur asal item ini dari sistem eksternal');
        });

        Schema::table('cash_sales_details', function (Blueprint $table) {
            $table->string('ref_nota_id', 100)->nullable()->after('csd_notes')
                ->comment('Referensi nota/faktur asal item ini dari sistem eksternal');
        });
    }

    public function down(): void
    {
        Schema::table('cash_armada_details', function (Blueprint $table) {
            $table->dropColumn('ref_nota_id');
        });

        Schema::table('cash_sales_details', function (Blueprint $table) {
            $table->dropColumn('ref_nota_id');
        });
    }
};
