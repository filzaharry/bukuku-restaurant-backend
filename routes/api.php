<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TestController;

Route::get('/', [TestController::class, 'index']);
Route::get('/test', [TestController::class, 'test']);

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
