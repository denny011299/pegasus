<?php

namespace Tests\Workflow;

use App\ExternalApi\Errors\ErrorCatalog;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * Saklar "Endpoint Aktif" (halaman Status API Eksternal) — App\Http\Middleware\
 * AuthenticateExternalApi. Mematikan satu endpoint harus menolak SELURUH permintaan ke endpoint
 * itu dengan error yang benar dan bisa dibaca mesin, BUKAN sekadar gagal begitu saja.
 *
 * Kenapa berkas ini ada: sebelumnya endpoint yang kebetulan dimatikan di DB lokal membuat
 * test-test fungsional External API gagal dengan 503 yang menyesatkan — kelihatan seperti test
 * rusak, padahal aplikasinya berperilaku persis seperti yang dirancang. Sekarang test fungsional
 * memastikan saklarnya menyala (lihat Tests\Support\ActingAsExternalApiClient), dan perilaku
 * "endpoint dimatikan" diuji di sini secara sengaja dan eksplisit.
 *
 * Yang dikunci di sini, sesuai dokumentasi App\ExternalApi\Docs\PlatformErrors:
 *  - HTTP 503 + kode error API_ENDPOINT_DISABLED, dalam amplop error standar External API.
 *  - Berlaku untuk SEMUA pemanggil — termasuk yang membawa API Key valid.
 *  - Dicek SEBELUM autentikasi: permintaan tanpa API Key pun dijawab 503 (bukan 401), karena
 *    endpoint yang mati tidak boleh membocorkan apa pun soal keabsahan kunci.
 *  - Hanya endpoint yang bersangkutan yang terpengaruh; endpoint lain di versi yang sama tetap
 *    jalan normal.
 */
class ExternalApiDisabledEndpointFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    /** Sama persis dengan ApiEndpointDoc::key() milik GET /shipments/{ref_shipment_id}. */
    private const SHIPMENT_SHOW_KEY = 'shipment-show';

    /** Endpoint tetangga pada versi yang sama, dipakai untuk membuktikan efeknya tidak meluber. */
    private const STOCK_CHECK_KEY = 'stok-cek';

    public function test_a_disabled_endpoint_is_rejected_with_the_documented_error_even_with_a_valid_key(): void
    {
        $headers = $this->externalApiHeaders();
        $this->disableExternalApiEndpoint(self::SHIPMENT_SHOW_KEY);

        $response = $this->getJson('/api/external/v1/shipments/SHP-1', $headers);

        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'error' => ['code' => ErrorCatalog::API_ENDPOINT_DISABLED],
        ]);

        // Pesannya harus menyebut endpoint mana yang dimatikan, bukan kalimat generik — inilah
        // yang membedakan "sengaja dimatikan admin" dari kegagalan server biasa di mata pemanggil.
        $message = (string) data_get($response->json(), 'error.message');
        $this->assertNotSame('', trim($message), 'error 503 wajib membawa pesan yang terbaca manusia');
        $this->assertStringContainsStringIgnoringCase(
            'nonaktif',
            $message,
            'pesan harus menjelaskan endpoint-nya sedang dinonaktifkan'
        );
    }

    public function test_a_disabled_endpoint_answers_before_authentication_so_a_missing_key_still_gets_503(): void
    {
        // Sengaja TANPA header API Key. Urutannya penting: pengecekan saklar berada SEBELUM
        // autentikasi, jadi jawabannya 503 (endpoint mati), bukan 401 (kunci tidak ada).
        $this->enableAllExternalApiEndpoints();
        $this->disableExternalApiEndpoint(self::SHIPMENT_SHOW_KEY);

        $this->getJson('/api/external/v1/shipments/SHP-1')
            ->assertStatus(503)
            ->assertJson([
                'success' => false,
                'error' => ['code' => ErrorCatalog::API_ENDPOINT_DISABLED],
            ]);
    }

    public function test_disabling_one_endpoint_does_not_affect_its_neighbours(): void
    {
        $headers = $this->externalApiHeaders();
        $this->disableExternalApiEndpoint(self::SHIPMENT_SHOW_KEY);

        // Endpoint lain pada versi yang sama tidak boleh ikut mati. Yang diuji di sini murni
        // "tidak dijawab 503 oleh saklar" — bukan isi responsnya (itu urusan test endpoint-nya
        // sendiri), supaya test ini tidak ikut rapuh terhadap aturan validasi stok.
        $response = $this->getJson('/api/external/v1/stok-cek?kode_produk=TIDAK-ADA-'.uniqid(), $headers);

        $this->assertNotSame(
            503,
            $response->getStatusCode(),
            'mematikan shipment-show tidak boleh ikut mematikan endpoint lain di versi yang sama'
        );
    }
}
