<?php

namespace App\Synchronization;

use App\Models\SyncExecution;

/**
 * Menghitung status prasyarat satu langkah berdasarkan riwayat eksekusi.
 *
 * Urutan sinkronisasi penting: mis. Produk butuh Satuan & Kategori lebih dulu.
 * Kelas ini yang menerjemahkan ketergantungan itu menjadi informasi yang bisa
 * ditampilkan wizard sekaligus divalidasi ulang di sisi server.
 */
class PrerequisiteChecker
{
    /**
     * @param  array<string, SyncExecution>  $latest
     * @return array<int, array<string, mixed>>
     */
    public function evaluate(SyncFlow $flow, SyncStep $step, array $latest): array
    {
        $steps = $flow->stepsByKey();
        $out = [];

        foreach ($step->prerequisites as $key) {
            $required = $steps[$key] ?? null;
            $execution = $latest[$key] ?? null;
            $satisfied = $execution !== null && $execution->status === SyncStatus::SUCCESS;

            $out[] = [
                'key' => $key,
                'title' => $required?->title ?? $key,
                'satisfied' => $satisfied,
                'reason' => $this->reason($required?->title ?? $key, $execution),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, SyncExecution>  $latest
     * @return array<int, string> Judul langkah prasyarat yang belum terpenuhi.
     */
    public function unmet(SyncFlow $flow, SyncStep $step, array $latest): array
    {
        return collect($this->evaluate($flow, $step, $latest))
            ->reject(fn (array $row) => $row['satisfied'])
            ->pluck('title')
            ->values()
            ->all();
    }

    private function reason(string $title, ?SyncExecution $execution): string
    {
        if ($execution === null) {
            return $title.' belum pernah dijalankan.';
        }

        return match ($execution->status) {
            SyncStatus::SUCCESS => $title.' sudah tersinkronisasi.',
            SyncStatus::FAILED => $title.' gagal pada eksekusi terakhir, ulangi langkah tersebut.',
            SyncStatus::RUNNING => $title.' masih berjalan.',
            default => $title.' belum selesai dijalankan.',
        };
    }
}
