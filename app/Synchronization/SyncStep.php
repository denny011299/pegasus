<?php

namespace App\Synchronization;

use App\Synchronization\Contracts\SyncStepHandler;

/**
 * Definisi statis satu langkah sinkronisasi.
 *
 * Isinya murni deskriptif — tidak ada logika sinkronisasi di sini. Eksekusi
 * didelegasikan ke kelas handler yang di-resolve lewat container.
 */
class SyncStep
{
    /**
     * @param  string  $key  Identitas langkah, dipakai di URL dan pencatatan riwayat.
     * @param  string  $title  Judul yang dibaca pengguna operasional.
     * @param  string  $description  Penjelasan singkat langkah ini.
     * @param  string  $dataSynced  Data apa yang disinkronkan.
     * @param  string  $why  Kenapa langkah ini dibutuhkan.
     * @param  string  $dependents  Data lain yang bergantung pada langkah ini.
     * @param  string  $expectation  Apa yang terjadi setelah langkah dijalankan.
     * @param  array<int, string>  $prerequisites  Daftar key langkah yang wajib berhasil lebih dulu.
     * @param  class-string<SyncStepHandler>  $handler
     * @param  array<int, string>  $notes  Catatan/peringatan tambahan.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $description,
        public readonly string $dataSynced,
        public readonly string $why,
        public readonly string $dependents,
        public readonly string $expectation,
        public readonly array $prerequisites,
        public readonly string $handler,
        public readonly array $notes = [],
    ) {
    }

    public function makeHandler(): SyncStepHandler
    {
        $handler = app($this->handler);

        if (! $handler instanceof SyncStepHandler) {
            throw new \LogicException(
                'Handler '.$this->handler.' harus mengimplementasikan '.SyncStepHandler::class.'.'
            );
        }

        return $handler;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'data_synced' => $this->dataSynced,
            'why' => $this->why,
            'dependents' => $this->dependents,
            'expectation' => $this->expectation,
            'prerequisites' => $this->prerequisites,
            'notes' => $this->notes,
        ];
    }
}
