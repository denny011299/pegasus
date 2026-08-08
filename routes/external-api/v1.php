<?php

use App\Http\Controllers\ExternalApi\V1\CashPaymentController;
use App\Http\Controllers\ExternalApi\V1\MasterDataController;
use App\Http\Controllers\ExternalApi\V1\MasterSalesController;
use App\Http\Controllers\ExternalApi\V1\MasterWarehouseController;
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
    Route::get('/warehouses', [MasterWarehouseController::class, 'index'])->name('warehouses');
    Route::post('/warehouses', [MasterWarehouseController::class, 'store'])->name('warehouses.store');
    Route::put('/warehouses/{gudang_id}', [MasterWarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{gudang_id}', [MasterWarehouseController::class, 'destroy'])->name('warehouses.destroy');
    Route::get('/warehouse_types', [MasterDataController::class, 'warehouseTypes'])->name('warehouseTypes');

    Route::get('/sales', [MasterSalesController::class, 'index'])->name('sales');
    Route::post('/sales', [MasterSalesController::class, 'store'])->name('sales.store');
    Route::put('/sales/{staff_id}', [MasterSalesController::class, 'update'])->name('sales.update');
    Route::delete('/sales/{staff_id}', [MasterSalesController::class, 'destroy'])->name('sales.destroy');
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
