<?php

use App\Http\Controllers\Config\AccessController;
use App\Http\Controllers\Config\DropdownController;
use App\Http\Controllers\Config\MenuController;
use App\Http\Controllers\Config\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('util')->group(function () {
    Route::get('/access', [AccessController::class, 'access']);  
    Route::get('/sidebar/{media}', [MenuController::class, 'sidebar']);  
    Route::get('/navbar', [UserController::class, 'navbar']); 
});

Route::prefix('dropdown')->group(function () {
    Route::get('/fnb-category', [DropdownController::class, 'fnbCategory']);  
});