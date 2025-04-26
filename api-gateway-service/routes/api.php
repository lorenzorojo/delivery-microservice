<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [GatewayController::class, 'forwardToAuth']);
    Route::post('/register', [GatewayController::class, 'forwardToAuth']);
});

Route::middleware(['jwt.verify'])->group(function () {
    Route::prefix('products')->group(function () {
        Route::any('/', [GatewayController::class, 'forwardToInventory']);
        Route::any('/{any}', [GatewayController::class, 'forwardToInventory'])->where('any', '.*');
    });

    Route::prefix('orders')->group(function () {
        Route::any('/', [GatewayController::class, 'forwardToOrders']);
        Route::any('/{any}', [GatewayController::class, 'forwardToOrders'])->where('any', '.*');
    });
});
