<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail untuk `CashArmada` row yang auto-dibuat oleh `CashGudang::acceptCashGudang()` (lihat
 * KNOWN_ISSUES.md "CONFIRMED INTENTIONAL: CashGudang's operasional branch is not a pure status
 * flip") — sebelumnya row itu hanya punya catatan bebas teks ("Penyerahan kas dari gudang"), tidak
 * ada kolom yang bisa dipakai untuk menelusuri balik ke `cash_gudang_details` mana asalnya.
 *
 * Nullable: hanya diisi untuk CashArmada row yang lahir dari alur ini; row yang dibuat lewat alur
 * Cash Armada sendiri (insert/accept manual) tetap null.
 *
 * Kode yang membaca/menulis kolom ini SELALU dibungkus Schema::hasColumn() dulu — migration ini
 * belum tentu sudah ter-merge/dijalankan di semua branch/environment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cash_armadas', 'source_cgd_id')) {
            return;
        }

        Schema::table('cash_armadas', function (Blueprint $table) {
            $table->unsignedInteger('source_cgd_id')->nullable()->after('cash_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cash_armadas', 'source_cgd_id')) {
            return;
        }

        Schema::table('cash_armadas', function (Blueprint $table) {
            $table->dropColumn('source_cgd_id');
        });
    }
};
