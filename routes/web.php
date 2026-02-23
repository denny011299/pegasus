<?php

use App\Http\Controllers\AutocompleteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\checkLogin;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/login',[GeneralController::class,"login"])->name('login');
Route::post('/loginUser', [UserController::class, "loginUser"])->name('loginUser');
Route::middleware(checkLogin::class)->group(function () {
    Route::get('/', function () {
        return view('Backoffice.Dashboard.Dashboard-Admin');
    });
    Route::get('/admin', function () {
        return view('Backoffice.Dashboard.Dashboard-Admin');
    });

    Route::post('/autocompleteCity', [AutocompleteController::class, "autocompleteCity"])->name('autocompleteCity');
    Route::post('/autocompleteProv', [AutocompleteController::class, "autocompleteProv"])->name('autocompleteProv');
    Route::post('/autocompleteArea', [AutocompleteController::class, "autocompleteArea"])->name('autocompleteArea');
    Route::post('/autocompleteDistrict', [AutocompleteController::class, "autocompleteDistrict"])->name('autocompleteDistrict');
    Route::post('/autocompleteCountry', [AutocompleteController::class, "autocompleteCountry"])->name('autocompleteCountry');
    Route::post('/autocompleteCategory', [AutocompleteController::class, "autocompleteCategory"])->name('autocompleteCategory');
    Route::post('/autocompleteUnit', [AutocompleteController::class, "autocompleteUnit"])->name('autocompleteUnit');
    Route::post('/autocompleteVariant', [AutocompleteController::class, "autocompleteVariant"])->name('autocompleteVariant');
    Route::post('/autocompleteBom', [AutocompleteController::class, "autocompleteBom"])->name('autocompleteBom');
    Route::post('/autocompleteProduct', [AutocompleteController::class, "autocompleteProduct"])->name('autocompleteProduct');
    Route::post('/autocompleteSupplies', [AutocompleteController::class, "autocompleteSupplies"])->name('autocompleteSupplies');
    Route::post('/autocompleteSuppliesVariant', [AutocompleteController::class, "autocompleteSuppliesVariant"])->name('autocompleteSuppliesVariant');
    Route::post('/autocompleteSuppliesVariantOnly', [AutocompleteController::class, "autocompleteSuppliesVariantOnly"])->name('autocompleteSuppliesVariantOnly');
    Route::post('/autocompleteProductVariant', [AutocompleteController::class, "autocompleteProductVariant"])->name('autocompleteProductVariant');
    Route::post('/autocompleteProductVariants', [AutocompleteController::class, "autocompleteProductVariants"])->name('autocompleteProductVariant');
    Route::post('/autocompleteCustomer', [AutocompleteController::class, "autocompleteCustomer"])->name('autocompleteCustomer');
    Route::post('/autocompleteSupplier', [AutocompleteController::class, "autocompleteSupplier"])->name('autocompleteSupplier');
    Route::post('/autocompleteStaff', [AutocompleteController::class, "autocompleteStaff"])->name('autocompleteStaff');
    Route::post('/autocompleteStaffSales', [AutocompleteController::class, "autocompleteStaffSales"])->name('autocompleteStaffSales');
    Route::post('/autocompleteSubdistrict', [AutocompleteController::class, "autocompleteSubdistrict"])->name('autocompleteSubdistrict');
    Route::post('/autocompleteCashCategory', [AutocompleteController::class, "autocompleteCashCategory"])->name('autocompleteCashCategory');
    Route::post('/autocompleteRole', [AutocompleteController::class, "autocompleteRole"])->name('autocompleteRole');
    Route::post('/autocompleteRekening', [AutocompleteController::class, "autocompleteRekening"])->name('autocompleteRekening');
    Route::post('/autocompletePO', [AutocompleteController::class, "autocompletePO"])->name('autocompletePO');
    Route::post('/autocompleteSO', [AutocompleteController::class, "autocompleteSO"])->name('autocompleteSO');

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
    Route::post('/accStockOpname',[StockController::class,"accStockOpname"])->name('accStockOpname');
    Route::post('/tolakStockOpname',[StockController::class,"tolakStockOpname"])->name('tolakStockOpname');
    Route::get('/generateStockOpname/{id}',[StockController::class,"generateStockOpname"])->name('generateStockOpname');

    Route::get('/detailStockOpname/{id}', [StockController::class, "DetailStockOpname"])->name('detailStockOpname');
    Route::get('/getDetailStockOpname', [StockController::class, "getDetailStockOpname"])->name('getDetailStockOpname');
    Route::post('/insertDetailStockOpname', [StockController::class, "insertDetailStockOpname"])->name('insertDetailStockOpname');
    Route::post('/updateDetailStockOpname', [StockController::class, "updateDetailStockOpname"])->name('updateDetailStockOpname');
    Route::post('/deleteDetailStockOpname', [StockController::class, "deleteDetailStockOpname"])->name('deleteDetailStockOpname');

    Route::get('/stockOpnameBahan',[StockController::class,"StockOpnameBahan"])->name('stockOpnameBahan');
    Route::get('/getStockOpnameBahan', [StockController::class, "getStockOpnameBahan"])->name('getStockOpnameBahan');
    Route::post('/insertStockOpnameBahan', [StockController::class, "insertStockOpnameBahan"])->name('insertStockOpnameBahan');
    Route::post('/updateStockOpnameBahan', [StockController::class, "updateStockOpnameBahan"])->name('updateStockOpnameBahan');
    Route::post('/deleteStockOpnameBahan', [StockController::class, "deleteStockOpnameBahan"])->name('deleteStockOpnameBahan');
    Route::post('/accStockOpnameBahan',[StockController::class,"accStockOpnameBahan"])->name('accStockOpnameBahan');
    Route::post('/tolakStockOpnameBahan',[StockController::class,"tolakStockOpnameBahan"])->name('tolakStockOpnameBahan');
    Route::get('/generateStockOpnameBahan/{id}',[StockController::class,"generateStockOpnameBahan"])->name('generateStockOpnameBahan');
    
    Route::get('/detailStockOpnameBahan/{id}', [StockController::class, "DetailStockOpnameBahan"])->name('detailStockOpnameBahan');
    Route::get('/getDetailStockOpnameBahan', [StockController::class, "getDetailStockOpnameBahan"])->name('getDetailStockOpnameBahan');
    Route::post('/insertDetailStockOpnameBahan', [StockController::class, "insertDetailStockOpnameBahan"])->name('insertDetailStockOpnameBahan');
    Route::post('/updateDetailStockOpnameBahan', [StockController::class, "updateDetailStockOpnameBahan"])->name('updateDetailStockOpnameBahan');
    Route::post('/deleteDetailStockOpnameBahan', [StockController::class, "deleteDetailStockOpnameBahan"])->name('deleteDetailStockOpnameBahan');

    Route::get('/stockAlert',[StockController::class,"StockAlert"])->name('stockAlert');
    Route::get('/getStockAlert', [StockController::class, "getStockAlert"])->name('getStockAlert');
    Route::post('/insertStockAlert', [StockController::class, "insertStockAlert"])->name('insertStockAlert');
    Route::post('/updateStockAlert', [StockController::class, "updateStockAlert"])->name('updateStockAlert');
    Route::post('/deleteStockAlert', [StockController::class, "deleteStockAlert"])->name('deleteStockAlert');
    
    Route::get('/stockAlertSupplies',[StockController::class,"StockAlertSupplies"])->name('stockAlertSupplies');
    Route::get('/getStockAlertSupplies', [StockController::class, "getStockAlertSupplies"])->name('getStockAlertSupplies');
    Route::post('/insertStockAlertSupplies', [StockController::class, "insertStockAlertSupplies"])->name('insertStockAlertSupplies');
    Route::post('/updateStockAlertSupplies', [StockController::class, "updateStockAlertSupplies"])->name('updateStockAlertSupplies');
    Route::post('/deleteStockAlertSupplies', [StockController::class, "deleteStockAlertSupplies"])->name('deleteStockAlertSupplies');

    Route::get('/productIssue',[StockController::class,"ProductIssue"])->name('productIssue');
    Route::get('/getProductIssue', [StockController::class, "getProductIssue"])->name('getProductIssue');
    Route::post('/insertProductIssues', [StockController::class, "insertProductIssue"])->name('insertProductIssue');
    Route::post('/updateProductIssues', [StockController::class, "updateProductIssue"])->name('updateProductIssue');
    Route::post('/deleteProductIssues', [StockController::class, "deleteProductIssue"])->name('deleteProductIssue');

    Route::get('/inwardOutward',[ReportController::class,"InwardOutward"])->name('inwardOutward');
    Route::get('/getInwardOutward',[ReportController::class,"getInwardOutward"])->name('getInwardOutward');

    Route::get('/payReceive',[ReportController::class,"PayReceive"])->name('payReceive');
    Route::get('/checkHutang', [ReportController::class, "checkHutang"])->name('checkHutang');
    Route::get('/generateHutang', [ReportController::class, "generateHutang"])->name('generateHutang');

    Route::get('/salesOrder',[CustomerController::class,"SalesOrder"])->name('salesOrder');
    Route::get('/getSalesOrder', [CustomerController::class, "getSalesOrder"])->name('getSalesOrder');
    Route::get('/salesOrderDetail/{id}', [CustomerController::class, "SalesOrderDetail"])->name('salesOrderDetail');
    Route::post('/insertSalesOrder', [CustomerController::class, "insertSalesOrder"])->name('insertSalesOrder');
    Route::post('/updateSalesOrder', [CustomerController::class, "updateSalesOrder"])->name('updateSalesOrder');
    Route::post('/deleteSalesOrder', [CustomerController::class, "deleteSalesOrder"])->name('deleteSalesOrder');
    Route::post('/updateSalesOrderDetail', [CustomerController::class, "updateSalesOrderDetail"])->name('updateSalesOrderDetail');
    Route::get('/getSoDelivery', [CustomerController::class, "getSoDelivery"])->name('getSoDelivery');
    Route::post('/insertSoDelivery', [CustomerController::class, "insertSoDelivery"])->name('insertSoDelivery');
    Route::post('/updateSoDelivery', [CustomerController::class, "updateSoDelivery"])->name('updateSoDelivery');
    Route::post('/deleteSoDelivery', [CustomerController::class, "deleteSoDelivery"])->name('deleteSoDelivery');
    Route::get('/getSoInvoice', [CustomerController::class, "getSoInvoice"])->name('getSoInvoice');
    Route::post('/insertInvoiceSO', [CustomerController::class, "insertInvoiceSO"])->name('insertInvoiceSO');
    Route::post('/updateInvoiceSO', [CustomerController::class, "updateInvoiceSO"])->name('updateInvoiceSO');
    Route::post('/deleteInvoiceSO', [CustomerController::class, "deleteInvoiceSO"])->name('deleteInvoiceSO');
    Route::post('/accSoDelivery', [CustomerController::class, "accSoDelivery"])->name('accSoDelivery');
    Route::post('/declineSoDelivery', [CustomerController::class, "declineSoDelivery"])->name('declineSoDelivery');
    Route::post('/acceptInvoiceSO', [CustomerController::class, "acceptInvoiceSO"])->name('acceptInvoiceSO');
    Route::post('/declineInvoiceSO', [CustomerController::class, "declineInvoiceSO"])->name('declineInvoiceSO');

    Route::get('/purchaseOrder',[SupplierController::class,"PurchaseOrder"])->name('purchaseOrder');
    Route::post('/accPO',[SupplierController::class,"accPO"])->name('accPO');
    Route::post('/tolakPO',[SupplierController::class,"tolakPO"])->name('tolakPO');
    Route::get('/searchSupplies',[SupplierController::class,"searchSupplies"])->name('searchSupplies');
    Route::get('/purchaseOrderDetail/{id}',[SupplierController::class,"PurchaseOrderDetail"])->name('purchaseOrderDetail');
    Route::get('/purchaseOrderDetailHutang/{id}',[SupplierController::class,"PurchaseOrderDetailHutang"])->name('purchaseOrderDetailHutang');
    Route::get('/getPurchaseOrder', [SupplierController::class, "getPurchaseOrder"])->name('getPurchaseOrder');
    Route::post('/insertPurchaseOrder', [SupplierController::class, "insertPurchaseOrder"])->name('insertPurchaseOrder');
    Route::post('/deletePurchaseOrder', [SupplierController::class, "deletePurchaseOrder"])->name('deletePurchaseOrder');
    Route::get('/getPurchaseOrderDetail', [SupplierController::class, "getPurchaseOrderDetail"])->name('getPurchaseOrderDetail');
    Route::post('/updatePurchaseOrderDetail', [SupplierController::class, "updatePurchaseOrderDetail"])->name('updatePurchaseOrderDetail');
    Route::get('/getPoDelivery', [SupplierController::class, "getPoDelivery"])->name('getPoDelivery');
    Route::post('/insertPoDelivery', [SupplierController::class, "insertPoDelivery"])->name('insertPoDelivery');
    Route::post('/updatePoDelivery', [SupplierController::class, "updatePoDelivery"])->name('updatePoDelivery');
    Route::post('/deletePoDelivery', [SupplierController::class, "deletePoDelivery"])->name('deletePoDelivery');
    Route::get('/getPoInvoice', [SupplierController::class, "getPoInvoice"])->name('getPoInvoice');
    Route::get('/getPoReceipt', [SupplierController::class, "getPoReceipt"])->name('getPoReceipt');
    Route::post('/insertInvoicePO', [SupplierController::class, "insertInvoicePO"])->name('insertInvoicePO');
    Route::post('/updateInvoicePO', [SupplierController::class, "updateInvoicePO"])->name('updateInvoicePO');
    Route::post('/deleteInvoicePO', [SupplierController::class, "deleteInvoicePO"])->name('deleteInvoicePO');
    Route::post('/acceptInvoicePO', [SupplierController::class, "acceptInvoicePO"])->name('acceptInvoicePO');
    Route::post('/declineInvoicePO', [SupplierController::class, "declineInvoicePO"])->name('declineInvoicePO');
    Route::post('/accPoDelivery', [SupplierController::class, "accPoDelivery"])->name('accPoDelivery');
    Route::post('/declinePoDelivery', [SupplierController::class, "declinePoDelivery"])->name('declinePoDelivery');
    Route::post('/pelunasanPurchaseOrder', [SupplierController::class, "pelunasanPurchaseOrder"])->name('pelunasanPurchaseOrder');

    Route::get('/getReturnSupplies', [SupplierController::class, "getReturnSupplies"])->name('getReturnSupplies');
    Route::post('/insertReturnSupplies', [SupplierController::class, "insertReturnSupplies"])->name('insertReturnSupplies');
    Route::post('/updateReturnSupplies', [SupplierController::class, "updateReturnSupplies"])->name('updateReturnSupplies');
    Route::post('/deleteReturnSupplies', [SupplierController::class, "deleteReturnSupplies"])->name('deleteReturnSupplies');

    // Route::get('/manageStock',[StockController::class,"ManageStock"])->name('manageStock');
    // Route::post('/insertManageStocks',[StockController::class,"insertManageStocks"])->name('insertManageStocks');
    // Route::get('/getManageStock',[StockController::class,"getManageStock"])->name('getManageStock');

    Route::get('/product',[ProductController::class,"Product"])->name('product');
    Route::get('/getProduct',[ProductController::class,"getProduct"])->name('getProduct');
    Route::get('/getProductVariant',[ProductController::class,"getProductVariant"])->name('getProductVariant');
    Route::get('/insertProduct', [ProductController::class, "viewInsertProduct"])->name('viewInsertProduct');
    Route::post('/insertProduct', [ProductController::class, "insertProduct"])->name('insertProduct');
    Route::get('/updateProduct/{id}', [ProductController::class, "ViewUpdateProduct"])->name('ViewUpdateProduct');
    Route::post('/updateProduct', [ProductController::class, "updateProduct"])->name('updateProduct');
    Route::post('/deleteProduct', [ProductController::class, "deleteProduct"])->name('deleteProduct');

    Route::get('/stockProduct',[StockController::class,"Stock"])->name('stockProduct');
    Route::get('/getStock',[StockController::class,"getStock"])->name('getStock');
        
    Route::get('/stockSupplies',[StockController::class,"StockSupplies"])->name('stockSupplies');
    Route::get('/getStockSupplies',[StockController::class,"getStockSupplies"])->name('getStockSupplies');

    Route::get('/supplies',[ProductController::class,"Supplies"])->name('supplies');
    Route::get('/getSupplies',[ProductController::class,"getSupplies"])->name('getSupplies');
    Route::get('/getSuppliesVariant',[ProductController::class,"getSuppliesVariant"])->name('getSuppliesVariant');
    Route::post('/insertSupplies', [ProductController::class, "insertSupplies"])->name('insertSupplies');
    Route::post('/updateSupplies', [ProductController::class, "updateSupplies"])->name('updateSupplies');
    Route::post('/deleteSupplies', [ProductController::class, "deleteSupplies"])->name('deleteSupplies');
    Route::post('/insertSuppliesUnit',[ProductController::class,"insertSuppliesUnit"])->name('insertSuppliesUnit');
    Route::post('/insertSuppliesRelation',[ProductController::class,"insertSuppliesRelation"])->name('insertSuppliesRelation');

    Route::get('/role',[UserController::class,"role"])->name('role');
    Route::get('/getRole', [UserController::class, "getRole"])->name('getRole');
    Route::post('/insertRole', [UserController::class, "insertRole"])->name('insertRole');
    Route::post('/updateRole', [UserController::class, "updateRole"])->name('updateRole');
    Route::post('/deleteRole', [UserController::class, "deleteRole"])->name('deleteRole');

    Route::get('/permission/{id}',[UserController::class,"permission"])->name('permission');
    Route::get('/getPermission', [UserController::class, "getPermission"])->name('getPermission');
    Route::post('/insertPermission', [UserController::class, "insertPermission"])->name('insertPermission');
    Route::post('/updatePermission', [UserController::class, "updatePermission"])->name('updatePermission');
    Route::post('/deletePermission', [UserController::class, "deletePermission"])->name('deletePermission');

    //customer
    Route::get('/customer',[CustomerController::class,"customer"])->name('customer');
    Route::get('/customerDetail/{id}',[CustomerController::class,"customerDetail"])->name('customerDetail');
    Route::get('/getCustomer', [CustomerController::class, "getCustomer"])->name('getCustomer');
    Route::get('/insertCustomer', [CustomerController::class, "viewInsertCustomer"])->name('viewInsertCustomer');
    Route::post('/insertCustomer', [CustomerController::class, "insertCustomer"])->name('insertCustomer');
    Route::get('/updateCustomer/{id}', [CustomerController::class, "ViewUpdateCustomer"])->name('ViewUpdateCustomer');
    Route::post('/updateCustomer', [CustomerController::class, "updateCustomer"])->name('updateCustomer');
    Route::post('/deleteCustomer', [CustomerController::class, "deleteCustomer"])->name('deleteCustomer');

    //reporting
    Route::get('/reportBahanBaku', [ReportController::class, "reportBahanBaku"])->name('reportBahanBaku');
    Route::get('/reportProduksi', [ReportController::class, "reportProduksi"])->name('reportProduksi');
    Route::get('/ProductReturn', [ReportController::class, "ProductReturn"])->name('ProductReturn');

    Route::get('/cash',[ReportController::class,"Cash"])->name('cash');
    Route::get('/getCash',[ReportController::class,"getCash"])->name('getCash');
    Route::post('/insertCash',[ReportController::class,"insertCash"])->name('insertCash');

    Route::get('/pettyCash',[ReportController::class,"PettyCash"])->name('pettyCash');
    Route::get('/getPettyCash',[ReportController::class,"getPettyCash"])->name('getPettyCash');
    Route::post('/insertPettyCash',[ReportController::class,"insertPettyCash"])->name('insertPettyCash');

    Route::get('/operationalCash',[ReportController::class,"OperationalCash"])->name('operationalCash');
    Route::get('/getCashAdmin', [ReportController::class, "getCashAdmin"])->name('getCashAdmin');
    Route::post('/insertCashAdmin', [ReportController::class, "insertCashAdmin"])->name('insertCashAdmin');
    Route::post('/updateCashAdmin', [ReportController::class, "updateCashAdmin"])->name('updateCashAdmin');
    Route::post('/deleteCashAdmin', [ReportController::class, "deleteCashAdmin"])->name('deleteCashAdmin');
    Route::post('/acceptCashAdmin', [ReportController::class, "acceptCashAdmin"])->name('acceptCashAdmin');
    Route::post('/declineCashAdmin', [ReportController::class, "declineCashAdmin"])->name('declineCashAdmin');


    Route::get('/getCashGudang', [ReportController::class, "getCashGudang"])->name('getCashGudang');
    Route::post('/insertCashGudang', [ReportController::class, "insertCashGudang"])->name('insertCashGudang');
    Route::post('/updateCashGudang', [ReportController::class, "updateCashGudang"])->name('updateCashGudang');
    Route::post('/deleteCashGudang', [ReportController::class, "deleteCashGudang"])->name('deleteCashGudang');

    // supplier
    Route::get('/getSupplier',[SupplierController::class,"getSupplier"])->name('getSupplier');
    Route::get('/supplier',[SupplierController::class,"supplier"])->name('supplier');
    Route::get('/supplierDetail/{id}',[SupplierController::class,"supplierDetail"])->name('supplierDetail');
    Route::get('/insertSupplier', [SupplierController::class, "ViewInsertSupplier"])->name('ViewInsertSupplier');
    Route::post('/insertSupplier', [SupplierController::class, "insertSupplier"])->name('insertSupplier');
    Route::get('/updateSupplier/{id}', [SupplierController::class, "ViewUpdateSupplier"])->name('ViewUpdateSupplier');
    Route::post('/updateSupplier', [SupplierController::class, "updateSupplier"])->name('updateSupplier');
    Route::post('/deleteSupplier', [SupplierController::class, "deleteSupplier"])->name('deleteSupplier');

    // Staff
    Route::get('/staff',[UserController::class,"staff"])->name('staff');
    Route::get('/staffDetail/{id}',[UserController::class,"staffDetail"])->name('staffDetail');
    Route::get('/getStaff', [UserController::class, "getStaff"])->name('getStaff');
    Route::get('/insertStaff', [UserController::class, "viewInsertStaff"])->name('viewInsertStaff');
    Route::post('/insertStaff', [UserController::class, "insertStaff"])->name('insertStaff');
    Route::get('/updateStaff/{id}', [UserController::class, "ViewUpdateStaff"])->name('ViewUpdateStaff');
    Route::post('/updateStaff', [UserController::class, "updateStaff"])->name('updateStaff');
    Route::post('/deleteStaff', [UserController::class, "deleteStaff"])->name('deleteStaff');

    //produksi
    Route::get('/bom',[ProductionController::class,"bom"])->name('bom');
    Route::get('/getBom', [ProductionController::class, "getBom"])->name('getBom');
    Route::post('/insertBom', [ProductionController::class, "insertBom"])->name('insertBom');
    Route::post('/updateBom', [ProductionController::class, "updateBom"])->name('updateBom');
    Route::post('/deleteBom', [ProductionController::class, "deleteBom"])->name('deleteBom');

    Route::get('/production',[ProductionController::class,"production"])->name('production');
    Route::get('/getProduction', [ProductionController::class, "getProduction"])->name('getProduction');
    Route::get('/getPemakaian', [ProductionController::class, "getPemakaian"])->name('getPemakaian');
    Route::post('/insertProduction', [ProductionController::class, "insertProduction"])->name('insertProduction');
    Route::post('/updateProduction', [ProductionController::class, "updateProduction"])->name('updateProduction');
    Route::post('/deleteProduction', [ProductionController::class, "deleteProduction"])->name('deleteProduction');
    Route::post('/accDeleteProduction', [ProductionController::class, "accDeleteProduction"])->name('accDeleteProduction');
    Route::post('/tolakDeleteProduction', [ProductionController::class, "tolakDeleteProduction"])->name('tolakDeleteProduction');
    Route::post('/uploadPhotoProduksi', [ProductionController::class, "uploadPhotoProduksi"])->name('uploadPhotoProduksi');
    Route::get('/getFotoProduksi', [ProductionController::class, "getFotoProduksi"])->name('getFotoProduksi');

    // Settings
    Route::get('/testing',[GeneralController::class,"testing"])->name('testing');
    Route::get('/profiles',[SettingController::class,"Profiles"])->name('profiles');
        
    Route::get('/settings',[SettingController::class,"Settings"])->name('settings');
    Route::post('/getSetting', [SettingController::class, "getSetting"])->name('getSetting');
    Route::post('/insertSetting', [SettingController::class, "insertSetting"])->name('insertSetting');
    Route::post('/updateSetting', [SettingController::class, "updateSetting"])->name('updateSetting');

    // Wilayah
    Route::get('/area',[GeneralController::class,"Area"])->name('area');
    Route::get('/getArea', [GeneralController::class, "getArea"])->name('getArea');
    Route::post('/insertArea', [GeneralController::class, "insertArea"])->name('insertArea');
    Route::post('/updateArea', [GeneralController::class, "updateArea"])->name('updateArea');
    Route::post('/deleteArea', [GeneralController::class, "deleteArea"])->name('deleteArea');

    // kategori kas
    Route::get('/cashCategory',[ReportController::class,"CashCategory"])->name('cashCategory');
    Route::get('/getCashCategory', [ReportController::class, "getCashCategory"])->name('getCashCategory');
    Route::post('/insertCashCategory', [ReportController::class, "insertCashCategory"])->name('insertCashCategory');
    Route::post('/updateCashCategory', [ReportController::class, "updateCashCategory"])->name('updateCashCategory');
    Route::post('/deleteCashCategory', [ReportController::class, "deleteCashCategory"])->name('deleteCashCategory');

    //bank
    Route::get('/bank',[UserController::class,"bank"])->name('bank');
    Route::get('/getBank', [UserController::class, "getBank"])->name('getBank');
    Route::post('/insertBank', [UserController::class, "insertBank"])->name('insertBank');
    Route::post('/updateBank', [UserController::class, "updateBank"])->name('updateBank');
    Route::post('/deleteBank', [UserController::class, "deleteBank"])->name('deleteBank');

    Route::get('/tt',[SupplierController::class,"tt"])->name('tt');
    Route::get('/getTt', [SupplierController::class, "getTt"])->name('getTt');
    Route::post('/insertTt', [SupplierController::class, "insertTt"])->name('insertTt');
    Route::post('/updateTt', [SupplierController::class, "updateTt"])->name('updateTt');
    Route::post('/deleteTt', [SupplierController::class, "deleteTt"])->name('deleteTt');
    Route::post('/accTt', [SupplierController::class, "accTt"])->name('accTt');
    Route::post('/declineTt', [SupplierController::class, "declineTt"])->name('declineTt');
    Route::get('/generateTandaTerima/{id}/{kode}', [SupplierController::class, "generateTandaTerima"])->name('generateTandaTerima');
    Route::get('/generateTandaTerimaInvoice', [SupplierController::class, "generateTandaTerimaInvoice"])->name('generateTandaTerimaInvoice');
    Route::get('/viewTandaTerima/{id}', [SupplierController::class, "viewTandaTerima"])->name('generateTandaTerima');

    // Log
    Route::get('/getLog', [GeneralController::class, "getLog"])->name('getLog');
});