<?php

namespace App\Synchronization;

use Carbon\CarbonImmutable;

/**
 * Hasil satu kali eksekusi langkah sinkronisasi.
 *
 * Objek ini yang dipakai wizard untuk menampilkan ringkasan eksekusi dan
 * rincian hasil sinkronisasi.
 */
class SyncStepResult
{
    /** @var array<int, string> */
    public array $errors = [];

    /** Catatan informatif yang bukan kegagalan (mis. baris yang diadopsi). */
    /** @var array<int, string> */
    public array $notices = [];

    /** @var array<string, mixed> */
    public array $details = [];

    /**
     * Pesan error PMO mentah di balik sebuah fallback sumber data (mis. /getUnits gagal
     * jadi langkah ini jatuh ke sumber cadangan) — ditampilkan di wizard sebagai accordion
     * tertutup terpisah dari `notices`, supaya operator bisa membuka sendiri kalau perlu
     * tanpa layar dipenuhi teks error di awal.
     */
    public ?string $sourceError = null;

    public int $processed = 0;

    public int $inserted = 0;

    public int $updated = 0;

    public int $failed = 0;

    public int $skipped = 0;

    public string $status = SyncStatus::NOT_EXECUTED;

    public ?string $message = null;

    public ?CarbonImmutable $startedAt = null;

    public ?CarbonImmutable $finishedAt = null;

    public static function start(): self
    {
        $result = new self();
        $result->status = SyncStatus::RUNNING;
        $result->startedAt = CarbonImmutable::now();

        return $result;
    }

    public function succeed(?string $message = null): self
    {
        $this->status = SyncStatus::SUCCESS;
        $this->message = $message;

        return $this->stop();
    }

    public function fail(string $message): self
    {
        $this->status = SyncStatus::FAILED;
        $this->message = $message;

        return $this->stop();
    }

    /**
     * Sebagian baris berhasil, sebagian gagal (GitHub #184) — dipakai finish()
     * di bawah, bukan dipanggil manual dari step manapun.
     */
    public function partial(string $message): self
    {
        $this->status = SyncStatus::PARTIAL;
        $this->message = $message;

        return $this->stop();
    }

    /**
     * Selesaikan eksekusi:
     * - tidak ada baris gagal -> SUCCESS.
     * - ada baris gagal TAPI ada juga yang berhasil (insert/update) -> PARTIAL,
     *   operator bisa memilih lanjut ke langkah berikutnya memakai baris yang
     *   berhasil tanpa menunggu baris yang gagal diperbaiki (GitHub #184).
     * - semua baris gagal, tidak ada satu pun yang berhasil -> FAILED.
     */
    public function finish(?string $successMessage = null): self
    {
        if ($this->failed > 0 && ($this->inserted + $this->updated) > 0) {
            return $this->partial(
                $this->failed.' dari '.$this->processed.' baris gagal disinkronkan, '
                .($this->inserted + $this->updated).' baris lainnya berhasil dan bisa dilanjutkan '
                .'ke langkah berikutnya. Perbaiki masalah di atas lalu jalankan ulang langkah ini '
                .'kapan saja untuk menyinkronkan baris yang gagal.'
            );
        }

        if ($this->failed > 0) {
            return $this->fail(
                $this->failed.' dari '.$this->processed.' baris gagal disinkronkan.'
            );
        }

        return $this->succeed($successMessage);
    }

    public function addError(string $message, int $limit = 200): self
    {
        if (count($this->errors) < $limit) {
            $this->errors[] = $message;
        } elseif (count($this->errors) === $limit) {
            $this->errors[] = '… daftar dipotong pada '.$limit.' baris. Sisanya ada di storage/logs.';
        }

        return $this;
    }

    public function withSourceError(string $message): self
    {
        $this->sourceError = $message;

        return $this;
    }

    public function addNotice(string $message, int $limit = 200): self
    {
        if (count($this->notices) < $limit) {
            $this->notices[] = $message;
        } elseif (count($this->notices) === $limit) {
            $this->notices[] = '… daftar dipotong pada '.$limit.' baris.';
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public function withDetails(array $details): self
    {
        $this->details = array_merge($this->details, $details);

        return $this;
    }

    public function durationMs(): int
    {
        if (! $this->startedAt || ! $this->finishedAt) {
            return 0;
        }

        return (int) round(($this->finishedAt->getPreciseTimestamp(3) - $this->startedAt->getPreciseTimestamp(3)));
    }

    private function stop(): self
    {
        $this->finishedAt = CarbonImmutable::now();

        return $this;
    }
}
