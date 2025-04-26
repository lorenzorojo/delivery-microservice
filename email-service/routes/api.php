<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['jwt.verify'])->group(function () {
    Route::prefix('emails')->group(function () {
        Route::post('/', [\App\Http\Controllers\EmailController::class, 'sendOrderShippedEmail']);
    });
});
