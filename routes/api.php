<?php

use App\Http\Controllers\BarcodePrintController;
use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StoreInformationController;
use App\Http\Controllers\UserController;

//user
Route::prefix('/user')->group(function () {
    Route::post('/add', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/get', [UserController::class, 'getUser'])->middleware('auth:sanctum');
    Route::put('/change', [UserController::class, 'changePassword'])->middleware('auth:sanctum');
    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
    Route::delete('/delete/{userId}', [UserController::class, 'destroy'])->middleware('auth:sanctum');
});

//stock
Route::prefix('/stock')->group(function () {
    Route::get('/get', [StockController::class, 'getStock']);
    Route::post('/add', [StockController::class, 'addStock'])->middleware('auth:sanctum');
    Route::put('/update/{id}', [StockController::class, 'updateStock'])->middleware('auth:sanctum');
    Route::post('/reduce/multiple', [StockController::class, 'updateMultipleStocks'])->middleware('auth:sanctum');
    Route::delete('/delete/{stockId}', [StockController::class, 'destroy'])->middleware('auth:sanctum');
});

//sale
Route::prefix('/sale')->group(function () {
    Route::get('/get', [SaleController::class, 'getSales']);
    Route::get('/get/{id}/{userId}', [SaleController::class, 'getCurrentShiftSales']);
    Route::get('/get/prev/{id}/{userId}', [SaleController::class, 'getPreviousShiftSales']);
    Route::get('/get/returns', [SaleController::class, 'getReturns']);
    Route::get('/get/all', [SaleController::class, 'getAllTransactions']);
    Route::post('/get/holdItems', [SaleController::class, 'getHoldItems']);
    Route::post('/add', [SaleController::class, 'addSales'])->middleware('auth:sanctum');
    Route::post('/add/soldItems', [SaleController::class, 'addSoldItems'])->middleware('auth:sanctum');
    Route::post('/add/holdItems', [SaleController::class, 'addHoldItems'])->middleware('auth:sanctum');
    Route::post('/return/entire/{id}', [SaleController::class, 'returnEntireSale'])->middleware('auth:sanctum');
    Route::post('/return/item', [SaleController::class, 'returnItem'])->middleware('auth:sanctum');
    Route::delete('/delete/{id}', [SaleController::class, 'destroy'])->middleware('auth:sanctum');
});

//store Information
Route::prefix('/store')->group(function () {
    Route::get('/get', [StoreInformationController::class, 'getStoreInfo']);
    Route::post('/add', [StoreInformationController::class, 'addStoreInfo'])->middleware('auth:sanctum');
    Route::post('/update/{id}', [StoreInformationController::class, 'updateStoreInfo'])->middleware('auth:sanctum');
});

//shift Information
Route::prefix('/shift')->group(function () {
    Route::post('/open', [ShiftController::class, 'openShift'])->middleware('auth:sanctum');
    Route::post('/{id}/close', [ShiftController::class, 'closeShift'])->middleware('auth:sanctum');
    Route::get('/current/{id}', [ShiftController::class, 'currentShift']);
    Route::get('/{id}', [ShiftController::class, 'allShiftsById'])->middleware('auth:sanctum');
    Route::get('/all/{id}', [ShiftController::class, 'allShifts']);
});

Route::prefix('/counter')->group(function () {
    Route::post('/add', [ShiftController::class, 'addCounter']);
    Route::delete('/delete/{id}', [ShiftController::class, 'deleteCounter']);
    Route::post('/get', [ShiftController::class, 'allCounters']);
    Route::put('/open/{id}', [ShiftController::class, 'openCounter']);
    Route::put('/close/{id}', [ShiftController::class, 'closeCounter']);
    Route::get('/reports', [ShiftController::class, 'dailyReports']);
    Route::get('/reports/generate', [ShiftController::class, 'generateReportManually']);
});

Route::prefix('/menu')->group(function () {
    // Categories
    Route::post('/category', [MenuController::class, 'storeCategory']);
    Route::get('/categories', [MenuController::class, 'getCategories']);

    // Items
    Route::post('/item', [MenuController::class, 'storeItem']);
    Route::get('/items/{categoryId}', [MenuController::class, 'getItemsByCategory']);

    // Variants
    Route::post('/item/{itemId}/variant', [MenuController::class, 'storeVariant']);

    // Full menu
    Route::get('/all', [MenuController::class, 'getFullMenu']);
});

Route::prefix('/print')->group(function () {
    Route::post('/barcode', [BarcodePrintController::class, 'printBarcode']);
});
