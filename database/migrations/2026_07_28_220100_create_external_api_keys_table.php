<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * API Key milik satu External Application.
     *
     * Kunci asli TIDAK PERNAH disimpan. Yang tersimpan hanya:
     * - `key_prefix`    : bagian awal kunci yang boleh dilihat, dipakai sebagai
     *                     kunci pencarian saat autentikasi (unik + terindeks).
     * - `key_hash`      : SHA-256 dari kunci utuh, dibandingkan constant-time.
     * - `key_last_four` : empat karakter terakhir untuk tampilan tersamar.
     *
     * `expires_at` NULL berarti "Tidak Pernah Kedaluwarsa". Status kedaluwarsa
     * tidak disimpan sebagai kolom karena selalu bisa dihitung dari waktu
     * sekarang — menyimpannya justru berisiko basi.
     *
     * Skema ini sudah siap untuk rotasi kunci di fase berikutnya: satu aplikasi
     * boleh punya banyak kunci aktif sekaligus, jadi kunci lama dan baru bisa
     * hidup berdampingan selama masa migrasi klien.
     */
    public function up(): void
    {
        Schema::create('external_api_keys', function (Blueprint $table) {
            $table->bigIncrements('external_api_key_id');
            $table->unsignedBigInteger('external_application_id');
            $table->string('key_name', 150);
            $table->string('environment', 20)->default('production')->comment('production, staging, development');
            $table->string('key_prefix', 64)->comment('Bagian awal kunci, dipakai untuk pencarian saat autentikasi');
            $table->string('key_hash', 64)->comment('SHA-256 dari kunci utuh');
            $table->string('key_last_four', 8);
            $table->string('key_status', 20)->default('active')->comment('active, revoked');
            $table->timestamp('expires_at')->nullable()->comment('NULL = tidak pernah kedaluwarsa');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->integer('revoked_by')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('status')->default(1)->comment('1 = active, 0 = dead');
            $table->timestamps();

            $table->unique('key_prefix', 'external_api_keys_prefix_unique');
            $table->index(['external_application_id', 'status'], 'external_api_keys_application_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_api_keys');
    }
};
