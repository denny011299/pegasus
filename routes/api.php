<?php

use App\ExternalApi\Http\ApiResponse;
use App\ExternalApi\Support\ExternalApiPath;
use App\Http\Middleware\AuthenticateExternalApi;
use App\Http\Middleware\LogExternalApiRequest;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute External API
|--------------------------------------------------------------------------
|
| Alamat akhir: /{base_path}/{versi}/{endpoint}  →  /api/external/v1/products
|
| File ini HANYA berisi kerangka versi. Endpoint bisnis tidak pernah ditulis
| di sini — masing-masing versi punya file rutenya sendiri di
| routes/external-api/{versi}.php.
|
| Awalan alamat diambil dari config('externalapi.base_path'), BUKAN dari
| apiPrefix di bootstrap/app.php (yang sengaja dikosongkan karena config belum
| termuat saat berkas itu dijalankan). Dengan begitu awalannya cukup ditulis
| di satu tempat.
|
| Menambah versi baru (v2, v3, ...):
|   1. tambahkan "v2" ke config('externalapi.versions')
|   2. buat routes/external-api/v2.php
| Versi lama tetap hidup berdampingan; tidak ada yang perlu dipindahkan.
|
| Seluruh rute di sini berada di luar session — tidak ada checkLogin, tidak
| ada CSRF. Satu-satunya gerbangnya adalah API Key.
|
| Urutan middleware disengaja: pencatatan dipasang lebih dulu (paling luar)
| supaya permintaan yang ditolak autentikasi pun tetap masuk log.
|
*/

Route::prefix(ExternalApiPath::basePath())
    ->middleware([LogExternalApiRequest::class])
    ->group(function () {
        foreach ((array) config('externalapi.versions', []) as $version) {
            $file = base_path('routes/external-api/'.$version.'.php');

            if (! file_exists($file)) {
                continue;
            }

            Route::prefix($version)
                ->name('externalApi.'.$version.'.')
                ->middleware([AuthenticateExternalApi::class])
                ->group($file);
        }

        // Alamat yang tidak dikenal tetap dijawab dengan bentuk error baku,
        // bukan halaman HTML 404 milik aplikasi admin. Dibatasi ke dalam
        // awalan External API, jadi rute web di luar sini tidak ikut terpengaruh.
        Route::fallback(function () {
            return ApiResponse::error(
                ApiResponse::ERROR_NOT_FOUND,
                'Endpoint tidak ditemukan.',
                404,
            );
        });
    });
