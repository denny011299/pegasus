<?php

namespace Tests\Support;

use App\ExternalApi\ApiKeyManager;
use App\Models\ExternalApiEndpointStatus;
use App\Models\ExternalApiKey;
use App\Models\ExternalApplication;

/**
 * The External API (routes/external-api/v1.php) authenticates via an API Key header, never a
 * session — see App\ExternalApi\Auth\ExternalApiAuthenticator. This trait builds a real
 * application + key pair and returns the headers to attach to a test request, mirroring how
 * Tests\Support\ActingAsStaff builds a session for the internal admin routes.
 */
trait ActingAsExternalApiClient
{
    /** @return array<string, string> */
    protected function externalApiHeaders(array $overrides = []): array
    {
        // Saklar "Endpoint Aktif" (halaman Status API Eksternal) adalah fitur admin yang berdiri
        // sendiri, TERPISAH dari apa yang diuji test-test fungsional ini. Endpoint yang dimatikan
        // dijawab 503 oleh App\Http\Middleware\AuthenticateExternalApi SEBELUM autentikasi jalan,
        // jadi test yang endpoint-nya kebetulan mati di DB lokal gagal dengan 503 yang menyesatkan
        // — bukan menguji apa pun. Karena itu saklarnya dipastikan menyala di sini, sebagai bagian
        // dari menyiapkan klien, supaya test tidak bergantung pada keadaan DB lokal.
        //
        // Perilaku "endpoint dimatikan" TETAP diuji, sengaja dan eksplisit, di
        // Tests\Workflow\ExternalApiDisabledEndpointFlowTest lewat disableExternalApiEndpoint().
        $this->enableAllExternalApiEndpoints();

        $application = new ExternalApplication();
        $application->application_code = 'test-app-'.uniqid();
        $application->application_name = 'Test External Application';
        $application->application_status = ExternalApplication::STATUS_ACTIVE;
        $application->status = 1;
        $application->save();

        /** @var ApiKeyManager $manager */
        $manager = app(ApiKeyManager::class);
        $generated = $manager->generate('development');

        $key = new ExternalApiKey();
        $key->external_application_id = $application->external_application_id;
        $key->key_name = 'Test Key';
        $key->environment = 'development';
        $key->key_prefix = $generated['prefix'];
        $key->key_hash = $generated['hash'];
        $key->key_last_four = $generated['last_four'];
        $key->key_status = 'active';
        $key->status = 1;
        foreach ($overrides as $attr => $value) {
            $key->{$attr} = $value;
        }
        $key->save();

        return [$manager->header() => $generated['plain']];
    }

    /**
     * Nyalakan saklar "Endpoint Aktif" untuk SEMUA endpoint.
     *
     * Aman: Tests\TestCase memakai DatabaseTransactions, jadi perubahan ini di-rollback setiap
     * selesai satu test dan tidak pernah mengubah DB developer secara permanen. Baris yang memang
     * belum pernah ada sengaja TIDAK dibuat — ApiEndpointSettings::isActive() sudah menganggap
     * "tidak ada baris" = aktif (lihat docblock ExternalApiEndpointStatus).
     */
    protected function enableAllExternalApiEndpoints(): void
    {
        ExternalApiEndpointStatus::query()
            ->where('is_active', 0)
            ->update(['is_active' => 1]);
    }

    /**
     * Matikan saklar "Endpoint Aktif" untuk satu endpoint, meniru persis apa yang dilakukan
     * halaman Status API Eksternal lewat ApiEndpointSettings::setActive() — termasuk upsert
     * manualnya (model ini sengaja tanpa $fillable, ikut konvensi model lain di app).
     *
     * $endpointKey memakai kosakata yang sama dengan ApiEndpointDoc::key(), mis. 'shipment-show'.
     */
    protected function disableExternalApiEndpoint(string $endpointKey): void
    {
        $row = ExternalApiEndpointStatus::where('endpoint_key', '=', $endpointKey)->first();

        if (! $row) {
            $row = new ExternalApiEndpointStatus();
            $row->endpoint_key = $endpointKey;
            $row->is_public_docs_show = false;
        }

        $row->is_active = false;
        $row->save();
    }
}
