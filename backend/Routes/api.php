<?php

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\DemoAuthController;

Route::prefix('api/demo')->middleware(['api', 'throttle:60,1'])->group(static function () {
    Route::get('info', [DemoAuthController::class, 'info'])->name('demo.info');
    Route::post('login', [DemoAuthController::class, 'login'])->name('demo.login');
});
