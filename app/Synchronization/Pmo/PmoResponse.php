<?php

namespace App\Synchronization\Pmo;

/**
 * Hasil pengambilan data dari PMO: daftar baris mentah + informasi tambahan
 * yang ikut dikirim server (dipakai untuk ditampilkan di hasil eksekusi).
 */
class PmoResponse
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $meta = [],
        public readonly int $pages = 1,
        public readonly string $url = '',
    ) {
    }

    public function count(): int
    {
        return count($this->rows);
    }
}
