<?php

namespace App\Synchronization\Pmo;

use Carbon\CarbonInterface;

/**
 * Potret satu kali tarik data dari PMO, dipakai bersama oleh seluruh langkah
 * dalam satu sesi sinkronisasi.
 */
class PmoSnapshot
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $meta,
        public readonly CarbonInterface $fetchedAt,
        public readonly string $url,
        public readonly bool $justFetched,
    ) {
    }

    public function count(): int
    {
        return count($this->rows);
    }

    public function ageInMinutes(): int
    {
        return (int) $this->fetchedAt->diffInMinutes(now());
    }

    public function isStale(): bool
    {
        return $this->ageInMinutes() > (int) config('synchronization.snapshot_stale_after_minutes', 1440);
    }

    /**
     * Keterangan asal data untuk ditampilkan pada hasil eksekusi.
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        $details = [
            'Sumber Data' => $this->url,
            'Data PMO Diambil' => $this->fetchedAt->format('d/m/Y H:i:s')
                .($this->justFetched ? ' (baru saja)' : ' (dipakai ulang)'),
            'Baris Diterima dari PMO' => $this->count(),
        ];

        if ($this->isStale()) {
            $details['Peringatan'] = 'Data PMO sudah lebih dari '
                .(int) config('synchronization.snapshot_stale_after_minutes', 1440)
                .' menit. Jalankan ulang langkah "Ambil & Periksa Data PMO" bila ingin data terbaru.';
        }

        return $details + $this->meta;
    }
}
