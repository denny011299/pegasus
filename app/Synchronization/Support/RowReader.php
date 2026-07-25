<?php

namespace App\Synchronization\Support;

/**
 * Pembaca baris PMO yang toleran terhadap perbedaan penamaan atribut.
 *
 * Dokumen PMO menyebut daftar atribut di dalamnya sebagai "standar minimum",
 * dan implementasinya masih berjalan, jadi setiap pembacaan menerima beberapa
 * kemungkinan nama kolom.
 */
trait RowReader
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function pick(array $row, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return $default;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function pickString(array $row, array $keys, string $default = ''): string
    {
        $value = $this->pick($row, $keys, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function pickInt(array $row, array $keys, int $default = 0): int
    {
        $value = $this->pick($row, $keys, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    protected function pickList(array $row, array $keys): array
    {
        $value = $this->pick($row, $keys);

        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $out[] = $item;
            } elseif (is_object($item)) {
                $out[] = (array) $item;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     * @return array<string, mixed>|null
     */
    protected function pickObject(array $row, array $keys): ?array
    {
        $value = $this->pick($row, $keys);

        if (is_object($value)) {
            $value = (array) $value;
        }

        return is_array($value) && $value !== [] ? $value : null;
    }

    /**
     * Status Pegasus: 1 = aktif, 0 = nonaktif. PMO bisa mengirim boolean,
     * angka, atau string.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function pickStatus(array $row, array $keys = ['is_active', 'status', 'active']): int
    {
        $value = $this->pick($row, $keys);

        if ($value === null) {
            return 1;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 0 ? 0 : 1;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['0', 'false', 'inactive', 'nonaktif', 'tidak aktif'], true)
            ? 0
            : 1;
    }
}
