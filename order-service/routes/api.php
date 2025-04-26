<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['jwt.verify'])->group(function () {
    Route::prefix('orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\OrderController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\OrderController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\OrderController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\OrderController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\OrderController::class, 'destroy']);
    });
});
