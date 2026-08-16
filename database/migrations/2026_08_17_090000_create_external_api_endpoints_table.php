<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status per endpoint External API (App\ExternalApi\Docs\ApiEndpointDoc),
     * dikelola dari halaman Status API Eksternal — satu baris per endpoint
     * terdokumentasi, lintas semua versi sekaligus.
     *
     * `endpoint_key` diisi nilai ApiEndpointDoc::key(), sudah dijamin unik
     * lintas versi (lihat App\ExternalApi\Docs\ApiDocRegistry). Baris hanya
     * ada untuk endpoint yang salah satu saklarnya pernah diubah dari nilai
     * bawaan — lihat App\ExternalApi\Support\ApiEndpointSettings untuk arti
     * "tidak ada baris" pada tiap kolom.
     *
     * Tabel khusus, BUKAN tabel `settings` generik: baris di sini bertambah
     * seiring endpoint bertambah (akan terus tumbuh puluhan-ratus), dan
     * butuh kolom typed (boolean) dengan endpoint_key sebagai identitas,
     * bukan pasangan key/value string seperti saklar RequestLogger yang
     * cukup satu baris global.
     */
    public function up(): void
    {
        Schema::create('external_api_endpoints', function (Blueprint $table) {
            $table->bigIncrements('external_api_endpoint_id');
            $table->string('endpoint_key', 150)->comment('ApiEndpointDoc::key() — identitas unik endpoint lintas semua versi API');
            $table->boolean('is_active')->default(true)->comment('Endpoint bisa dipanggil — dicek di AuthenticateExternalApi sebelum autentikasi API Key');
            $table->boolean('is_public_docs_show')->default(false)->comment('Endpoint muncul di halaman dokumentasi publik /api-docs');
            $table->timestamps();

            $table->unique('endpoint_key', 'external_api_endpoints_key_unique');
        });

        $this->migrateFromSettingsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('external_api_endpoints');
    }

    /**
     * Fitur Status API Eksternal sebelumnya menyimpan kedua saklar ini di
     * tabel `settings` generik (setting_key
     * `external_api_endpoint_active_{key}` /
     * `external_api_endpoint_public_docs_{key}`). Baris yang sudah pernah
     * diatur admin dipindah ke sini dan dihapus dari `settings`, supaya
     * konfigurasi yang ada tidak hilang saat migrasi ini berjalan di
     * lingkungan lain (staging/production) yang sempat memakai bentuk lama.
     */
    private function migrateFromSettingsTable(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $rows = DB::table('settings')
            ->where(function ($query) {
                $query->where('setting_key', 'like', 'external_api_endpoint_active_%')
                    ->orWhere('setting_key', 'like', 'external_api_endpoint_public_docs_%');
            })
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $byKey = [];

        foreach ($rows as $row) {
            if (str_starts_with($row->setting_key, 'external_api_endpoint_active_')) {
                $endpointKey = substr($row->setting_key, strlen('external_api_endpoint_active_'));
                $byKey[$endpointKey]['is_active'] = filter_var($row->setting_value, FILTER_VALIDATE_BOOLEAN);
            } elseif (str_starts_with($row->setting_key, 'external_api_endpoint_public_docs_')) {
                $endpointKey = substr($row->setting_key, strlen('external_api_endpoint_public_docs_'));
                $byKey[$endpointKey]['is_public_docs_show'] = filter_var($row->setting_value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $now = now();

        foreach ($byKey as $endpointKey => $values) {
            DB::table('external_api_endpoints')->insert([
                'endpoint_key' => $endpointKey,
                'is_active' => $values['is_active'] ?? true,
                'is_public_docs_show' => $values['is_public_docs_show'] ?? false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('settings')
            ->where(function ($query) {
                $query->where('setting_key', 'like', 'external_api_endpoint_active_%')
                    ->orWhere('setting_key', 'like', 'external_api_endpoint_public_docs_%');
            })
            ->delete();
    }
};
