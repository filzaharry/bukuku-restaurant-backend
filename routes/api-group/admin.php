<?php

use App\Http\Controllers\Content\FnbMenuController;
use App\Http\Controllers\Content\FnbCategoryController;
use App\Http\Controllers\Content\FnbOrderController;
use App\Http\Controllers\Content\FnbTableController;
use Illuminate\Support\Facades\Route;


Route::prefix('fnb')->group(function () {
    Route::prefix('menu')->group(function () {
        Route::get('/', [FnbMenuController::class, 'index']);
        Route::post('/', [FnbMenuController::class, 'store']);
        Route::get('/{id}', [FnbMenuController::class, 'show']);
        Route::post('/{id}', [FnbMenuController::class, 'update']);
        Route::delete('/{id}', [FnbMenuController::class, 'destroy']);
    });

    Route::prefix('category')->group(function () {
        Route::get('/', [FnbCategoryController::class, 'index']);
        Route::post('/', [FnbCategoryController::class, 'store']);
        Route::get('/{id}', [FnbCategoryController::class, 'show']);
        Route::post('/{id}', [FnbCategoryController::class, 'update']);
        Route::delete('/{id}', [FnbCategoryController::class, 'destroy']);
    });

    Route::get('/kitchen', [FnbOrderController::class, 'kitchen']);
    
    Route::prefix('order')->group(function () {
        Route::get('/', [FnbOrderController::class, 'index']);
        Route::post('/', [FnbOrderController::class, 'store']);

        Route::get('/{id}', [FnbOrderController::class, 'show']);
        Route::post('/{id}', [FnbOrderController::class, 'update']);
        Route::delete('/{id}', [FnbOrderController::class, 'destroy']);

        Route::get('/{id}/{status}', [FnbOrderController::class, 'updateStatus']);
    });

    Route::prefix('table')->group(function () {
        Route::get('/', [FnbTableController::class, 'index']);
        Route::get('/count', [FnbTableController::class, 'count']);
        Route::post('/', [FnbTableController::class, 'store']);
        Route::get('/{id}', [FnbTableController::class, 'show']);
        Route::post('/{id}', [FnbTableController::class, 'update']);
        Route::delete('/{id}', [FnbTableController::class, 'destroy']);
    });
});
