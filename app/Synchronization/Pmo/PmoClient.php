<?php

namespace App\Synchronization\Pmo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Klien HTTP sederhana ke server PMO.
 *
 * Setiap request membawa header X-API-Key (dari PMO_API_KEY di .env).
 */
class PmoClient
{
    /** Kunci yang lazim dipakai server untuk membungkus daftar baris. */
    private const ROW_WRAPPERS = ['data', 'rows', 'items', 'records', 'result', 'results', 'list'];

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
     * Ambil satu koleksi data dari PMO. Kalau responsnya terpaginasi
     * (mengandung current_page/last_page) halaman berikutnya ikut diambil.
     *
     * @param  array<string, mixed>  $query
     *
     * @throws PmoException
     */
    public function fetchCollection(string $endpoint, array $query = []): PmoResponse
    {
        if (! $this->isConfigured()) {
            throw new PmoException(
                'Alamat server PMO belum dikonfigurasi. Isi PMO_BASE_URL dan PMO_API_KEY pada file .env terlebih dahulu.'
            );
        }

        $url = $this->url($endpoint);
        $rows = [];
        $meta = [];
        $page = 1;
        $maxPages = max(1, (int) ($this->config['max_pages'] ?? 200));

        while ($page <= $maxPages) {
            $payload = $this->request($url, $page > 1 ? $query + ['page' => $page] : $query);

            $rows = array_merge($rows, $this->extractRows($payload));

            if ($page === 1) {
                $meta = $this->extractMeta($payload);
            }

            $lastPage = $this->readPageNumber($payload, ['last_page', 'total_pages', 'lastPage', 'totalPages']);
            $currentPage = $this->readPageNumber($payload, ['current_page', 'page', 'currentPage']) ?? $page;

            if ($lastPage === null || $currentPage >= $lastPage) {
                break;
            }

            $page = $currentPage + 1;
        }

        return new PmoResponse($rows, $meta, $page, $url);
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
     * @param  array<mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(array $payload): array
    {
        if (array_is_list($payload)) {
            return $this->onlyAssocRows($payload);
        }

        foreach (self::ROW_WRAPPERS as $key) {
            $value = $payload[$key] ?? null;

            if (is_array($value) && array_is_list($value)) {
                return $this->onlyAssocRows($value);
            }

            // Bentuk paginator Laravel: {"data": {"data": [...], "current_page": 1}}
            if (is_array($value)) {
                foreach (self::ROW_WRAPPERS as $nested) {
                    $inner = $value[$nested] ?? null;
                    if (is_array($inner) && array_is_list($inner)) {
                        return $this->onlyAssocRows($inner);
                    }
                }
            }
        }

        return [];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function onlyAssocRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $out[] = $row;
            } elseif (is_object($row)) {
                $out[] = (array) $row;
            }
        }

        return $out;
    }

    /**
     * Informasi tambahan dari respons (di luar daftar baris) supaya bisa
     * ditampilkan apa adanya pada hasil eksekusi.
     *
     * @param  array<mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractMeta(array $payload): array
    {
        if (array_is_list($payload)) {
            return [];
        }

        $meta = Arr::except($payload, self::ROW_WRAPPERS);

        if (isset($payload['meta']) && is_array($payload['meta'])) {
            $meta = array_merge($meta, $payload['meta']);
            unset($meta['meta']);
        }

        return array_filter($meta, static fn ($value) => is_scalar($value) || $value === null);
    }

    /**
     * @param  array<mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function readPageNumber(array $payload, array $keys): ?int
    {
        $sources = [$payload];
        foreach (['meta', 'data', 'pagination'] as $wrapper) {
            if (isset($payload[$wrapper]) && is_array($payload[$wrapper])) {
                $sources[] = $payload[$wrapper];
            }
        }

        foreach ($sources as $source) {
            foreach ($keys as $key) {
                if (isset($source[$key]) && is_numeric($source[$key])) {
                    return (int) $source[$key];
                }
            }
        }

        return null;
    }
}
