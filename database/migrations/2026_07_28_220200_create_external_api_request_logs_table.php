<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat permintaan yang masuk lewat External API.
     *
     * Ini log LALU LINTAS, bukan audit trail administratif. Perubahan pada
     * aplikasi/kunci (dibuat, dicabut, dihapus) nanti punya tabelnya sendiri —
     * tabel ini sengaja tidak dicampur agar pembersihan log rutin tidak ikut
     * menghapus jejak audit.
     *
     * Referensi aplikasi & kunci disimpan sebagai ID biasa TANPA foreign key,
     * mengikuti konvensi tabel lain di proyek ini. Nama aplikasi ikut disalin
     * supaya log lama tetap terbaca meski aplikasinya sudah dihapus.
     *
     * Baris di sini bisa tumbuh sangat cepat, jadi indeksnya dirancang untuk
     * dua pola pakai: filter per aplikasi dan pembersihan/urutan per waktu.
     */
    public function up(): void
    {
        Schema::create('external_api_request_logs', function (Blueprint $table) {
            $table->bigIncrements('external_api_request_log_id');
            $table->unsignedBigInteger('external_application_id')->nullable();
            $table->unsignedBigInteger('external_api_key_id')->nullable();
            $table->string('application_name', 150)->nullable()->comment('Disalin saat request agar log lama tetap terbaca');
            $table->string('key_name', 150)->nullable();
            $table->string('method', 10);
            $table->string('endpoint', 255)->comment('Path yang diminta, termasuk query string');
            $table->string('route_name', 150)->nullable();
            $table->string('api_version', 20)->nullable();
            $table->unsignedSmallInteger('status_code')->default(0);
            $table->unsignedBigInteger('duration_ms')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index('requested_at', 'external_api_request_logs_requested_at_idx');
            $table->index(['external_application_id', 'requested_at'], 'external_api_request_logs_application_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_api_request_logs');
    }
};
