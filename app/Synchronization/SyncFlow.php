<?php

namespace App\Synchronization;

/**
 * Definisi statis satu proses sinkronisasi bisnis.
 *
 * Alur tidak bisa diubah pengguna dan tidak punya CRUD. Menambah alur baru
 * cukup dengan membuat turunan kelas ini lalu mendaftarkannya di
 * config/synchronization.php.
 */
abstract class SyncFlow
{
    /** Identitas alur, dipakai di URL. */
    abstract public function key(): string;

    abstract public function title(): string;

    /** Deskripsi singkat untuk kartu di Pusat Sinkronisasi. */
    abstract public function description(): string;

    /** Tujuan dijalankannya alur ini. */
    abstract public function purpose(): string;

    /**
     * Data apa saja yang akan disinkronkan.
     *
     * @return array<int, string>
     */
    abstract public function dataSynced(): array;

    /** Kapan pengguna sebaiknya menjalankan alur ini. */
    abstract public function whenToRun(): string;

    /**
     * Catatan penting / peringatan sebelum menjalankan alur.
     *
     * @return array<int, string>
     */
    public function warnings(): array
    {
        return [];
    }

    /** Ikon feather yang dipakai kartu dan header wizard. */
    public function icon(): string
    {
        return 'refresh-cw';
    }

    /**
     * Daftar langkah berurutan.
     *
     * @return array<int, SyncStep>
     */
    abstract public function steps(): array;

    public function findStep(string $key): ?SyncStep
    {
        foreach ($this->steps() as $step) {
            if ($step->key === $key) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @return array<string, SyncStep>
     */
    public function stepsByKey(): array
    {
        $out = [];
        foreach ($this->steps() as $step) {
            $out[$step->key] = $step;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'title' => $this->title(),
            'description' => $this->description(),
            'purpose' => $this->purpose(),
            'data_synced' => $this->dataSynced(),
            'when_to_run' => $this->whenToRun(),
            'warnings' => $this->warnings(),
            'icon' => $this->icon(),
            'steps' => array_map(static fn (SyncStep $step) => $step->toArray(), $this->steps()),
        ];
    }
}
