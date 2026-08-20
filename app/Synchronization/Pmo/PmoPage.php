<?php

namespace App\Synchronization\Pmo;

/**
 * Hasil pengambilan TEPAT SATU halaman dari PMO (lihat PmoClient::fetchPage()),
 * berbeda dari PmoResponse yang sudah menggabungkan seluruh halaman.
 */
class PmoPage
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $meta,
        public readonly int $page,
        public readonly int $totalPages,
        public readonly string $url,
    ) {
    }

    public function isLastPage(): bool
    {
        return $this->page >= $this->totalPages;
    }
}
