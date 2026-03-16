<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Config\DropdownController;
use App\Http\Controllers\Content\HomeController;
use App\Http\Controllers\Content\OrderController;
use App\Http\Controllers\Content\PosController;

Route::prefix('pos')->group(function () {
    
    // Scan QR Code
    Route::get('/table/{uniqueId}', [PosController::class, 'getTableByUniqueId']);
    
    // Active Items & Categories as requested
    Route::get('/items', [PosController::class, 'getItems']);
    Route::get('/categories', [PosController::class, 'getCategories']);

    Route::prefix('dropdown')->group(function () {
        Route::get('/item-categories', [DropdownController::class, 'fnbCategory']);
        Route::get('/fnb-table', [DropdownController::class, 'fnbTable']);
    });

    Route::get('/item/list', [HomeController::class, 'fnbList']);
    Route::get('/item/detail/{id}', [HomeController::class, 'fnbDetail']);
    Route::get('/item-category/list', [HomeController::class, 'fnbCategoryList']);
    
    Route::post('/order', [OrderController::class, 'createOrder']);
    Route::get('/order/list', [OrderController::class, 'orderList']);
    Route::get('/order/detail/{code}', [OrderController::class, 'orderDetail']);
    // Route::post('/order/{code}/done', [OrderController::class, 'markOrderAsDone']);
});
