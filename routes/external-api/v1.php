<?php

use App\Http\Controllers\ExternalApi\V1\CashPaymentController;
use App\Http\Controllers\ExternalApi\V1\MasterDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| External API v1
|--------------------------------------------------------------------------
|
| Base URL: /api/external/v1  (awalannya dari config('externalapi.base_path'))
|
| Seluruh rute di file ini sudah otomatis:
|   - dilindungi API Key (middleware AuthenticateExternalApi)
|   - tercatat di Log API Eksternal (middleware LogExternalApiRequest)
|   - diberi awalan nama rute "externalApi.v1."
| Jadi jangan menambahkan middleware autentikasi atau pencatatan lagi di sini.
|
| Saat menambah endpoint baru, WAJIB sekalian mendaftarkan dokumentasinya di
| config('externalapi.docs'). Langkah lengkapnya ada di
| .claude/skills/external-api-endpoint/SKILL.md.
|
*/

/*
 * Data master (API-001).
 *
 * Hanya baca dan tanpa parameter: tabelnya kecil dan spesifikasi API-001
 * mengeluarkan penyaringan, pencarian, serta paginasi dari lingkupnya.
 */
Route::prefix('master')->name('master.')->group(function () {
    // API-001
    Route::get('/units', [MasterDataController::class, 'units'])->name('units');
    Route::get('/cash_categories', [MasterDataController::class, 'cashCategories'])->name('cashCategories');

    // API-002
    Route::get('/warehouses', [MasterDataController::class, 'warehouses'])->name('warehouses');
    Route::get('/warehouse_types', [MasterDataController::class, 'warehouseTypes'])->name('warehouseTypes');
    Route::get('/sales', [MasterDataController::class, 'sales'])->name('sales');
});

/*
 * Pembayaran kas (API-005).
 *
 * POST bersifat idempoten lewat ref_payment_id: mengirim ulang permintaan yang
 * sama tidak membuat transaksi kas kedua.
 */
Route::prefix('payments')->name('payments.')->group(function () {
    Route::post('/cash', [CashPaymentController::class, 'store'])->name('cashStore');
    Route::get('/cash/{ref_payment_id}', [CashPaymentController::class, 'show'])->name('cashShow');
});
