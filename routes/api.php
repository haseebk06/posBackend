<?php

use App\Http\Controllers\BarcodePrintController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StoreInformationController;
use App\Http\Controllers\MenuSectionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServerController;


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
    Route::get('/get/retrun/{id}/{userId}', [SaleController::class, 'getCurrentShiftRetruns']);
    Route::get('/prev/retrun/{id}/{userId}', [SaleController::class, 'getPreviousShiftRetruns']);
    Route::get('/get/prev/{id}/{userId}', [SaleController::class, 'getPreviousShiftSales']);
    Route::get('/get/returns', [SaleController::class, 'getReturns']);
    Route::get('/get/all', [SaleController::class, 'getAllTransactions']);
    Route::post('/get/holdItems', [SaleController::class, 'getHoldItems']);
    Route::post('/add', [SaleController::class, 'addSales'])->middleware('auth:sanctum');
    Route::post('/add/soldItems', [SaleController::class, 'addSoldItems'])->middleware('auth:sanctum');
    Route::post('/add/holdItems', [SaleController::class, 'addHoldItems'])->middleware('auth:sanctum');
    Route::post('/return/add', [SaleController::class, 'addReturns'])->middleware('auth:sanctum');
    Route::delete('/delete/{id}', [SaleController::class, 'destroy'])->middleware('auth:sanctum');
});

//order
Route::prefix('/order')->group(function () {
    Route::get('/get', [OrderController::class, 'getOrders']);
    Route::post('/get/holdOrders', [OrderController::class, 'getHoldOrders']);
    Route::post('/add', [OrderController::class, 'addOrders'])->middleware('auth:sanctum');
    Route::post('/add/orderItems', [OrderController::class, 'addOrderItems'])->middleware('auth:sanctum');
    Route::post('/addOns/orderItems/{orderId}', [OrderController::class, 'addOrderAddons'])->middleware('auth:sanctum');
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
    Route::get('/prev/{id}', [ShiftController::class, 'prevShift']);
    Route::get('/{id}', [ShiftController::class, 'allShiftsById'])->middleware('auth:sanctum');
    Route::get('/all/{id}', [ShiftController::class, 'allShifts']);
});

Route::prefix('/counter')->group(function () {
    Route::post('/add', [ShiftController::class, 'addCounter'])->middleware('auth:sanctum');
    Route::get('/get', [ShiftController::class, 'allCounters']);
    Route::put('/open/{id}', [ShiftController::class, 'openCounter'])->middleware('auth:sanctum');
    Route::put('/close/{id}', [ShiftController::class, 'closeCounter'])->middleware('auth:sanctum');
    Route::get('/reports', [ShiftController::class, 'dailyReports']);
    Route::get('/reports/generate', [ShiftController::class, 'generateReportManually']);
});

Route::prefix('/menu')->group(function () {
    // Categories
    Route::post('/category', [MenuController::class, 'storeCategory']);
    Route::get('/categories', [MenuController::class, 'getCategories']);
    Route::put('/category/{id}', [MenuController::class, 'updateCategory']);
    Route::delete('/category/{id}', [MenuController::class, 'deleteCategory']);

    // Items
    Route::post('/item', [MenuController::class, 'storeItem']);
    Route::get('/items/{categoryId}', [MenuController::class, 'getItemsByCategory']);
    Route::put('/item/{id}', [MenuController::class, 'updateItem']);
    Route::delete('/item/{id}', [MenuController::class, 'deleteItem']);

    // Variants
    Route::post('/item/{itemId}/variant', [MenuController::class, 'storeVariant']);
    Route::put('/variant/{id}', [MenuController::class, 'updateVariant']);
    Route::delete('/variant/{id}', [MenuController::class, 'deleteVariant']);

    // Full menu
    Route::get('/all', [MenuController::class, 'getFullMenu']);
});

Route::prefix('sections')->group(function () {
    Route::get('/all', [MenuSectionController::class, 'index']);
    Route::post('/add', [MenuSectionController::class, 'addSections'])->middleware('auth:sanctum');
    Route::get('/{id}', [MenuSectionController::class, 'show']);
    Route::put('/{id}', [MenuSectionController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{id}', [MenuSectionController::class, 'destroy'])->middleware('auth:sanctum');
    Route::post('/{id}/items', [MenuSectionController::class, 'attachItems'])->middleware('auth:sanctum');
    Route::delete('/{id}/items/{itemId}', [MenuSectionController::class, 'detachItem'])->middleware('auth:sanctum');
});

Route::prefix('/tables')->group(function () {
    Route::post('/', [MenuController::class, 'store']);
    Route::get('/', [MenuController::class, 'index']);
    Route::get('/{id}', [MenuController::class, 'show']);
    Route::put('/{id}', [MenuController::class, 'update']);
    Route::put('/status/{id}/{status}/{pay}/{orderId?}/{serverId?}', [MenuController::class, 'updateStatus']);
    Route::delete('/{id}', [MenuController::class, 'destroy']);
});

Route::prefix('/server')->group(function () {
    Route::post('/add', [ServerController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/get', [ServerController::class, 'index']);
    Route::delete('/{id}', [ServerController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::prefix('/print')->group(function () {
    Route::post('/barcode', [BarcodePrintController::class, 'printBarcode']);
});

