<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * request_body/response_body — TIDAK PERNAH diisi di luar lingkungan local (lihat
 * App\ExternalApi\Logging\RequestLogger::write()), murni alat bantu debug saat mengembangkan
 * integrasi PMO secara lokal (mis. melacak kenapa satu permintaan dibalas 422 tanpa perlu buka
 * storage/logs). Kolom tetap ada di semua environment (skema harus sama), cuma isinya yang
 * dijaga kosong di luar local. Baris ditampilkan lewat popup detail saat baris log diklik —
 * lihat resources/views/Backoffice/ExternalApi/Logs.blade.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_api_request_logs', function (Blueprint $table) {
            $table->longText('request_body')->nullable()->after('user_agent')
                ->comment('Hanya diisi di lingkungan local, untuk debug — lihat RequestLogger');
            $table->longText('response_body')->nullable()->after('request_body')
                ->comment('Hanya diisi di lingkungan local, untuk debug — lihat RequestLogger');
        });
    }

    public function down(): void
    {
        Schema::table('external_api_request_logs', function (Blueprint $table) {
            $table->dropColumn(['request_body', 'response_body']);
        });
    }
};
