<?php

use App\Http\Controllers\AutocompleteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('Backoffice.Dashboard.Dashboard-Admin');
});
Route::post('/autocompleteCity', [AutocompleteController::class, "autocompleteCity"])->name('autocompleteCity');
Route::post('/autocompleteProv', [AutocompleteController::class, "autocompleteProv"])->name('autocompleteProv');
Route::post('/autocompleteCountry', [AutocompleteController::class, "autocompleteCountry"])->name('autocompleteCountry');

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

Route::get('/salesOrder',[CustomerController::class,"SalesOrder"])->name('salesOrder');
Route::get('/getSalesOrder', [CustomerController::class, "getSalesOrder"])->name('getSalesOrder');
Route::get('/salesOrderDetail/{id}', [CustomerController::class, "SalesOrderDetail"])->name('salesOrderDetail');
Route::get('/getSoInvoice', [CustomerController::class, "getSoInvoice"])->name('getSoInvoice');
Route::get('/getSoDelivery', [CustomerController::class, "getSoDelivery"])->name('getSoDelivery');

Route::get('/purchaseOrder',[SupplierController::class,"PurchaseOrder"])->name('purchaseOrder');
Route::get('/purchaseOrderDetail/{id}',[SupplierController::class,"PurchaseOrderDetail"])->name('purchaseOrderDetail');
Route::get('/getPurchaseOrder', [SupplierController::class, "getPurchaseOrder"])->name('getPurchaseOrder');
Route::get('/getPoDelivery', [SupplierController::class, "getPoDelivery"])->name('getPoDelivery');
Route::get('/getPoInvoice', [SupplierController::class, "getPoInvoice"])->name('getPoInvoice');
Route::get('/getPoReceipt', [SupplierController::class, "getPoReceipt"])->name('getPoReceipt');

Route::get('/manageStock',[StockController::class,"ManageStock"])->name('manageStock');
Route::get('/getManageStock',[StockController::class,"getManageStock"])->name('getManageStock');

Route::get('/product',[ProductController::class,"Product"])->name('product');
Route::get('/getProduct',[ProductController::class,"getProduct"])->name('getProduct');

Route::get('/stock',[StockController::class,"Stock"])->name('stock');
Route::get('/getStock',[StockController::class,"getStock"])->name('getStock');

Route::get('/supplies',[ProductController::class,"Supplies"])->name('supplies');
Route::get('/getSupplies',[ProductController::class,"getSupplies"])->name('getSupplies');

Route::get('/user',[UserController::class,"user"])->name('user');
Route::get('/getUser', [UserController::class, "getUser"])->name('getUser');
Route::post('/insertUser', [UserController::class, "insertUser"])->name('insertUser');
Route::post('/updateUser', [UserController::class, "updateUser"])->name('updateUser');
Route::post('/deleteUser', [UserController::class, "deleteUser"])->name('deleteUser');

Route::get('/role',[UserController::class,"role"])->name('role');
Route::get('/getRole', [UserController::class, "getRole"])->name('getRole');
Route::post('/insertRole', [UserController::class, "insertRole"])->name('insertRole');
Route::post('/updateRole', [UserController::class, "updateRole"])->name('updateRole');
Route::post('/deleteRole', [UserController::class, "deleteRole"])->name('deleteRole');

Route::get('/permission',[UserController::class,"permission"])->name('permission');
Route::get('/getPermission', [UserController::class, "getPermission"])->name('getPermission');
Route::post('/insertPermission', [UserController::class, "insertPermission"])->name('insertPermission');
Route::post('/updatePermission', [UserController::class, "updatePermission"])->name('updatePermission');
Route::post('/deletePermission', [UserController::class, "deletePermission"])->name('deletePermission');

//customer
Route::get('/customers',[CustomerController::class,"customer"])->name('customers');
Route::get('/customerDetail/{id}',[CustomerController::class,"customerDetail"])->name('customerDetail');
Route::get('/getCustomers', [CustomerController::class, "getCustomers"])->name('getCustomers');
Route::get('/insertCustomer', [CustomerController::class, "viewInsertCustomer"])->name('viewInsertCustomer');
Route::post('/insertCustomer', [CustomerController::class, "insertCustomer"])->name('insertCustomer');
Route::post('/updateCustomer', [CustomerController::class, "updateCustomer"])->name('updateCustomer');
Route::post('/deleteCustomer', [CustomerController::class, "deleteCustomer"])->name('deleteCustomer');

//reporting
Route::get('/reportBahanBaku', [ReportController::class, "reportBahanBaku"])->name('reportBahanBaku');
Route::get('/reportProduksi', [ReportController::class, "reportProduksi"])->name('reportProduksi');
Route::get('/SuppliesReturn', [ReportController::class, "SuppliesReturn"])->name('SuppliesReturn');
Route::get('/cash',[ReportController::class,"Cash"])->name('cash');
Route::get('/getCash',[ReportController::class,"getCash"])->name('getCash');
Route::get('/pettyCash',[ReportController::class,"PettyCash"])->name('pettyCash');
Route::get('/getPettyCash',[ReportController::class,"getPettyCash"])->name('getPettyCash');
