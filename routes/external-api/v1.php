<?php

use App\Http\Controllers\ExternalApi\V1\CashPaymentController;
use App\Http\Controllers\ExternalApi\V1\MasterArmadaController;
use App\Http\Controllers\ExternalApi\V1\MasterDataController;
use App\Http\Controllers\ExternalApi\V1\MasterSalesController;
use App\Http\Controllers\ExternalApi\V1\MasterUnitController;
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
    //
    // {ref_unit_id} pada PUT/DELETE units adalah id satuan yang sama pada
    // sistem PMO (units.ref_unit_id), bukan id internal Pegasus. PATCH
    // /units/connect terpisah dari situ: menghubungkan banyak satuan
    // sekaligus (body.connections), tiap butir memakai id internal Pegasus.
    // Lihat catatan kelas MasterUnitController — termasuk kenapa kolom yang
    // sama juga ditulis Pusat Sinkronisasi (SyncUnitStep), disengaja.
    Route::get('/units', [MasterUnitController::class, 'index'])->name('units');
    Route::post('/units', [MasterUnitController::class, 'store'])->name('units.store');
    Route::put('/units/{ref_unit_id}', [MasterUnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{ref_unit_id}', [MasterUnitController::class, 'destroy'])->name('units.destroy');
    Route::patch('/units/connect', [MasterUnitController::class, 'connect'])->name('units.connect');

    Route::get('/cash_categories', [MasterDataController::class, 'cashCategories'])->name('cashCategories');

    // API-002
    Route::get('/warehouses', [MasterWarehouseController::class, 'index'])->name('warehouses');
    Route::post('/warehouses', [MasterWarehouseController::class, 'store'])->name('warehouses.store');
    Route::put('/warehouses/{gudang_id}', [MasterWarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{gudang_id}', [MasterWarehouseController::class, 'destroy'])->name('warehouses.destroy');
    Route::get('/warehouse_types', [MasterDataController::class, 'warehouseTypes'])->name('warehouseTypes');

    // {staff_id} pada PUT/DELETE adalah external_ref_id (rujukan sistem
    // pemanggil). PATCH /sales/connect terpisah dari situ: menghubungkan
    // banyak staf sekaligus (body.connections), masing-masing butir memakai
    // id internal Pegasus, bukan external_ref_id. Lihat catatan kelas
    // MasterSalesController.
    Route::get('/sales', [MasterSalesController::class, 'index'])->name('sales');
    Route::post('/sales', [MasterSalesController::class, 'store'])->name('sales.store');
    Route::put('/sales/{staff_id}', [MasterSalesController::class, 'update'])->name('sales.update');
    Route::delete('/sales/{staff_id}', [MasterSalesController::class, 'destroy'])->name('sales.destroy');
    Route::patch('/sales/connect', [MasterSalesController::class, 'connect'])->name('sales.connect');

    // Data Armada — "armada" bukan tabel tersendiri, baris tabel customers
    // yang sama dengan pelanggan biasa (lihat catatan kelas
    // MasterArmadaController). {customer_code} pada PUT/DELETE adalah id
    // universal yang ditentukan pemanggil sendiri saat POST. TIDAK ADA
    // endpoint connect di sini — customer_code selalu sudah terisi otomatis
    // untuk setiap pelanggan, jadi tidak ada baris "belum tersambung" yang
    // perlu dihubungkan belakangan seperti pada sales/satuan.
    Route::get('/armada', [MasterArmadaController::class, 'index'])->name('armada');
    Route::post('/armada', [MasterArmadaController::class, 'store'])->name('armada.store');
    Route::put('/armada/{customer_code}', [MasterArmadaController::class, 'update'])->name('armada.update');
    Route::delete('/armada/{customer_code}', [MasterArmadaController::class, 'destroy'])->name('armada.destroy');
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
