<?php

namespace App\ExternalApi\Http;

use Illuminate\Http\JsonResponse;

/**
 * Bentuk baku respons External API.
 *
 * Aplikasi ini belum punya standar respons API — endpoint internal yang ada
 * mengembalikan bermacam bentuk (angka 1, {status:-1}, koleksi Eloquent polos)
 * karena hanya dipakai jQuery milik sendiri. Bentuk itu tidak layak dipakai
 * pihak ketiga, jadi External API memakai standar tersendiri yang ditetapkan
 * di kelas ini dan TIDAK mengubah apa pun di API internal.
 *
 * Sukses:
 *   { "success": true, "data": ..., "meta": { ... } }
 *
 * Gagal:
 *   { "success": false, "error": { "code": "...", "message": "...", "details": ... } }
 *
 * `code` adalah string stabil yang boleh dijadikan pegangan klien; `message`
 * boleh berubah sewaktu-waktu karena ditujukan untuk dibaca manusia.
 *
 * Kode error itu sendiri TIDAK lagi didefinisikan di sini — lihat
 * App\ExternalApi\Errors\ErrorCatalog, satu-satunya sumber kode error di seluruh External API
 * (dipisah dari kelas ini karena daftarnya terus bertambah seiring endpoint baru, sementara
 * kelas ini murni membentuk amplop respons).
 */
class ApiResponse
{
    /**
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success($data = null, array $meta = [], int $httpStatus = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $httpStatus);
    }

    /**
     * @param  mixed  $details
     */
    public static function error(string $code, string $message, int $httpStatus = 400, $details = null): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
        ], $httpStatus);
    }

    /**
     * Respons daftar terpaginasi.
     *
     * Standar paginasi External API: meta selalu rata (bukan dibungkus lagi
     * di dalam sub-objek seperti `meta.pagination`), dan bentuknya PERSIS
     * sama dengan yang dipakai list() di bawah untuk daftar yang tidak
     * dipaginasi — pemanggil tidak perlu menangani dua bentuk meta berbeda
     * tergantung ada tidaknya ?page= pada permintaan.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @param  callable|null  $transform  pemeta satu baris menjadi array
     */
    public static function paginated($paginator, ?callable $transform = null): JsonResponse
    {
        $items = collect($paginator->items());
        if ($transform) {
            $items = $items->map($transform);
        }

        return self::success($items->values()->all(), [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'next_page_exists' => $paginator->currentPage() < $paginator->lastPage(),
            'total_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Respons daftar utuh, TANPA paginasi di sisi server (seluruh baris
     * dikembalikan sekaligus) — tapi tetap memakai bentuk meta yang PERSIS
     * sama dengan paginated(), sebagai "satu halaman berisi semuanya":
     * current_page selalu 1, total_page selalu 1, next_page_exists selalu
     * false. Ini yang membuat endpoint "paginasi opsional" (kirim ?page=
     * untuk mengaktifkan, atau tidak sama sekali untuk dapat semuanya)
     * tidak pernah mengubah bentuk meta tergantung mana yang dipakai
     * pemanggil.
     *
     * @param  iterable  $items
     * @param  callable|null  $transform  pemeta satu baris menjadi array
     */
    public static function list(iterable $items, ?callable $transform = null): JsonResponse
    {
        $collection = collect($items);
        if ($transform) {
            $collection = $collection->map($transform);
        }

        $total = $collection->count();

        return self::success($collection->values()->all(), [
            'total' => $total,
            'per_page' => $total,
            'current_page' => 1,
            'next_page_exists' => false,
            'total_page' => 1,
        ]);
    }
}
