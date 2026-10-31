<?php

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\DemoAuthController;

// Separate per-IP buckets: every visitor requests both, and many may share one office IP
Route::prefix('api/demo')->middleware('api')->group(static function () {
    Route::get('info', [DemoAuthController::class, 'info'])
        ->middleware('throttle:120,1,demo-info')->name('demo.info');
    Route::post('login', [DemoAuthController::class, 'login'])
        ->middleware('throttle:60,1,demo-login')->name('demo.login');
});
