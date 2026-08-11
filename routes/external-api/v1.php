<?php

use App\Http\Controllers\ExternalApi\V1\CashPaymentController;
use App\Http\Controllers\ExternalApi\V1\MasterArmadaController;
use App\Http\Controllers\ExternalApi\V1\MasterDataController;
use App\Http\Controllers\ExternalApi\V1\MasterProductController;
use App\Http\Controllers\ExternalApi\V1\MasterSalesController;
use App\Http\Controllers\ExternalApi\V1\MasterUnitController;
use App\Http\Controllers\ExternalApi\V1\MasterWarehouseController;
use App\Http\Controllers\ExternalApi\V1\ShipmentController;
use App\Http\Controllers\ExternalApi\V1\StockController;
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

/*
 * Data Armada.
 *
 * Modul tersendiri, BUKAN bagian dari prefix master/ di atas — "armada"
 * bukan tabel tersendiri, baris tabel customers yang sama dengan pelanggan
 * biasa (lihat catatan kelas MasterArmadaController), tapi konsepnya beda
 * dari data master yang statis (satuan, gudang, dst.) sehingga sengaja
 * dilayani lewat rute dan halaman dokumentasi sendiri ("Data Armada").
 *
 * {customer_code} pada PUT/DELETE adalah id universal yang ditentukan
 * pemanggil sendiri saat POST. TIDAK ADA endpoint connect di sini —
 * customer_code selalu sudah terisi otomatis untuk setiap pelanggan, jadi
 * tidak ada baris "belum tersambung" yang perlu dihubungkan belakangan
 * seperti pada sales/satuan.
 */
Route::prefix('armada')->name('armada.')->group(function () {
    Route::get('/', [MasterArmadaController::class, 'index'])->name('index');
    Route::post('/', [MasterArmadaController::class, 'store'])->name('store');
    Route::put('/{customer_code}', [MasterArmadaController::class, 'update'])->name('update');
    Route::delete('/{customer_code}', [MasterArmadaController::class, 'destroy'])->name('destroy');
});

/*
 * Data Produk.
 *
 * Modul tersendiri, bukan bagian dari prefix master/ di atas — sama seperti
 * Data Armada, punya rute dan halaman dokumentasi sendiri ("Data Produk").
 * Beda dengan Armada: produk PUNYA endpoint connect, karena
 * products.ref_product_id — sama seperti units.ref_unit_id — nullable dan
 * sering kosong untuk produk yang dibuat lewat halaman admin, bukan selalu
 * terisi seperti customers.customer_code. {ref_product_id} pada PUT/DELETE
 * adalah rujukan itu; PATCH /produk/connect terpisah, menghubungkan banyak
 * produk sekaligus (body.connections), tiap butir memakai id internal
 * Pegasus. Lihat catatan kelas MasterProductController.
 */
Route::prefix('produk')->name('produk.')->group(function () {
    Route::get('/', [MasterProductController::class, 'index'])->name('index');
    Route::post('/', [MasterProductController::class, 'store'])->name('store');
    Route::put('/{ref_product_id}', [MasterProductController::class, 'update'])->name('update');
    Route::delete('/{ref_product_id}', [MasterProductController::class, 'destroy'])->name('destroy');
    Route::patch('/connect', [MasterProductController::class, 'connect'])->name('connect');
});

/*
 * Stok.
 *
 * Beda dengan modul lain: hanya baca, tidak ada create/update/delete di
 * sini, jadi tidak ada konsep "dikelola API ini" atau endpoint connect.
 * check() memakai ulang App\Support\ProductUnitStock (logika stok yang sama
 * dipakai Sales Order) untuk menghitung stok tersedia setara satu satuan per
 * SKU, termasuk bongkar satuan lebih besar dalam satu chain
 * product_relations. Lihat catatan kelas StockController.
 */
Route::prefix('stock')->name('stock.')->group(function () {
    Route::post('/check', [StockController::class, 'check'])->name('check');
});

/*
 * Shipment.
 *
 * Modul baru mengikuti "private docs/Open API/API_Integration_Specification_PMO_IPM_v1.md"
 * (API Contract v1): /shipments/scheduled, /shipments/shipped, GET /shipments/{ref_shipment_id}
 * (semua dibangun di sini) — /shipments/status, /shipments/cancel menyusul terpisah. Prefix rute
 * PLURAL ("shipments") sesuai dokumen itu, beda dengan nama modul/branch yang singular
 * ("Shipment") — dikonfirmasi pemilik produk.
 *
 * Tabelnya TETAP sales_orders/sales_order_details (menu admin "Pengiriman"), bukan tabel baru —
 * lihat catatan kelas ShipmentController. scheduled() memakai ulang cek stok yang SAMA PERSIS
 * dengan POST /stock/check lewat Concerns\ChecksStockAvailability. shipped() idempoten lewat
 * ref_shipment_id (beda dengan scheduled() yang menolak duplikat) dan memakai ulang
 * App\Support\SalesOrderApproval::confirm() — logika accSO() yang sama dipakai halaman admin
 * Pengiriman, diekstrak supaya bisa dipakai di sini juga. show() (GET) menemukan baris apa pun
 * dengan ref_shipment_id itu tanpa syarat status.
 */
Route::prefix('shipments')->name('shipments.')->group(function () {
    Route::post('/scheduled', [ShipmentController::class, 'scheduled'])->name('scheduled');
    Route::post('/shipped', [ShipmentController::class, 'shipped'])->name('shipped');
    Route::get('/{ref_shipment_id}', [ShipmentController::class, 'show'])->name('show');
});
