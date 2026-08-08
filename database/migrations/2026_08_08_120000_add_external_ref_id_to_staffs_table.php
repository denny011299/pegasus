<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * external_ref_id: id yang dipakai sistem eksternal untuk merujuk satu staf
 * (dipakai lewat External API /master/sales — API-002 lanjutan). Nullable
 * karena staf yang dibuat lewat halaman admin tidak otomatis punya rujukan
 * eksternal; dihubungkan belakangan lewat PATCH /master/sales/{staff_id}.
 *
 * Unique (nullable-safe di MySQL — banyak baris NULL tetap diperbolehkan,
 * hanya nilai bukan-NULL yang wajib unik) supaya pencarian
 * external_ref_id -> satu staf selalu tidak ambigu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->string('external_ref_id', 191)->nullable()->after('staff_code');
            $table->unique('external_ref_id');
        });
    }

    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropUnique(['external_ref_id']);
            $table->dropColumn('external_ref_id');
        });
    }
};
