<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referensi opsional ke faktur/nota asal pembayaran (GitHub #207).
     *
     * PMO menyebut faktur pelanggan sebagai "nota". POST /payments/cash tidak
     * punya kolom yang mengaitkan transaksi kas ke nota asalnya, sehingga
     * rekonsiliasi keuangan harus mem-parsing teks bebas di notes. Kolom ini
     * mengikuti pola ref_payment_id: id milik sistem luar, nullable karena
     * opsional dan karena transaksi lama tidak punya nilai ini.
     */
    public function up(): void
    {
        Schema::table('cash_armadas', function (Blueprint $table) {
            $table->string('ref_nota_id', 100)->nullable()->after('ref_payment_id')
                ->comment('Referensi nota/faktur asal pembayaran dari sistem eksternal');
        });

        Schema::table('cash_sales', function (Blueprint $table) {
            $table->string('ref_nota_id', 100)->nullable()->after('ref_payment_id')
                ->comment('Referensi nota/faktur asal pembayaran dari sistem eksternal');
        });
    }

    public function down(): void
    {
        Schema::table('cash_armadas', function (Blueprint $table) {
            $table->dropColumn('ref_nota_id');
        });

        Schema::table('cash_sales', function (Blueprint $table) {
            $table->dropColumn('ref_nota_id');
        });
    }
};
