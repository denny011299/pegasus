<?php

use App\Http\Controllers\ProductController;
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