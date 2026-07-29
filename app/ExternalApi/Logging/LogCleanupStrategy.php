<?php

namespace App\ExternalApi\Logging;

use Illuminate\Support\Carbon;

/**
 * Cara-cara pembersihan log yang boleh dipilih admin.
 *
 * Setiap strategi hanya bertugas menjawab satu pertanyaan: "hapus log yang
 * lebih tua dari kapan?". Menambah strategi baru cukup menambah satu baris di
 * config('externalapi.log_cleanup') — halaman log dan endpoint pembersihan
 * ikut menyesuaikan sendiri tanpa perubahan kode.
 *
 * Bentuk satu baris config:
 *   'kunci' => ['label' => 'Teks di UI', 'days' => 30]
 *
 * `days` bernilai:
 *   angka  → hapus yang lebih tua dari sekian hari
 *   0      → hapus semuanya
 *   null   → admin memilih sendiri tanggal & waktunya
 */
final class LogCleanupStrategy
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly ?int $days,
    ) {
    }

    /** Strategi ini meminta admin memilih tanggal sendiri. */
    public function needsDate(): bool
    {
        return $this->days === null;
    }

    /** Strategi ini menghapus seluruh isi log. */
    public function deletesEverything(): bool
    {
        return $this->days === 0;
    }

    /**
     * Batas waktu pembersihan. Null berarti "hapus semua".
     *
     * @param  string|null  $chosenDate  tanggal pilihan admin, hanya dipakai
     *                                   oleh strategi yang needsDate()
     */
    public function boundary(?string $chosenDate = null): ?Carbon
    {
        if ($this->deletesEverything()) {
            return null;
        }

        if ($this->needsDate()) {
            return $chosenDate ? Carbon::parse($chosenDate) : now();
        }

        return now()->subDays($this->days);
    }

    /**
     * @return array<string, self>
     */
    public static function all(): array
    {
        $out = [];

        foreach ((array) config('externalapi.log_cleanup', []) as $key => $row) {
            $out[$key] = new self(
                (string) $key,
                (string) ($row['label'] ?? $key),
                array_key_exists('days', $row) ? ($row['days'] === null ? null : (int) $row['days']) : null,
            );
        }

        return $out;
    }

    public static function find(?string $key): ?self
    {
        return self::all()[$key] ?? null;
    }
}
