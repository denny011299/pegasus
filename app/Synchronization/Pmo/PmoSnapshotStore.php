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

        if (! $row) {
            return $this->refresh($flowKey, $endpointKey);
        }

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
     * Selalu tarik ulang dari PMO dan gantikan potret sebelumnya.
     *
     * @throws PmoException
     */
    public function refresh(string $flowKey, string $endpointKey): PmoSnapshot
    {
        $response = $this->client->fetchCollection($this->endpoint($endpointKey));
        $fetchedAt = Carbon::now();
        $user = Session::get('user');

        SyncSnapshot::updateOrCreate(
            ['flow_key' => $flowKey, 'endpoint_key' => $endpointKey],
            [
                'url' => $response->url,
                'payload' => $this->encode(['rows' => $response->rows, 'meta' => $response->meta]),
                'row_count' => $response->count(),
                'fetched_at' => $fetchedAt,
                'fetched_by' => $user->staff_id ?? null,
            ]
        );

        return new PmoSnapshot(
            rows: $response->rows,
            meta: $response->meta,
            fetchedAt: $fetchedAt,
            url: $response->url,
            justFetched: true,
        );
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
     * @throws PmoException
     */
    private function endpoint(string $endpointKey): string
    {
        // Diambil langsung dari array supaya kunci yang mengandung titik tetap
        // terbaca apa adanya oleh config().
        $endpoints = (array) config('synchronization.endpoints', []);
        $endpoint = (string) ($endpoints[$endpointKey] ?? '');

        if ($endpoint === '') {
            throw new PmoException(
                'Endpoint PMO "'.$endpointKey.'" belum terdaftar di config/synchronization.php.'
            );
        }

        return $endpoint;
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
