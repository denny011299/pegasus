<?php

namespace App\Http\Controllers\ExternalApi\V1\Concerns;

use App\ExternalApi\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Paginasi opsional untuk endpoint GET (daftar) External API: kirim ?page=
 * untuk mengaktifkannya (dengan ?per_page= opsional), atau tidak sama
 * sekali untuk mendapat seluruh baris aktif sekaligus — dipakai seragam di
 * semua endpoint daftar (units, warehouses, warehouse_types,
 * cash_categories, sales, armada) supaya bentuk meta-nya selalu sama
 * ({total, per_page, current_page, next_page_exists, total_page}) apa pun
 * yang dipakai pemanggil, lewat ApiResponse::list()/paginated().
 *
 * Query yang dioper ke respondList() HARUS sudah berisi seluruh
 * penyaringan/pengurutan/pemilihan kolom yang relevan (mis. status aktif,
 * kolom aman untuk pihak ketiga) — trait ini hanya memutuskan penuh vs
 * terpaginasi, tidak menambah aturan bisnis apa pun.
 */
trait HandlesOptionalPagination
{
    /** Baris per halaman kalau pemanggil tidak menyebutkan ?per_page=. */
    private int $defaultPerPage = 20;

    /** Batas atas ?per_page= supaya satu permintaan tidak menarik seluruh tabel. */
    private int $maxPerPage = 100;

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function respondList($query, Request $request, callable $present): JsonResponse
    {
        if (! $request->has('page')) {
            return ApiResponse::list($query->get(), $present);
        }

        $perPage = (int) $request->input('per_page', $this->defaultPerPage);
        $perPage = max(1, min($perPage, $this->maxPerPage));

        return ApiResponse::paginated($query->paginate($perPage), $present);
    }
}
