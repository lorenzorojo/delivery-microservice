<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['jwt.verify'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login')->withoutMiddleware(['jwt.verify']);
        Route::post('logout', [\App\Http\Controllers\AuthController::class, 'logout']);
        Route::post('me', [\App\Http\Controllers\AuthController::class, 'me']);
        Route::post('refresh', [\App\Http\Controllers\AuthController::class, 'refresh']);
        Route::post('register', [\App\Http\Controllers\AuthController::class, 'register'])->withoutMiddleware(['jwt.verify']);
    });
});


