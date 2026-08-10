<?php

namespace App\Http\Controllers\ExternalApi\V1\Concerns;

use App\ExternalApi\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Paginasi, urutan, dan pencarian opsional untuk endpoint GET (daftar)
 * External API — dipakai seragam di semua endpoint daftar (units,
 * warehouses, warehouse_types, cash_categories, sales, armada):
 *
 *   ?page=&per_page=   paginasi. Tanpa ?page=, seluruh baris aktif
 *                      dikembalikan sekaligus. Lihat respondList().
 *   ?sort=             urutan kustom, format "kunci:arah" dipisah koma,
 *                      mis. "unit_name:asc,created_at:desc". Kunci yang
 *                      diperbolehkan HANYA nama field yang benar-benar
 *                      dikembalikan endpoint itu (atau created_at/
 *                      updated_at) — dipetakan lewat parameter $sortable
 *                      pada respondList(), bukan nama kolom basis data
 *                      apa adanya. Kunci atau arah yang tidak dikenal
 *                      dilewati diam-diam (bukan galat 422); kalau seluruh
 *                      butir ?sort= tidak ada yang sah, urutan bawaan
 *                      endpoint itu yang tetap berlaku. Lihat applySort().
 *   ?search=           pencarian %LIKE% satu kata kunci di antara beberapa
 *                      kolom teks yang paling masuk akal untuk endpoint
 *                      itu (OR antar kolom) — dipetakan lewat parameter
 *                      $searchable. Lihat applySearch().
 *
 * Bentuk meta ({total, per_page, current_page, next_page_exists,
 * total_page}) selalu sama lewat ApiResponse::list()/paginated(), apa pun
 * kombinasi ketiga parameter di atas yang dipakai pemanggil.
 */
trait HandlesListQueryParams
{
    /** Baris per halaman kalau pemanggil tidak menyebutkan ?per_page=. */
    private int $defaultPerPage = 20;

    /** Batas atas ?per_page= supaya satu permintaan tidak menarik seluruh tabel. */
    private int $maxPerPage = 100;

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query  sudah berisi
     *         seluruh penyaringan/urutan bawaan/pemilihan kolom yang
     *         relevan (mis. status aktif, kolom aman untuk pihak ketiga) —
     *         trait ini hanya menambah ?sort=/?search=/?page= di atasnya,
     *         tidak menambah aturan bisnis apa pun.
     * @param  array<string, string>  $sortable  peta kunci publik (dipakai
     *         pemanggil pada ?sort=) -> ekspresi kolom SQL yang sebenarnya,
     *         mis. ['gudang_id' => 'id', 'nama' => 'warehouse_name']. Kunci
     *         yang bukan field yang dikembalikan endpoint ini (atau bukan
     *         created_at/updated_at) sengaja tidak dimasukkan ke peta ini,
     *         supaya otomatis dilewati oleh applySort().
     * @param  array<int, string>  $searchable  daftar ekspresi kolom SQL
     *         yang ikut dicari lewat ?search= (OR antar kolom).
     * @param  string|null  $tieBreaker  kolom (kunci utama) yang ditambahkan
     *         sebagai urutan penentu akhir setelah ?sort= kustom diterapkan,
     *         supaya hasil paginasi tetap stabil walau kunci urutan yang
     *         diminta punya nilai kembar. Diabaikan kalau ?sort= tidak
     *         mengubah apa pun (urutan bawaan query sudah punya penentu
     *         akhir sendiri).
     */
    private function respondList(
        $query,
        Request $request,
        callable $present,
        array $sortable = [],
        array $searchable = [],
        ?string $tieBreaker = null,
    ): JsonResponse {
        $this->applySearch($query, $request, $searchable);
        $this->applySort($query, $request, $sortable, $tieBreaker);

        if (! $request->has('page')) {
            return ApiResponse::list($query->get(), $present);
        }

        $perPage = (int) $request->input('per_page', $this->defaultPerPage);
        $perPage = max(1, min($perPage, $this->maxPerPage));

        return ApiResponse::paginated($query->paginate($perPage), $present);
    }

    /**
     * ?search=kata -> WHERE (kolom1 LIKE %kata% OR kolom2 LIKE %kata% ...).
     * Kosong atau tidak dikirim -> tidak menyaring apa pun. $searchable
     * kosong (endpoint belum memetakan kolom mana yang masuk akal dicari)
     * -> ?search= diabaikan diam-diam, bukan galat.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<int, string>  $searchable
     */
    private function applySearch($query, Request $request, array $searchable): void
    {
        $keyword = trim((string) $request->input('search', ''));

        if ($keyword === '' || $searchable === []) {
            return;
        }

        $query->where(function ($inner) use ($searchable, $keyword) {
            foreach ($searchable as $column) {
                $inner->orWhere($column, 'like', '%'.$keyword.'%');
            }
        });
    }

    /**
     * ?sort=kunci1:arah1,kunci2:arah2,... -> ->orderBy() berurutan sesuai
     * yang diminta, MENGGANTIKAN urutan bawaan query (lewat ->reorder())
     * begitu ada satu saja butir yang sah — kalau tidak, urutan bawaan
     * dibiarkan apa adanya. Setiap butir divalidasi sendiri-sendiri:
     * kunci yang tidak ada di $sortable, atau arah selain asc/desc
     * (tanpa peduli besar/kecil huruf), dilewati satu-satu tanpa
     * menggagalkan butir lain yang sah dalam ?sort= yang sama.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<string, string>  $sortable
     */
    private function applySort($query, Request $request, array $sortable, ?string $tieBreaker): void
    {
        $raw = trim((string) $request->input('sort', ''));

        if ($raw === '' || $sortable === []) {
            return;
        }

        $applied = false;

        foreach (explode(',', $raw) as $part) {
            $part = trim($part);

            if ($part === '' || ! str_contains($part, ':')) {
                continue;
            }

            [$key, $direction] = array_map('trim', explode(':', $part, 2));
            $direction = strtolower($direction);

            if (! isset($sortable[$key]) || ! in_array($direction, ['asc', 'desc'], true)) {
                continue;
            }

            if (! $applied) {
                $query->reorder();
                $applied = true;
            }

            $query->orderBy($sortable[$key], $direction);
        }

        if ($applied && $tieBreaker !== null) {
            $query->orderBy($tieBreaker, 'asc');
        }
    }
}
