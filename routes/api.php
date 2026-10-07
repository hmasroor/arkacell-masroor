<?php

use App\Http\Controllers\Api\ProductHistoryController;
use App\Http\Controllers\Api\StockLevelController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

Route::middleware(IdentifyTenant::class)->group(function () {
    Route::post('/stock-movements', [StockMovementController::class, 'store']);
    Route::get('/stock-levels', [StockLevelController::class, 'index']);
    Route::get(
        '/products/{sku}/history',
        [ProductHistoryController::class, 'index']
    );
});
