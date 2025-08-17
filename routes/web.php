<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('Backoffice.Dashboard.Dashboard-Admin');
});

Route::get('/category',[ProductController::class,"Category"])->name('category');
Route::get('/getCategory', [ProductController::class, "getCategory"])->name('getCategory');
Route::post('/insertCategory', [ProductController::class, "insertCategory"])->name('insertCategory');
Route::post('/updateCategory', [ProductController::class, "updateCategory"])->name('updateCategory');
Route::post('/deleteCategory', [ProductController::class, "deleteCategory"])->name('deleteCategory');

Route::get('/unit',[ProductController::class,"Unit"])->name('unit');
Route::get('/getUnit', [ProductController::class, "getUnit"])->name('getUnit');
Route::post('/insertUnit', [ProductController::class, "insertUnit"])->name('insertUnit');
Route::post('/updateUnit', [ProductController::class, "updateUnit"])->name('updateUnit');
Route::post('/deleteUnit', [ProductController::class, "deleteUnit"])->name('deleteUnit');

Route::get('/variant',[ProductController::class,"Variant"])->name('variant');
Route::get('/getVariant', [ProductController::class, "getVariant"])->name('getVariant');
Route::post('/insertVariant', [ProductController::class, "insertVariant"])->name('insertVariant');
Route::post('/updateVariant', [ProductController::class, "updateVariant"])->name('updateVariant');
Route::post('/deleteVariant', [ProductController::class, "deleteVariant"])->name('deleteVariant');

Route::get('/profitLoss',[ReportController::class,"ProfitLoss"])->name('profitLoss');
Route::get('/getProfit',[ReportController::class,"getProfit"])->name('getProfit');
Route::get('/getLoss',[ReportController::class,"getLoss"])->name('getLoss');

Route::get('/stockOpname',[StockController::class,"StockOpname"])->name('stockOpname');
Route::get('/getStockOpname', [StockController::class, "getStockOpname"])->name('getStockOpname');
Route::post('/insertStockOpname', [StockController::class, "insertStockOpname"])->name('insertStockOpname');
Route::post('/updateStockOpname', [StockController::class, "updateStockOpname"])->name('updateStockOpname');
Route::post('/deleteStockOpname', [StockController::class, "deleteStockOpname"])->name('deleteStockOpname');

Route::get('/stockAlert',[StockController::class,"StockAlert"])->name('stockAlert');
Route::get('/getStockAlert', [StockController::class, "getStockAlert"])->name('getStockAlert');
Route::post('/insertStockAlert', [StockController::class, "insertStockAlert"])->name('insertStockAlert');
Route::post('/updateStockAlert', [StockController::class, "updateStockAlert"])->name('updateStockAlert');
Route::post('/deleteStockAlert', [StockController::class, "deleteStockAlert"])->name('deleteStockAlert');

Route::get('/productIssue',[StockController::class,"ProductIssue"])->name('productIssue');
Route::get('/getProductIssue', [StockController::class, "getProductIssue"])->name('getProductIssue');
Route::post('/insertProductIssue', [StockController::class, "insertProductIssue"])->name('insertProductIssue');
Route::post('/updateProductIssue', [StockController::class, "updateProductIssue"])->name('updateProductIssue');
Route::post('/deleteProductIssue', [StockController::class, "deleteProductIssue"])->name('deleteProductIssue');

Route::get('/detailStockOpname/{id}', [StockController::class, "DetailStockOpname"])->name('detailStockOpname');
Route::get('/getDetailStockOpname', [StockController::class, "getDetailStockOpname"])->name('getDetailStockOpname');
Route::post('/insertDetailStockOpname', [StockController::class, "insertDetailStockOpname"])->name('insertDetailStockOpname');
Route::post('/updateDetailStockOpname', [StockController::class, "updateDetailStockOpname"])->name('updateDetailStockOpname');
Route::post('/deleteDetailStockOpname', [StockController::class, "deleteDetailStockOpname"])->name('deleteDetailStockOpname');

Route::get('/inwardOutward',[ReportController::class,"InwardOutward"])->name('inwardOutward');
Route::get('/getInwardOutward',[ReportController::class,"getInwardOutward"])->name('getInwardOutward');

Route::get('/payReceive',[ReportController::class,"PayReceive"])->name('payReceive');
Route::get('/getSalesOrder', [CustomerController::class, "getSalesOrder"])->name('getSalesOrder');
Route::get('/getPurchaseOrder', [SupplierController::class, "getPurchaseOrder"])->name('getPurchaseOrder');

Route::get('/cash',[ReportController::class,"Cash"])->name('cash');
Route::get('/getCash',[ReportController::class,"getCash"])->name('getCash');

Route::get('/pettyCash',[ReportController::class,"PettyCash"])->name('pettyCash');
Route::get('/getPettyCash',[ReportController::class,"getPettyCash"])->name('getPettyCash');

Route::get('/manageStock',[StockController::class,"ManageStock"])->name('manageStock');
Route::get('/getManageStock',[StockController::class,"getManageStock"])->name('getManageStock');

Route::get('/product',[ProductController::class,"Product"])->name('product');
Route::get('/getProduct',[ProductController::class,"getProduct"])->name('getProduct');

Route::get('/stock',[StockController::class,"Stock"])->name('stock');
Route::get('/getStock',[StockController::class,"getStock"])->name('getStock');

Route::get('/supplies',[ProductController::class,"Supplies"])->name('supplies');
Route::get('/getSupplies',[ProductController::class,"getSupplies"])->name('getSupplies');
