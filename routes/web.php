<?php

use App\Http\Controllers\AutocompleteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\checkLogin;
use Illuminate\Support\Facades\Route;

Route::get('/login', [GeneralController::class, 'login'])->name('login');
Route::post('/loginUser', [UserController::class, 'loginUser'])->name('loginUser');
Route::middleware(checkLogin::class)->group(function () {
    Route::get('/', function () {
        return view('Backoffice.Dashboard.Dashboard-Admin');
    })->name('index');
    Route::get('/admin', function () {
        return view('Backoffice.Dashboard.Dashboard-Admin');
    })->name('dashboard-admin');

    Route::post('/autocompleteCity', [AutocompleteController::class, 'autocompleteCity'])->name('autocompleteCity');
    Route::post('/autocompleteProv', [AutocompleteController::class, 'autocompleteProv'])->name('autocompleteProv');
    Route::post('/autocompleteArea', [AutocompleteController::class, 'autocompleteArea'])->name('autocompleteArea');
    Route::post('/autocompleteDistrict', [AutocompleteController::class, 'autocompleteDistrict'])->name('autocompleteDistrict');
    Route::post('/autocompleteCountry', [AutocompleteController::class, 'autocompleteCountry'])->name('autocompleteCountry');
    Route::post('/autocompleteSubdistrict', [AutocompleteController::class, 'autocompleteSubdistrict'])->name('autocompleteSubdistrict');

    Route::middleware('check.access:Kategori|view')->group(function () {
        Route::post('/autocompleteCategory', [AutocompleteController::class, 'autocompleteCategory'])->name('autocompleteCategory');
    });
    Route::middleware('check.access:Satuan|view')->group(function () {
        Route::post('/autocompleteUnit', [AutocompleteController::class, 'autocompleteUnit'])->name('autocompleteUnit');
    });
    Route::middleware('check.access:Variasi|view')->group(function () {
        Route::post('/autocompleteVariant', [AutocompleteController::class, 'autocompleteVariant'])->name('autocompleteVariant');
    });
    Route::middleware('check.access:Resep Bahan Mentah|view')->group(function () {
        Route::post('/autocompleteBom', [AutocompleteController::class, 'autocompleteBom'])->name('autocompleteBom');
    });
    Route::middleware('check.access.any:Daftar Produk,Stok Produk,Pengiriman,Produksi,Produk Bermasalah,view')->group(function () {
        Route::post('/autocompleteProduct', [AutocompleteController::class, 'autocompleteProduct'])->name('autocompleteProduct');
        Route::post('/autocompleteProductVariant', [AutocompleteController::class, 'autocompleteProductVariant'])->name('autocompleteProductVariant');
        Route::post('/autocompleteProductVariants', [AutocompleteController::class, 'autocompleteProductVariants'])->name('autocompleteProductVariant');
    });
    Route::middleware('check.access.any:Daftar Bahan Mentah,Stok Bahan Mentah,Pembelian,Produksi,Resep Bahan Mentah,Pengelolaan Bahan Mentah,Produk Bermasalah,view')->group(function () {
        Route::post('/autocompleteSupplies', [AutocompleteController::class, 'autocompleteSupplies'])->name('autocompleteSupplies');
        Route::post('/autocompleteSuppliesVariant', [AutocompleteController::class, 'autocompleteSuppliesVariant'])->name('autocompleteSuppliesVariant');
        Route::post('/autocompleteSuppliesVariantOnly', [AutocompleteController::class, 'autocompleteSuppliesVariantOnly'])->name('autocompleteSuppliesVariantOnly');
    });
    Route::middleware('check.access.any:Armada,Pengiriman,view')->group(function () {
        Route::post('/autocompleteCustomer', [AutocompleteController::class, 'autocompleteCustomer'])->name('autocompleteCustomer');
    });
    Route::middleware('check.access.any:Pemasok,Pembelian,view')->group(function () {
        Route::post('/autocompleteSupplier', [AutocompleteController::class, 'autocompleteSupplier'])->name('autocompleteSupplier');
    });
    Route::middleware('check.access.any:Pengguna,Pengiriman,Pembelian,Produksi,Kas Operasional,view')->group(function () {
        Route::post('/autocompleteStaff', [AutocompleteController::class, 'autocompleteStaff'])->name('autocompleteStaff');
    });
    Route::middleware('check.access:Pengiriman|view')->group(function () {
        Route::post('/autocompleteStaffSales', [AutocompleteController::class, 'autocompleteStaffSales'])->name('autocompleteStaffSales');
    });
    Route::middleware('check.access.any:Kategori Kas,Kas Operasional,Kas,view')->group(function () {
        Route::post('/autocompleteCashCategory', [AutocompleteController::class, 'autocompleteCashCategory'])->name('autocompleteCashCategory');
    });
    Route::middleware('check.access:Peran & Perizinan|view')->group(function () {
        Route::post('/autocompleteRole', [AutocompleteController::class, 'autocompleteRole'])->name('autocompleteRole');
    });
    Route::middleware('check.access.any:Bank Account,Hutang,view')->group(function () {
        Route::post('/autocompleteRekening', [AutocompleteController::class, 'autocompleteRekening'])->name('autocompleteRekening');
    });
    Route::middleware('check.access:Pembelian|view')->group(function () {
        Route::post('/autocompletePO', [AutocompleteController::class, 'autocompletePO'])->name('autocompletePO');
    });
    Route::middleware('check.access:Pengiriman|view')->group(function () {
        Route::post('/autocompleteSO', [AutocompleteController::class, 'autocompleteSO'])->name('autocompleteSO');
    });

    Route::middleware('check.access:Kategori|view')->group(function () {
        Route::get('/category', [ProductController::class, 'Category'])->name('category');
        Route::get('/getCategory', [ProductController::class, 'getCategory'])->name('getCategory');
    });
    Route::middleware('check.access:Kategori|create')->group(function () {
        Route::post('/insertCategory', [ProductController::class, 'insertCategory'])->name('insertCategory');
    });
    Route::middleware('check.access:Kategori|edit')->group(function () {
        Route::post('/updateCategory', [ProductController::class, 'updateCategory'])->name('updateCategory');
    });
    Route::middleware('check.access:Kategori|delete')->group(function () {
        Route::post('/deleteCategory', [ProductController::class, 'deleteCategory'])->name('deleteCategory');
    });

    Route::middleware('check.access:Satuan|view')->group(function () {
        Route::get('/unit', [ProductController::class, 'Unit'])->name('unit');
        Route::get('/getUnit', [ProductController::class, 'getUnit'])->name('getUnit');
    });
    Route::middleware('check.access:Satuan|create')->group(function () {
        Route::post('/insertUnit', [ProductController::class, 'insertUnit'])->name('insertUnit');
    });
    Route::middleware('check.access:Satuan|edit')->group(function () {
        Route::post('/updateUnit', [ProductController::class, 'updateUnit'])->name('updateUnit');
    });
    Route::middleware('check.access:Satuan|delete')->group(function () {
        Route::post('/deleteUnit', [ProductController::class, 'deleteUnit'])->name('deleteUnit');
    });

    Route::middleware('check.access:Variasi|view')->group(function () {
        Route::get('/variant', [ProductController::class, 'Variant'])->name('variant');
        Route::get('/getVariant', [ProductController::class, 'getVariant'])->name('getVariant');
    });
    Route::middleware('check.access:Variasi|create')->group(function () {
        Route::post('/insertVariant', [ProductController::class, 'insertVariant'])->name('insertVariant');
    });
    Route::middleware('check.access:Variasi|edit')->group(function () {
        Route::post('/updateVariant', [ProductController::class, 'updateVariant'])->name('updateVariant');
    });
    Route::middleware('check.access:Variasi|delete')->group(function () {
        Route::post('/deleteVariant', [ProductController::class, 'deleteVariant'])->name('deleteVariant');
    });

    Route::middleware('check.access:Untung & Rugi|view')->group(function () {
        Route::get('/profitLoss', [ReportController::class, 'ProfitLoss'])->name('profitLoss');
        Route::get('/getProfit', [ReportController::class, 'getProfit'])->name('getProfit');
        Route::get('/getLoss', [ReportController::class, 'getLoss'])->name('getLoss');
    });

    Route::middleware('check.access:Stok Opname Produk|view')->group(function () {
        Route::get('/stockOpname', [StockController::class, 'StockOpname'])->name('stockOpname');
        Route::get('/getStockOpname', [StockController::class, 'getStockOpname'])->name('getStockOpname');
        Route::get('/generateStockOpname/{id}', [StockController::class, 'generateStockOpname'])->name('generateStockOpname');
        Route::get('/detailStockOpname/{id}', [StockController::class, 'DetailStockOpname'])->name('detailStockOpname');
        Route::get('/getDetailStockOpname', [StockController::class, 'getDetailStockOpname'])->name('getDetailStockOpname');
    });
    Route::middleware('check.access:Stok Opname Produk|create')->group(function () {
        Route::post('/insertStockOpname', [StockController::class, 'insertStockOpname'])->name('insertStockOpname');
        Route::post('/insertDetailStockOpname', [StockController::class, 'insertDetailStockOpname'])->name('insertDetailStockOpname');
    });
    Route::middleware('check.access:Stok Opname Produk|edit')->group(function () {
        Route::post('/updateStockOpname', [StockController::class, 'updateStockOpname'])->name('updateStockOpname');
        Route::post('/updateDetailStockOpname', [StockController::class, 'updateDetailStockOpname'])->name('updateDetailStockOpname');
    });
    Route::middleware('check.access:Stok Opname Produk|delete')->group(function () {
        Route::post('/deleteStockOpname', [StockController::class, 'deleteStockOpname'])->name('deleteStockOpname');
        Route::post('/deleteDetailStockOpname', [StockController::class, 'deleteDetailStockOpname'])->name('deleteDetailStockOpname');
    });
    Route::middleware('check.access:Stok Opname Produk|others')->group(function () {
        Route::post('/accStockOpname', [StockController::class, 'accStockOpname'])->name('accStockOpname');
        Route::post('/tolakStockOpname', [StockController::class, 'tolakStockOpname'])->name('tolakStockOpname');
    });

    Route::middleware('check.access:Stok Opname Bahan Mentah|view')->group(function () {
        Route::get('/stockOpnameBahan', [StockController::class, 'StockOpnameBahan'])->name('stockOpnameBahan');
        Route::get('/getStockOpnameBahan', [StockController::class, 'getStockOpnameBahan'])->name('getStockOpnameBahan');
        Route::get('/generateStockOpnameBahan/{id}', [StockController::class, 'generateStockOpnameBahan'])->name('generateStockOpnameBahan');
        Route::get('/detailStockOpnameBahan/{id}', [StockController::class, 'DetailStockOpnameBahan'])->name('detailStockOpnameBahan');
        Route::get('/getDetailStockOpnameBahan', [StockController::class, 'getDetailStockOpnameBahan'])->name('getDetailStockOpnameBahan');
    });
    Route::middleware('check.access:Stok Opname Bahan Mentah|create')->group(function () {
        Route::post('/insertStockOpnameBahan', [StockController::class, 'insertStockOpnameBahan'])->name('insertStockOpnameBahan');
        Route::post('/insertDetailStockOpnameBahan', [StockController::class, 'insertDetailStockOpnameBahan'])->name('insertDetailStockOpnameBahan');
    });
    Route::middleware('check.access:Stok Opname Bahan Mentah|edit')->group(function () {
        Route::post('/updateStockOpnameBahan', [StockController::class, 'updateStockOpnameBahan'])->name('updateStockOpnameBahan');
        Route::post('/updateDetailStockOpnameBahan', [StockController::class, 'updateDetailStockOpnameBahan'])->name('updateDetailStockOpnameBahan');
    });
    Route::middleware('check.access:Stok Opname Bahan Mentah|delete')->group(function () {
        Route::post('/deleteStockOpnameBahan', [StockController::class, 'deleteStockOpnameBahan'])->name('deleteStockOpnameBahan');
        Route::post('/deleteDetailStockOpnameBahan', [StockController::class, 'deleteDetailStockOpnameBahan'])->name('deleteDetailStockOpnameBahan');
    });
    Route::middleware('check.access:Stok Opname Bahan Mentah|others')->group(function () {
        Route::post('/accStockOpnameBahan', [StockController::class, 'accStockOpnameBahan'])->name('accStockOpnameBahan');
        Route::post('/tolakStockOpnameBahan', [StockController::class, 'tolakStockOpnameBahan'])->name('tolakStockOpnameBahan');
    });

    Route::middleware('check.access:Peringatan Stok Produk|view')->group(function () {
        Route::get('/stockAlert', [StockController::class, 'StockAlert'])->name('stockAlert');
        Route::get('/getStockAlert', [StockController::class, 'getStockAlert'])->name('getStockAlert');
    });
    Route::middleware('check.access:Peringatan Stok Produk|create')->group(function () {
        Route::post('/insertStockAlert', [StockController::class, 'insertStockAlert'])->name('insertStockAlert');
    });
    Route::middleware('check.access:Peringatan Stok Produk|edit')->group(function () {
        Route::post('/updateStockAlert', [StockController::class, 'updateStockAlert'])->name('updateStockAlert');
    });
    Route::middleware('check.access:Peringatan Stok Produk|delete')->group(function () {
        Route::post('/deleteStockAlert', [StockController::class, 'deleteStockAlert'])->name('deleteStockAlert');
    });

    Route::middleware('check.access:Peringatan Stok Bahan Mentah|view')->group(function () {
        Route::get('/stockAlertSupplies', [StockController::class, 'StockAlertSupplies'])->name('stockAlertSupplies');
        Route::get('/getStockAlertSupplies', [StockController::class, 'getStockAlertSupplies'])->name('getStockAlertSupplies');
    });
    Route::middleware('check.access:Peringatan Stok Bahan Mentah|create')->group(function () {
        Route::post('/insertStockAlertSupplies', [StockController::class, 'insertStockAlertSupplies'])->name('insertStockAlertSupplies');
    });
    Route::middleware('check.access:Peringatan Stok Bahan Mentah|edit')->group(function () {
        Route::post('/updateStockAlertSupplies', [StockController::class, 'updateStockAlertSupplies'])->name('updateStockAlertSupplies');
    });
    Route::middleware('check.access:Peringatan Stok Bahan Mentah|delete')->group(function () {
        Route::post('/deleteStockAlertSupplies', [StockController::class, 'deleteStockAlertSupplies'])->name('deleteStockAlertSupplies');
    });

    Route::middleware('check.access:Produk Bermasalah|view')->group(function () {
        Route::get('/productIssue', [StockController::class, 'ProductIssue'])->name('productIssue');
        Route::get('/getProductIssue', [StockController::class, 'getProductIssue'])->name('getProductIssue');
    });
    Route::middleware('check.access:Produk Bermasalah|create')->group(function () {
        Route::post('/insertProductIssues', [StockController::class, 'insertProductIssue'])->name('insertProductIssue');
    });
    Route::middleware('check.access:Produk Bermasalah|edit')->group(function () {
        Route::post('/updateProductIssues', [StockController::class, 'updateProductIssue'])->name('updateProductIssue');
    });
    Route::middleware('check.access:Produk Bermasalah|delete')->group(function () {
        Route::post('/deleteProductIssues', [StockController::class, 'deleteProductIssue'])->name('deleteProductIssue');
    });
    Route::middleware('check.access:Produk Bermasalah|others')->group(function () {
        Route::post('/accProductIssues', [StockController::class, 'accProductIssues'])->name('accProductIssues');
        Route::post('/declineProductIssues', [StockController::class, 'declineProductIssues'])->name('declineProductIssues');
    });

    Route::middleware('check.access:Barang Masuk Keluar|view')->group(function () {
        Route::get('/inwardOutward', [ReportController::class, 'InwardOutward'])->name('inwardOutward');
        Route::get('/getInwardOutward', [ReportController::class, 'getInwardOutward'])->name('getInwardOutward');
    });

    Route::middleware('check.access:Hutang|view')->group(function () {
        Route::get('/payReceive', [ReportController::class, 'PayReceive'])->name('payReceive');
        Route::get('/checkHutang', [ReportController::class, 'checkHutang'])->name('checkHutang');
        Route::get('/generateHutang', [ReportController::class, 'generateHutang'])->name('generateHutang');
    });

    Route::middleware('check.access:Pengiriman|view')->group(function () {
        Route::get('/salesOrder', [CustomerController::class, 'SalesOrder'])->name('salesOrder');
        Route::get('/getSalesOrder', [CustomerController::class, 'getSalesOrder'])->name('getSalesOrder');
        Route::get('/salesOrderDetail/{id}', [CustomerController::class, 'SalesOrderDetail'])->name('salesOrderDetail');
        Route::get('/getSoDelivery', [CustomerController::class, 'getSoDelivery'])->name('getSoDelivery');
        Route::get('/getSoInvoice', [CustomerController::class, 'getSoInvoice'])->name('getSoInvoice');
    });
    Route::middleware('check.access:Pengiriman|create')->group(function () {
        Route::post('/insertSalesOrder', [CustomerController::class, 'insertSalesOrder'])->name('insertSalesOrder');
        Route::post('/insertSoDelivery', [CustomerController::class, 'insertSoDelivery'])->name('insertSoDelivery');
        Route::post('/insertInvoiceSO', [CustomerController::class, 'insertInvoiceSO'])->name('insertInvoiceSO');
    });
    Route::middleware('check.access:Pengiriman|edit')->group(function () {
        Route::post('/updateSalesOrder', [CustomerController::class, 'updateSalesOrder'])->name('updateSalesOrder');
        Route::post('/updateSalesOrderDetail', [CustomerController::class, 'updateSalesOrderDetail'])->name('updateSalesOrderDetail');
        Route::post('/updateSoDelivery', [CustomerController::class, 'updateSoDelivery'])->name('updateSoDelivery');
        Route::post('/updateInvoiceSO', [CustomerController::class, 'updateInvoiceSO'])->name('updateInvoiceSO');
    });
    Route::middleware('check.access:Pengiriman|delete')->group(function () {
        Route::post('/deleteSalesOrder', [CustomerController::class, 'deleteSalesOrder'])->name('deleteSalesOrder');
        Route::post('/deleteSoDelivery', [CustomerController::class, 'deleteSoDelivery'])->name('deleteSoDelivery');
        Route::post('/deleteInvoiceSO', [CustomerController::class, 'deleteInvoiceSO'])->name('deleteInvoiceSO');
    });
    Route::middleware('check.access:Pengiriman|others')->group(function () {
        Route::post('/accSO', [CustomerController::class, 'accSO'])->name('accSO');
        Route::post('/declineSO', [CustomerController::class, 'declineSO'])->name('declineSO');
        Route::post('/accSoDelivery', [CustomerController::class, 'accSoDelivery'])->name('accSoDelivery');
        Route::post('/declineSoDelivery', [CustomerController::class, 'declineSoDelivery'])->name('declineSoDelivery');
        Route::post('/acceptInvoiceSO', [CustomerController::class, 'acceptInvoiceSO'])->name('acceptInvoiceSO');
        Route::post('/declineInvoiceSO', [CustomerController::class, 'declineInvoiceSO'])->name('declineInvoiceSO');
    });

    Route::middleware('check.access:Pembelian|view')->group(function () {
        Route::get('/purchaseOrder', [SupplierController::class, 'PurchaseOrder'])->name('purchaseOrder');
        Route::get('/searchSupplies', [SupplierController::class, 'searchSupplies'])->name('searchSupplies');
        Route::get('/purchaseOrderDetail/{id}', [SupplierController::class, 'PurchaseOrderDetail'])->name('purchaseOrderDetail');
        Route::get('/getPurchaseOrder', [SupplierController::class, 'getPurchaseOrder'])->name('getPurchaseOrder');
        Route::get('/getPurchaseOrderDetail', [SupplierController::class, 'getPurchaseOrderDetail'])->name('getPurchaseOrderDetail');
        Route::get('/getPoDelivery', [SupplierController::class, 'getPoDelivery'])->name('getPoDelivery');
        Route::get('/getPoInvoice', [SupplierController::class, 'getPoInvoice'])->name('getPoInvoice');
        Route::get('/getPoReceipt', [SupplierController::class, 'getPoReceipt'])->name('getPoReceipt');
        Route::get('/getReturnSupplies', [SupplierController::class, 'getReturnSupplies'])->name('getReturnSupplies');
    });
    Route::middleware('check.access:Pembelian|create')->group(function () {
        Route::post('/insertPurchaseOrder', [SupplierController::class, 'insertPurchaseOrder'])->name('insertPurchaseOrder');
        Route::post('/insertPoDelivery', [SupplierController::class, 'insertPoDelivery'])->name('insertPoDelivery');
        Route::post('/insertInvoicePO', [SupplierController::class, 'insertInvoicePO'])->name('insertInvoicePO');
        Route::post('/insertReturnSupplies', [SupplierController::class, 'insertReturnSupplies'])->name('insertReturnSupplies');
    });
    Route::middleware('check.access:Pembelian|edit')->group(function () {
        Route::post('/updatePurchaseOrderDetail', [SupplierController::class, 'updatePurchaseOrderDetail'])->name('updatePurchaseOrderDetail');
        Route::post('/updatePoDelivery', [SupplierController::class, 'updatePoDelivery'])->name('updatePoDelivery');
        Route::post('/updateInvoicePO', [SupplierController::class, 'updateInvoicePO'])->name('updateInvoicePO');
        Route::post('/updateReturnSupplies', [SupplierController::class, 'updateReturnSupplies'])->name('updateReturnSupplies');
    });
    Route::middleware('check.access:Pembelian|delete')->group(function () {
        Route::post('/deletePurchaseOrder', [SupplierController::class, 'deletePurchaseOrder'])->name('deletePurchaseOrder');
        Route::post('/deletePoDelivery', [SupplierController::class, 'deletePoDelivery'])->name('deletePoDelivery');
        Route::post('/deleteInvoicePO', [SupplierController::class, 'deleteInvoicePO'])->name('deleteInvoicePO');
        Route::post('/deleteReturnSupplies', [SupplierController::class, 'deleteReturnSupplies'])->name('deleteReturnSupplies');
    });
    Route::middleware('check.access:Pembelian|others')->group(function () {
        Route::post('/accPO', [SupplierController::class, 'accPO'])->name('accPO');
        Route::post('/tolakPO', [SupplierController::class, 'tolakPO'])->name('tolakPO');
        Route::post('/acceptInvoicePO', [SupplierController::class, 'acceptInvoicePO'])->name('acceptInvoicePO');
        Route::post('/declineInvoicePO', [SupplierController::class, 'declineInvoicePO'])->name('declineInvoicePO');
        Route::post('/accPoDelivery', [SupplierController::class, 'accPoDelivery'])->name('accPoDelivery');
        Route::post('/declinePoDelivery', [SupplierController::class, 'declinePoDelivery'])->name('declinePoDelivery');
        Route::post('/pelunasanPurchaseOrder', [SupplierController::class, 'pelunasanPurchaseOrder'])->name('pelunasanPurchaseOrder');
    });

    Route::middleware('check.access.any:Pembelian,Hutang,view')->group(function () {
        Route::get('/purchaseOrderDetailHutang/{id}', [SupplierController::class, 'PurchaseOrderDetailHutang'])->name('purchaseOrderDetailHutang');
    });

    Route::middleware('check.access:Daftar Produk|view')->group(function () {
        Route::get('/product', [ProductController::class, 'Product'])->name('product');
        Route::get('/getProduct', [ProductController::class, 'getProduct'])->name('getProduct');
        Route::get('/getProductVariant', [ProductController::class, 'getProductVariant'])->name('getProductVariant');
    });
    Route::middleware('check.access:Daftar Produk|create')->group(function () {
        Route::get('/insertProduct', [ProductController::class, 'viewInsertProduct'])->name('viewInsertProduct');
        Route::post('/insertProduct', [ProductController::class, 'insertProduct'])->name('insertProduct');
    });
    Route::middleware('check.access:Daftar Produk|edit')->group(function () {
        Route::get('/updateProduct/{id}', [ProductController::class, 'ViewUpdateProduct'])->name('ViewUpdateProduct');
        Route::post('/updateProduct', [ProductController::class, 'updateProduct'])->name('updateProduct');
    });
    Route::middleware('check.access:Daftar Produk|delete')->group(function () {
        Route::post('/deleteProduct', [ProductController::class, 'deleteProduct'])->name('deleteProduct');
    });

    Route::middleware('check.access:Stok Produk|view')->group(function () {
        Route::get('/stockProduct', [StockController::class, 'Stock'])->name('stockProduct');
        Route::get('/getStock', [StockController::class, 'getStock'])->name('getStock');
    });

    Route::middleware('check.access:Daftar Bahan Mentah|view')->group(function () {
        Route::get('/supplies', [ProductController::class, 'Supplies'])->name('supplies');
        Route::get('/getSupplies', [ProductController::class, 'getSupplies'])->name('getSupplies');
        Route::get('/getSuppliesVariant', [ProductController::class, 'getSuppliesVariant'])->name('getSuppliesVariant');
    });
    Route::middleware('check.access:Daftar Bahan Mentah|create')->group(function () {
        Route::post('/insertSupplies', [ProductController::class, 'insertSupplies'])->name('insertSupplies');
        Route::post('/insertSuppliesUnit', [ProductController::class, 'insertSuppliesUnit'])->name('insertSuppliesUnit');
        Route::post('/insertSuppliesRelation', [ProductController::class, 'insertSuppliesRelation'])->name('insertSuppliesRelation');
    });
    Route::middleware('check.access:Daftar Bahan Mentah|edit')->group(function () {
        Route::post('/updateSupplies', [ProductController::class, 'updateSupplies'])->name('updateSupplies');
    });
    Route::middleware('check.access:Daftar Bahan Mentah|delete')->group(function () {
        Route::post('/deleteSupplies', [ProductController::class, 'deleteSupplies'])->name('deleteSupplies');
    });

    Route::middleware('check.access:Stok Bahan Mentah|view')->group(function () {
        Route::get('/stockSupplies', [StockController::class, 'StockSupplies'])->name('stockSupplies');
        Route::get('/getStockSupplies', [StockController::class, 'getStockSupplies'])->name('getStockSupplies');
    });

    Route::middleware('check.access:Peran & Perizinan|view')->group(function () {
        Route::get('/role', [UserController::class, 'role'])->name('role');
        Route::get('/getRole', [UserController::class, 'getRole'])->name('getRole');
        Route::get('/permission/{id}', [UserController::class, 'permission'])->name('permission');
        Route::get('/getPermission', [UserController::class, 'getPermission'])->name('getPermission');
        Route::get('/getLog', [GeneralController::class, 'getLog'])->name('getLog');
    });
    Route::middleware('check.access:Peran & Perizinan|create')->group(function () {
        Route::post('/insertRole', [UserController::class, 'insertRole'])->name('insertRole');
        Route::post('/insertPermission', [UserController::class, 'insertPermission'])->name('insertPermission');
    });
    Route::middleware('check.access:Peran & Perizinan|edit')->group(function () {
        Route::post('/updateRole', [UserController::class, 'updateRole'])->name('updateRole');
        Route::post('/updatePermission', [UserController::class, 'updatePermission'])->name('updatePermission');
    });
    Route::middleware('check.access:Peran & Perizinan|delete')->group(function () {
        Route::post('/deleteRole', [UserController::class, 'deleteRole'])->name('deleteRole');
        Route::post('/deletePermission', [UserController::class, 'deletePermission'])->name('deletePermission');
    });

    Route::middleware('check.access:Pengguna|view')->group(function () {
        Route::get('/staff', [UserController::class, 'staff'])->name('staff');
        Route::get('/staffDetail/{id}', [UserController::class, 'staffDetail'])->name('staffDetail');
        Route::get('/getStaff', [UserController::class, 'getStaff'])->name('getStaff');
    });
    Route::middleware('check.access:Pengguna|create')->group(function () {
        Route::get('/insertStaff', [UserController::class, 'viewInsertStaff'])->name('viewInsertStaff');
        Route::post('/insertStaff', [UserController::class, 'insertStaff'])->name('insertStaff');
    });
    Route::middleware('check.access:Pengguna|edit')->group(function () {
        Route::get('/updateStaff/{id}', [UserController::class, 'ViewUpdateStaff'])->name('ViewUpdateStaff');
        Route::post('/updateStaff', [UserController::class, 'updateStaff'])->name('updateStaff');
    });
    Route::middleware('check.access:Pengguna|delete')->group(function () {
        Route::post('/deleteStaff', [UserController::class, 'deleteStaff'])->name('deleteStaff');
    });

    Route::middleware('check.access:Armada|view')->group(function () {
        Route::get('/customer', [CustomerController::class, 'customer'])->name('customer');
        Route::get('/customerDetail/{id}', [CustomerController::class, 'customerDetail'])->name('customerDetail');
        Route::get('/getCustomer', [CustomerController::class, 'getCustomer'])->name('getCustomer');
    });
    Route::middleware('check.access:Armada|create')->group(function () {
        Route::get('/insertCustomer', [CustomerController::class, 'viewInsertCustomer'])->name('viewInsertCustomer');
        Route::post('/insertCustomer', [CustomerController::class, 'insertCustomer'])->name('insertCustomer');
    });
    Route::middleware('check.access:Armada|edit')->group(function () {
        Route::get('/updateCustomer/{id}', [CustomerController::class, 'ViewUpdateCustomer'])->name('ViewUpdateCustomer');
        Route::post('/updateCustomer', [CustomerController::class, 'updateCustomer'])->name('updateCustomer');
    });
    Route::middleware('check.access:Armada|delete')->group(function () {
        Route::post('/deleteCustomer', [CustomerController::class, 'deleteCustomer'])->name('deleteCustomer');
    });

    Route::get('/getDashboardExecutiveWidgets', [ReportController::class, 'getDashboardExecutiveWidgets'])->name('getDashboardExecutiveWidgets');

    Route::middleware('check.access:Pengelolaan Bahan Mentah|view')->group(function () {
        Route::get('/reportBahanBaku', [ReportController::class, 'reportBahanBaku'])->name('reportBahanBaku');
        Route::get('/getDashboardPemakaianBahan', [ReportController::class, 'getDashboardPemakaianBahan'])->name('getDashboardPemakaianBahan');
        Route::get('/getReportPemakaianBahan', [ReportController::class, 'getReportPemakaianBahan'])->name('getReportPemakaianBahan');
        Route::get('/generateReportPemakaianBahanPdf', [ReportController::class, 'generateReportPemakaianBahanPdf'])->name('generateReportPemakaianBahanPdf');
    });

    Route::middleware('check.access.any:Pengelolaan Bahan Mentah,Pembelian,view')->group(function () {
        Route::get('/getDashboardProcurementEstimate', [ReportController::class, 'getDashboardProcurementEstimate'])->name('getDashboardProcurementEstimate');
    });

    Route::middleware('check.access.any:Stok Opname Produk,Stok Opname Bahan Mentah,view')->group(function () {
        Route::get('/reportSelisihOpname', [ReportController::class, 'reportSelisihOpname'])->name('reportSelisihOpname');
        Route::get('/getReportSelisihOpname', [ReportController::class, 'getReportSelisihOpname'])->name('getReportSelisihOpname');
        Route::get('/generateReportSelisihOpnamePdf', [ReportController::class, 'generateReportSelisihOpnamePdf'])->name('generateReportSelisihOpnamePdf');
    });

    Route::middleware('check.access:Laporan Produksi|view')->group(function () {
        Route::get('/reportProduksi', [ReportController::class, 'reportProduksi'])->name('reportProduksi');
        Route::get('/getReportProduksi', [ReportController::class, 'getReportProduksi'])->name('getReportProduksi');
        Route::get('/generateReportProduksiPdf', [ReportController::class, 'generateReportProduksiPdf'])->name('generateReportProduksiPdf');
        Route::get('/reportEfisiensiProduksi', [ReportController::class, 'reportEfisiensiProduksi'])->name('reportEfisiensiProduksi');
        Route::get('/getReportEfisiensiProduksi', [ReportController::class, 'getReportEfisiensiProduksi'])->name('getReportEfisiensiProduksi');
        Route::get('/generateReportEfisiensiProduksiPdf', [ReportController::class, 'generateReportEfisiensiProduksiPdf'])->name('generateReportEfisiensiProduksiPdf');
    });

    Route::middleware('check.access:Retur Produk|view')->group(function () {
        Route::get('/ProductReturn', [ReportController::class, 'ProductReturn'])->name('ProductReturn');
        Route::get('/getReportReturn', [ReportController::class, 'getReportReturn'])->name('getReportReturn');
        Route::get('/generateReportReturnPdf', [ReportController::class, 'generateReportReturnPdf'])->name('generateReportReturnPdf');
        Route::get('/reportReturProdukArmada', [ReportController::class, 'reportReturProdukArmada'])->name('reportReturProdukArmada');
        Route::get('/getReportReturProdukArmada', [ReportController::class, 'getReportReturProdukArmada'])->name('getReportReturProdukArmada');
        Route::get('/generateReportReturProdukArmadaPdf', [ReportController::class, 'generateReportReturProdukArmadaPdf'])->name('generateReportReturProdukArmadaPdf');
    });

    Route::middleware('check.access:Laporan Stock Aging|view')->group(function () {
        Route::get('/reportStockAging', [ReportController::class, 'reportStockAging'])->name('reportStockAging');
        Route::get('/getReportStockAging', [ReportController::class, 'getReportStockAging'])->name('getReportStockAging');
        Route::get('/generateReportStockAgingPdf', [ReportController::class, 'generateReportStockAgingPdf'])->name('generateReportStockAgingPdf');
    });

    Route::middleware('check.access.any:Kas,Kas Operasional,view')->group(function () {
        Route::get('/cash', [ReportController::class, 'Cash'])->name('cash');
        Route::get('/getCash', [ReportController::class, 'getCash'])->name('getCash');
        Route::get('/getCashAdmin', [ReportController::class, 'getCashAdmin'])->name('getCashAdmin');
        Route::get('/getCashGudang', [ReportController::class, 'getCashGudang'])->name('getCashGudang');
        Route::get('/getCashArmada', [ReportController::class, 'getCashArmada'])->name('getCashArmada');
        Route::get('/getCashSales', [ReportController::class, 'getCashSales'])->name('getCashSales');
        Route::get('/pettyCash', [ReportController::class, 'PettyCash'])->name('pettyCash');
        Route::get('/getPettyCash', [ReportController::class, 'getPettyCash'])->name('getPettyCash');
        Route::get('/operationalCash', [ReportController::class, 'OperationalCash'])->name('operationalCash');
    });
    Route::middleware('check.access.any:Kas,Kas Operasional,create')->group(function () {
        Route::post('/insertCash', [ReportController::class, 'insertCash'])->name('insertCash');
        Route::post('/insertCashAdmin', [ReportController::class, 'insertCashAdmin'])->name('insertCashAdmin');
        Route::post('/insertCashGudang', [ReportController::class, 'insertCashGudang'])->name('insertCashGudang');
        Route::post('/insertCashArmada', [ReportController::class, 'insertCashArmada'])->name('insertCashArmada');
        Route::post('/insertCashSales', [ReportController::class, 'insertCashSales'])->name('insertCashSales');
        Route::post('/insertPettyCash', [ReportController::class, 'insertPettyCash'])->name('insertPettyCash');
    });
    Route::middleware('check.access.any:Kas,Kas Operasional,edit')->group(function () {
        Route::post('/updateCashAdmin', [ReportController::class, 'updateCashAdmin'])->name('updateCashAdmin');
        Route::post('/updateCashGudang', [ReportController::class, 'updateCashGudang'])->name('updateCashGudang');
        Route::post('/updateCashArmada', [ReportController::class, 'updateCashArmada'])->name('updateCashArmada');
        Route::post('/updateCashSales', [ReportController::class, 'updateCashSales'])->name('updateCashSales');
    });
    Route::middleware('check.access.any:Kas,Kas Operasional,delete')->group(function () {
        Route::post('/deleteCashAdmin', [ReportController::class, 'deleteCashAdmin'])->name('deleteCashAdmin');
        Route::post('/deleteCashGudang', [ReportController::class, 'deleteCashGudang'])->name('deleteCashGudang');
        Route::post('/deleteCashArmada', [ReportController::class, 'deleteCashArmada'])->name('deleteCashArmada');
        Route::post('/deleteCashSales', [ReportController::class, 'deleteCashSales'])->name('deleteCashSales');
    });
    Route::middleware('check.access.any:Kas,Kas Operasional,others')->group(function () {
        Route::post('/acceptCashAdmin', [ReportController::class, 'acceptCashAdmin'])->name('acceptCashAdmin');
        Route::post('/declineCashAdmin', [ReportController::class, 'declineCashAdmin'])->name('declineCashAdmin');
        Route::post('/acceptCashGudang', [ReportController::class, 'acceptCashGudang'])->name('acceptCashGudang');
        Route::post('/declineCashGudang', [ReportController::class, 'declineCashGudang'])->name('declineCashGudang');
        Route::post('/acceptCashArmada', [ReportController::class, 'acceptCashArmada'])->name('acceptCashArmada');
        Route::post('/declineCashArmada', [ReportController::class, 'declineCashArmada'])->name('declineCashArmada');
        Route::post('/acceptCashSales', [ReportController::class, 'acceptCashSales'])->name('acceptCashSales');
        Route::post('/declineCashSales', [ReportController::class, 'declineCashSales'])->name('declineCashSales');
    });

    Route::middleware('check.access:Pemasok|view')->group(function () {
        Route::get('/getSupplier', [SupplierController::class, 'getSupplier'])->name('getSupplier');
        Route::get('/supplier', [SupplierController::class, 'supplier'])->name('supplier');
        Route::get('/supplierDetail/{id}', [SupplierController::class, 'supplierDetail'])->name('supplierDetail');
    });
    Route::middleware('check.access:Pemasok|create')->group(function () {
        Route::get('/insertSupplier', [SupplierController::class, 'ViewInsertSupplier'])->name('ViewInsertSupplier');
        Route::post('/insertSupplier', [SupplierController::class, 'insertSupplier'])->name('insertSupplier');
    });
    Route::middleware('check.access:Pemasok|edit')->group(function () {
        Route::get('/updateSupplier/{id}', [SupplierController::class, 'ViewUpdateSupplier'])->name('ViewUpdateSupplier');
        Route::post('/updateSupplier', [SupplierController::class, 'updateSupplier'])->name('updateSupplier');
    });
    Route::middleware('check.access:Pemasok|delete')->group(function () {
        Route::post('/deleteSupplier', [SupplierController::class, 'deleteSupplier'])->name('deleteSupplier');
    });

    Route::middleware('check.access:Resep Bahan Mentah|view')->group(function () {
        Route::get('/bom', [ProductionController::class, 'bom'])->name('bom');
        Route::get('/getBom', [ProductionController::class, 'getBom'])->name('getBom');
    });
    Route::middleware('check.access:Resep Bahan Mentah|create')->group(function () {
        Route::post('/insertBom', [ProductionController::class, 'insertBom'])->name('insertBom');
    });
    Route::middleware('check.access:Resep Bahan Mentah|edit')->group(function () {
        Route::post('/updateBom', [ProductionController::class, 'updateBom'])->name('updateBom');
    });
    Route::middleware('check.access:Resep Bahan Mentah|delete')->group(function () {
        Route::post('/deleteBom', [ProductionController::class, 'deleteBom'])->name('deleteBom');
    });

    Route::middleware('check.access:Produksi|view')->group(function () {
        Route::get('/production', [ProductionController::class, 'production'])->name('production');
        Route::get('/getProduction', [ProductionController::class, 'getProduction'])->name('getProduction');
        Route::get('/getPemakaian', [ProductionController::class, 'getPemakaian'])->name('getPemakaian');
        Route::get('/getFotoProduksi', [ProductionController::class, 'getFotoProduksi'])->name('getFotoProduksi');
    });
    Route::middleware('check.access:Produksi|create')->group(function () {
        Route::post('/insertProduction', [ProductionController::class, 'insertProduction'])->name('insertProduction');
    });
    Route::middleware('check.access:Produksi|edit')->group(function () {
        Route::post('/updateProduction', [ProductionController::class, 'updateProduction'])->name('updateProduction');
        Route::post('/uploadPhotoProduksi', [ProductionController::class, 'uploadPhotoProduksi'])->name('uploadPhotoProduksi');
    });
    Route::middleware('check.access:Produksi|delete')->group(function () {
        Route::post('/deleteProduction', [ProductionController::class, 'deleteProduction'])->name('deleteProduction');
    });
    Route::middleware('check.access:Produksi|others')->group(function () {
        Route::post('/accProduction', [ProductionController::class, 'accProduction'])->name('accProduction');
        Route::post('/declineProduction', [ProductionController::class, 'declineProduction'])->name('declineProduction');
        Route::post('/accDeleteProduction', [ProductionController::class, 'accDeleteProduction'])->name('accDeleteProduction');
        Route::post('/tolakDeleteProduction', [ProductionController::class, 'tolakDeleteProduction'])->name('tolakDeleteProduction');
    });

    Route::middleware('check.access:Pengaturan|view')->group(function () {
        Route::get('/testing', [GeneralController::class, 'testing'])->name('testing');
        Route::get('/settings', [SettingController::class, 'Settings'])->name('settings');
        Route::post('/getSetting', [SettingController::class, 'getSetting'])->name('getSetting');
    });
    Route::middleware('check.access:Pengaturan|create')->group(function () {
        Route::post('/insertSetting', [SettingController::class, 'insertSetting'])->name('insertSetting');
    });
    Route::middleware('check.access:Pengaturan|edit')->group(function () {
        Route::post('/updateSetting', [SettingController::class, 'updateSetting'])->name('updateSetting');
    });

    Route::middleware('check.access:Profil|view')->group(function () {
        Route::get('/profiles', [SettingController::class, 'Profiles'])->name('profiles');
    });

    Route::middleware('check.access.any:Kategori,Satuan,Variasi,view')->group(function () {
        Route::get('/area', [GeneralController::class, 'Area'])->name('area');
        Route::get('/getArea', [GeneralController::class, 'getArea'])->name('getArea');
    });
    Route::middleware('check.access.any:Kategori,Satuan,Variasi,create')->group(function () {
        Route::post('/insertArea', [GeneralController::class, 'insertArea'])->name('insertArea');
    });
    Route::middleware('check.access.any:Kategori,Satuan,Variasi,edit')->group(function () {
        Route::post('/updateArea', [GeneralController::class, 'updateArea'])->name('updateArea');
    });
    Route::middleware('check.access.any:Kategori,Satuan,Variasi,delete')->group(function () {
        Route::post('/deleteArea', [GeneralController::class, 'deleteArea'])->name('deleteArea');
    });

    Route::middleware('check.access:Kategori Kas|view')->group(function () {
        Route::get('/cashCategory', [ReportController::class, 'CashCategory'])->name('cashCategory');
        Route::get('/getCashCategory', [ReportController::class, 'getCashCategory'])->name('getCashCategory');
    });
    Route::middleware('check.access:Kategori Kas|create')->group(function () {
        Route::post('/insertCashCategory', [ReportController::class, 'insertCashCategory'])->name('insertCashCategory');
    });
    Route::middleware('check.access:Kategori Kas|edit')->group(function () {
        Route::post('/updateCashCategory', [ReportController::class, 'updateCashCategory'])->name('updateCashCategory');
    });
    Route::middleware('check.access:Kategori Kas|delete')->group(function () {
        Route::post('/deleteCashCategory', [ReportController::class, 'deleteCashCategory'])->name('deleteCashCategory');
    });

    Route::middleware('check.access:Bank Account|view')->group(function () {
        Route::get('/bank', [UserController::class, 'bank'])->name('bank');
        Route::get('/getBank', [UserController::class, 'getBank'])->name('getBank');
    });
    Route::middleware('check.access:Bank Account|create')->group(function () {
        Route::post('/insertBank', [UserController::class, 'insertBank'])->name('insertBank');
    });
    Route::middleware('check.access:Bank Account|edit')->group(function () {
        Route::post('/updateBank', [UserController::class, 'updateBank'])->name('updateBank');
    });
    Route::middleware('check.access:Bank Account|delete')->group(function () {
        Route::post('/deleteBank', [UserController::class, 'deleteBank'])->name('deleteBank');
    });

    Route::middleware('check.access:Tanda Terima PO|view')->group(function () {
        Route::get('/tt', [SupplierController::class, 'tt'])->name('tt');
        Route::get('/getTt', [SupplierController::class, 'getTt'])->name('getTt');
        Route::get('/generateTandaTerima/{id}/{kode}', [SupplierController::class, 'generateTandaTerima'])->name('generateTandaTerima');
        Route::get('/generateTandaTerimaInvoice', [SupplierController::class, 'generateTandaTerimaInvoice'])->name('generateTandaTerimaInvoice');
        Route::get('/viewTandaTerima/{id}', [SupplierController::class, 'viewTandaTerima'])->name('generateTandaTerima');
    });
    Route::middleware('check.access:Tanda Terima PO|create')->group(function () {
        Route::post('/insertTt', [SupplierController::class, 'insertTt'])->name('insertTt');
    });
    Route::middleware('check.access:Tanda Terima PO|edit')->group(function () {
        Route::post('/updateTt', [SupplierController::class, 'updateTt'])->name('updateTt');
    });
    Route::middleware('check.access:Tanda Terima PO|delete')->group(function () {
        Route::post('/deleteTt', [SupplierController::class, 'deleteTt'])->name('deleteTt');
    });
    Route::middleware('check.access:Tanda Terima PO|others')->group(function () {
        Route::post('/accTt', [SupplierController::class, 'accTt'])->name('accTt');
        Route::post('/declineTt', [SupplierController::class, 'declineTt'])->name('declineTt');
    });
});
