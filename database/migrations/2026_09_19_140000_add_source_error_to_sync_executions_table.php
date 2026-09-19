<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pesan error PMO mentah di balik sebuah fallback sumber data (mis. /getUnits gagal
     * pada SyncUnitStep) — dipisahkan dari `notices` supaya wizard bisa menampilkannya
     * sebagai accordion tertutup terpisah, bukan tercampur di daftar catatan biasa.
     */
    public function up(): void
    {
        Schema::table('sync_executions', function (Blueprint $table) {
            $table->text('source_error')->nullable()->after('notices');
        });
    }

    public function down(): void
    {
        Schema::table('sync_executions', function (Blueprint $table) {
            $table->dropColumn('source_error');
        });
    }
};
