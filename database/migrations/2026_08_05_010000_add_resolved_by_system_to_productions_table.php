<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membedakan produksi yang di-ACC/tolak lewat aksi staf sungguhan dari yang di-auto-timeout oleh
 * ProductionOverdueAutoResolver (lihat KNOWN_ISSUES.md "GET /getProduction can silently trigger
 * real stock mutations") — tanpa ini, acc_by bisa salah menunjuk ke staf yang kebetulan sedang
 * membuka halaman list saat auto-timeout terjadi (Session::get('user') masih ada), padahal staf itu
 * tidak melakukan apa-apa.
 *
 * Kode yang membaca/menulis kolom ini SELALU dibungkus Schema::hasColumn() dulu — migration ini
 * belum tentu sudah ter-merge/dijalankan di semua branch/environment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('productions', 'resolved_by_system')) {
            return;
        }

        Schema::table('productions', function (Blueprint $table) {
            $table->boolean('resolved_by_system')->default(false)->after('acc_by');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('productions', 'resolved_by_system')) {
            return;
        }

        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('resolved_by_system');
        });
    }
};
