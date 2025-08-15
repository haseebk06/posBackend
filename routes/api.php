<?php

use App\Http\Controllers\BarcodePrintController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StoreInformationController;
use App\Http\Controllers\UserController;


//user
Route::prefix('/user')->group(function () {
    Route::post('/add', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/get', [UserController::class, 'getUser'])->middleware('auth:sanctum');
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
    Route::get('/get/returns', [SaleController::class, 'getReturns']);
    Route::get('/get/all', [SaleController::class, 'getAllTransactions']);
    Route::post('/get/holdItems', [SaleController::class, 'getHoldItems']);
    Route::post('/add', [SaleController::class, 'addSales'])->middleware('auth:sanctum');
    Route::post('/add/soldItems', [SaleController::class, 'addSoldItems'])->middleware('auth:sanctum');
    Route::post('/add/holdItems', [SaleController::class, 'addHoldItems'])->middleware('auth:sanctum');
    Route::post('/return/entire', [SaleController::class, 'returnEntireSale'])->middleware('auth:sanctum');
    Route::post('/return/item', [SaleController::class, 'returnItem'])->middleware('auth:sanctum');
    Route::delete('/delete/{id}', [SaleController::class, 'destroy'])->middleware('auth:sanctum');
});

//store Information
Route::prefix('/store')->group(function () {
    Route::get('/get', [StoreInformationController::class, 'getStoreInfo']);
    Route::post('/add', [StoreInformationController::class, 'addStoreInfo'])->middleware('auth:sanctum');
    Route::post('/update/{id}', [StoreInformationController::class, 'updateStoreInfo'])->middleware('auth:sanctum');
});

Route::prefix('/print')->group(function () {
    Route::post('/barcode', [BarcodePrintController::class, 'printBarcode']);
});
