<?php

namespace App\Synchronization\Pmo;

use App\Models\SyncSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

/**
 * Menyimpan hasil satu kali panggilan ke PMO agar dipakai bersama oleh seluruh
 * langkah pada satu alur.
 *
 * Disimpan di tabel sendiri, bukan di cache Laravel: CACHE_STORE proyek ini
 * adalah "database", sehingga `php artisan cache:clear` yang rutin dijalankan
 * akan menghapus data sinkronisasi yang sedang berjalan. Tabel sendiri juga
 * memberi jejak audit — hasil sinkronisasi bisa dirunut ke payload persisnya.
 */
class PmoSnapshotStore
{
    public function __construct(private readonly PmoClient $client)
    {
    }

    /**
     * Ambil potret yang ada; tarik dari PMO bila belum ada.
     *
     * @throws PmoException
     */
    public function get(string $flowKey, string $endpointKey): PmoSnapshot
    {
        $row = $this->find($flowKey, $endpointKey);

        return $row ? $this->fromRow($row) : $this->refresh($flowKey, $endpointKey);
    }

    /**
     * Sama seperti get(), tapi TIDAK PERNAH menarik ulang PMO saat potretnya
     * belum ada — gagal jelas sebagai gantinya.
     *
     * get() aman auto-refresh untuk endpoint tanpa parameter wajib (mis.
     * /getProducts, yang selalu bisa "ambil semua tanpa filter"). Untuk
     * endpoint yang query-nya wajib (mis. /getShipments dengan
     * date_start/date_end, lihat SyncStep::$queryFields), auto-refresh di
     * sini berarti memanggil PMO tanpa parameter itu — request yang salah,
     * bukan cuma potret yang basi. Langkah lanjutan (mis. Step 2 "Sinkronisasi
     * Pengiriman") pakai method ini supaya SELALU membaca hasil fetchPage()+
     * finalizePages() milik langkah pengambilan datanya sendiri, tidak pernah
     * memanggil PMO sendiri.
     *
     * @throws PmoException
     */
    public function getExisting(string $flowKey, string $endpointKey): PmoSnapshot
    {
        $row = $this->find($flowKey, $endpointKey);

        if (! $row) {
            throw new PmoException(
                'Belum ada data "'.$endpointKey.'" yang diambil untuk alur ini — jalankan langkah '
                .'pengambilan data PMO terlebih dahulu.'
            );
        }

        return $this->fromRow($row);
    }

    private function fromRow(SyncSnapshot $row): PmoSnapshot
    {
        $payload = $this->decode($row->payload);

        return new PmoSnapshot(
            rows: $payload['rows'] ?? [],
            meta: $payload['meta'] ?? [],
            fetchedAt: $row->fetched_at ?? $row->created_at ?? Carbon::now(),
            url: (string) $row->url,
            justFetched: false,
        );
    }

    /**
     * Selalu tarik ulang dari PMO (seluruh halaman sekaligus, satu
     * panggilan) dan gantikan potret sebelumnya.
     *
     * Dipakai jalur sekali-jalan (handle()). Untuk progres per halaman,
     * pakai fetchPage() + finalizePages().
     *
     * @throws PmoException
     */
    public function refresh(string $flowKey, string $endpointKey): PmoSnapshot
    {
        $response = $this->client->fetchCollection(
            PmoEndpoints::resolve($endpointKey),
            itemsKey: PmoEndpoints::itemsKey($endpointKey),
        );

        return $this->persist($flowKey, $endpointKey, $response->rows, $response->meta, $response->url);
    }

    /**
     * Ambil TEPAT SATU halaman dari PMO dan tambahkan ke buffer sesi —
     * belum menyentuh potret sungguhan, jadi langkah lain yang membaca lewat
     * get()/refresh() tidak melihat data yang belum lengkap. $page=1 selalu
     * memulai buffer baru, menimpa sisa percobaan sebelumnya yang belum
     * selesai (mis. karena browser tertutup di tengah jalan).
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed> page, total_pages, rows_so_far, is_last_page.
     *
     * @throws PmoException
     */
    public function fetchPage(string $flowKey, string $endpointKey, int $page, array $query = []): array
    {
        $pageResult = $this->client->fetchPage(
            PmoEndpoints::resolve($endpointKey),
            $query,
            $page,
            PmoEndpoints::itemsKey($endpointKey),
        );
        $bufferKey = $this->bufferKey($flowKey, $endpointKey);

        $buffer = $page > 1 ? Session::get($bufferKey) : null;
        $buffer ??= ['rows' => [], 'meta' => [], 'url' => $pageResult->url];

        $buffer['rows'] = array_merge($buffer['rows'], $pageResult->rows);
        if ($page === 1) {
            $buffer['meta'] = $pageResult->meta;
            $buffer['url'] = $pageResult->url;
        }

        Session::put($bufferKey, $buffer);

        return [
            'page' => $pageResult->page,
            'total_pages' => $pageResult->totalPages,
            'rows_so_far' => count($buffer['rows']),
            'is_last_page' => $pageResult->isLastPage(),
        ];
    }

    /**
     * Pindahkan buffer sesi (diisi lewat fetchPage()) ke potret sungguhan,
     * lalu bersihkan buffernya. Dipanggil setelah halaman terakhir selesai
     * diambil.
     *
     * @throws PmoException
     */
    public function finalizePages(string $flowKey, string $endpointKey): PmoSnapshot
    {
        $bufferKey = $this->bufferKey($flowKey, $endpointKey);
        $buffer = Session::get($bufferKey);

        if (! is_array($buffer)) {
            throw new PmoException(
                'Belum ada halaman yang diambil untuk "'.$endpointKey.'" pada sesi ini — '
                .'mulai lagi dari halaman 1.'
            );
        }

        Session::forget($bufferKey);

        return $this->persist($flowKey, $endpointKey, $buffer['rows'], $buffer['meta'], $buffer['url']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    private function persist(string $flowKey, string $endpointKey, array $rows, array $meta, string $url): PmoSnapshot
    {
        $fetchedAt = Carbon::now();
        $user = Session::get('user');

        SyncSnapshot::updateOrCreate(
            ['flow_key' => $flowKey, 'endpoint_key' => $endpointKey],
            [
                'url' => $url,
                'payload' => $this->encode(['rows' => $rows, 'meta' => $meta]),
                'row_count' => count($rows),
                'fetched_at' => $fetchedAt,
                'fetched_by' => $user->staff_id ?? null,
            ]
        );

        return new PmoSnapshot(
            rows: $rows,
            meta: $meta,
            fetchedAt: $fetchedAt,
            url: $url,
            justFetched: true,
        );
    }

    private function bufferKey(string $flowKey, string $endpointKey): string
    {
        return 'sync_fetch_buffer:'.$flowKey.':'.$endpointKey;
    }

    public function forget(string $flowKey, ?string $endpointKey = null): void
    {
        $query = SyncSnapshot::where('flow_key', $flowKey);

        if ($endpointKey !== null) {
            $query->where('endpoint_key', $endpointKey);
        }

        $query->delete();
    }

    private function find(string $flowKey, string $endpointKey): ?SyncSnapshot
    {
        return SyncSnapshot::where('flow_key', $flowKey)
            ->where('endpoint_key', $endpointKey)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $packed = gzencode($json, 6);

        return base64_encode($packed !== false ? $packed : $json);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $stored): array
    {
        $raw = base64_decode($stored, true);
        if ($raw === false) {
            return [];
        }

        $json = @gzdecode($raw);
        $decoded = json_decode($json !== false ? $json : $raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
