<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan informatif yang bukan kegagalan.
     *
     * Contoh: baris PMO yang diadopsi ke baris Pegasus yang sudah ada, atau
     * peringatan stok yang tidak bisa dikonversi satuannya. Dipisahkan dari
     * kolom `errors` supaya daftar masalah tetap benar-benar berisi masalah.
     */
    public function up(): void
    {
        Schema::table('sync_executions', function (Blueprint $table) {
            $table->json('notices')->nullable()->after('errors');
        });
    }

    public function down(): void
    {
        Schema::table('sync_executions', function (Blueprint $table) {
            $table->dropColumn('notices');
        });
    }
};
