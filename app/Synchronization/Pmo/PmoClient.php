<?php

namespace App\Synchronization\Pmo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Klien HTTP sederhana ke server PMO.
 *
 * Setiap request membawa header X-API-Key (dari PMO_API_KEY di .env).
 *
 * Kontrak respons PMO (dikonfirmasi PMO): `{"<itemsKey>": [...], "pagination":
 * {"total", "page", "limit", "total_pages"}}`. Tanpa query params, endpoint
 * otomatis membalas halaman pertama — satu-satunya parameter paginasi yang
 * boleh kita kirim adalah "page" (PMO tidak menerima permintaan mengubah
 * "limit"). Kalau $query membawa filter yang membuat PMO menganggapnya
 * "single get" (mis. product_id), responsnya tidak mengandung "pagination"
 * dan daftar barisnya berisi maksimal satu baris — dalam hal ini
 * fetchCollection() otomatis berhenti setelah satu kali panggilan.
 *
 * Nama kunci daftar baris TERNYATA tidak seragam antar endpoint (dikonfirmasi
 * 2026-08-22): /getProducts dan /getShipments memakai "items", tapi
 * /getArmada memakai "data". $itemsKey (default "items") mengakomodasi itu —
 * lihat App\Synchronization\Pmo\PmoEndpoints::itemsKey().
 */
class PmoClient
{
    public function __construct(private readonly array $config)
    {
    }

    public function isConfigured(): bool
    {
        return trim((string) ($this->config['base_url'] ?? '')) !== ''
            && trim((string) ($this->config['api_key'] ?? '')) !== '';
    }

    public function baseUrl(): string
    {
        return rtrim(trim((string) ($this->config['base_url'] ?? '')), '/');
    }

    public function url(string $endpoint): string
    {
        if (preg_match('#^https?://#i', $endpoint) === 1) {
            return $endpoint;
        }

        return $this->baseUrl().'/'.ltrim($endpoint, '/');
    }

    /**
     * Ambil satu koleksi data dari PMO, mengikuti seluruh halaman yang ada
     * (lewat "pagination.total_pages") sampai habis atau menyentuh max_pages.
     * Dibangun di atas fetchPage() — kalau pemanggil ingin mengontrol sendiri
     * perpindahan antar halaman (mis. supaya progresnya bisa ditampilkan di
     * UI), pakai fetchPage() langsung.
     *
     * $query bisa membawa filter khusus PMO (mis. product_id=... untuk
     * "single get") — dikirim apa adanya, tidak ikut diberi parameter
     * paginasi selain "page".
     *
     * @param  array<string, mixed>  $query
     *
     * @throws PmoException
     */
    public function fetchCollection(string $endpoint, array $query = [], string $itemsKey = 'items'): PmoResponse
    {
        $rows = [];
        $meta = [];
        $maxPages = max(1, (int) ($this->config['max_pages'] ?? 200));
        $totalPages = 1;
        $page = 1;
        $url = $this->url($endpoint);

        while ($page <= $totalPages && $page <= $maxPages) {
            $pageResult = $this->fetchPage($endpoint, $query, $page, $itemsKey);

            $rows = array_merge($rows, $pageResult->rows);

            if ($page === 1) {
                $meta = $pageResult->meta;
                $totalPages = $pageResult->totalPages;
                $url = $pageResult->url;
            }

            $page++;
        }

        return new PmoResponse($rows, $meta, $page - 1, $url);
    }

    /**
     * Ambil TEPAT SATU halaman dari PMO, tanpa mengikuti halaman berikutnya.
     * Dipakai saat pemanggil ingin mengontrol sendiri perpindahan antar
     * halaman — biasanya supaya progresnya bisa ditampilkan langsung di UI
     * (lihat App\Synchronization\Contracts\PaginatedStepHandler) — alih-alih
     * menunggu seluruh halaman selesai dalam satu panggilan seperti
     * fetchCollection().
     *
     * $page=1 selalu dikirim tanpa parameter "page" (mengikuti kontrak PMO:
     * tanpa query params = halaman pertama); $page>1 menambahkan "page" ke
     * $query. $itemsKey menyesuaikan endpoint yang membungkus barisnya
     * dengan kunci lain daripada "items" (mis. /getArmada memakai "data").
     *
     * @param  array<string, mixed>  $query
     *
     * @throws PmoException
     */
    public function fetchPage(string $endpoint, array $query, int $page, string $itemsKey = 'items'): PmoPage
    {
        if (! $this->isConfigured()) {
            throw new PmoException(
                'Alamat server PMO belum dikonfigurasi. Isi PMO_BASE_URL dan PMO_API_KEY pada file .env terlebih dahulu.'
            );
        }

        $url = $this->url($endpoint);
        $payload = $this->request($url, $page > 1 ? $query + ['page' => $page] : $query);

        return new PmoPage(
            rows: $this->extractRows($payload, $url, $itemsKey),
            meta: $this->extractMeta($payload),
            page: $page,
            totalPages: $this->extractTotalPages($payload),
            url: $url,
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     *
     * @throws PmoException
     */
    private function request(string $url, array $query): array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-API-Key' => (string) ($this->config['api_key'] ?? '')])
                ->timeout((int) ($this->config['timeout'] ?? 60))
                ->connectTimeout((int) ($this->config['connect_timeout'] ?? 10))
                ->withOptions(['verify' => (bool) ($this->config['verify_ssl'] ?? true)])
                ->get($url, $query);
        } catch (ConnectionException $e) {
            throw new PmoException('Tidak bisa menghubungi server PMO ('.$url.'): '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw new PmoException(
                'Server PMO membalas dengan status '.$response->status().' untuk '.$url.'.'
            );
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new PmoException('Respons PMO dari '.$url.' bukan JSON yang valid.');
        }

        return $payload;
    }

    /**
     * $itemsKey wajib ada dan berupa daftar — bukan sinyal "PMO tidak punya
     * data" (itu sah, hasilnya cukup daftar kosong), melainkan kegagalan di
     * level payload: kontraknya dilanggar, jadi seluruh langkah harus gagal
     * (bukan diam-diam dianggap nol baris).
     *
     * @param  array<mixed>  $payload
     * @return array<int, array<string, mixed>>
     *
     * @throws PmoException
     */
    private function extractRows(array $payload, string $url, string $itemsKey): array
    {
        $items = $payload[$itemsKey] ?? null;

        if (! is_array($items) || ! array_is_list($items)) {
            throw new PmoException('Respons PMO dari '.$url.' tidak memiliki "'.$itemsKey.'" berupa daftar.');
        }

        $rows = [];
        foreach ($items as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            } elseif (is_object($row)) {
                $rows[] = (array) $row;
            }
        }

        return $rows;
    }

    /**
     * Jumlah total halaman menurut "pagination.total_pages". Respons "single
     * get" (mis. hasil filter product_id) tidak membawa "pagination" sama
     * sekali — dianggap satu halaman, tidak perlu panggilan lanjutan.
     *
     * @param  array<mixed>  $payload
     */
    private function extractTotalPages(array $payload): int
    {
        $pagination = $payload['pagination'] ?? null;

        if (! is_array($pagination) || ! isset($pagination['total_pages']) || ! is_numeric($pagination['total_pages'])) {
            return 1;
        }

        return max(0, (int) $pagination['total_pages']);
    }

    /**
     * Informasi paginasi dari respons halaman pertama saja, supaya bisa
     * ditampilkan apa adanya pada hasil eksekusi.
     *
     * @param  array<mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractMeta(array $payload): array
    {
        $pagination = $payload['pagination'] ?? null;
        if (! is_array($pagination)) {
            return [];
        }

        $meta = [];

        if (isset($pagination['total']) && is_numeric($pagination['total'])) {
            $meta['Total Data di PMO'] = (int) $pagination['total'];
        }

        if (isset($pagination['total_pages']) && is_numeric($pagination['total_pages'])) {
            $meta['Jumlah Halaman'] = (int) $pagination['total_pages'];
        }

        return $meta;
    }
}
