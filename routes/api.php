<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\JobTestController;

Route::get('/', [TestController::class, 'index']);
Route::get('/test', [TestController::class, 'test']);

// Queue testing endpoints
Route::prefix('queue')->group(function () {
    Route::get('/status', [JobTestController::class, 'getQueueStatus']);
    Route::post('/email', [JobTestController::class, 'testEmailJob']);
    Route::post('/order', [JobTestController::class, 'testOrderJob']);
    Route::post('/report', [JobTestController::class, 'testReportJob']);
    Route::post('/multiple', [JobTestController::class, 'testMultipleJobs']);
});

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api-group/auth.php';

    // POS without auth
    require __DIR__ . '/api-group/pos.php';

    // CMS POS auth
    Route::middleware('auth:jwt')->group(function () {
        require __DIR__ . '/api-group/util.php';
        require __DIR__ . '/api-group/admin.php';
    });
});
