<?php

use App\Http\Controllers\Config\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Critical endpoints with strict rate limiting
    Route::post('/login', [AuthenticationController::class, 'login'])
         ->middleware('rate.limit:5,1') // 5 attempts per 1 minute
         ->name('login');
    
    Route::post('/register', [AuthenticationController::class, 'register'])
         ->middleware('rate.limit:3,1') // 3 attempts per 1 minute
         ->name('register');
    
    // Availability check endpoints
    Route::post('/check-email', [AuthenticationController::class, 'checkEmailAvailability'])
         ->middleware('rate.limit:10,1') // 10 attempts per 1 minute
         ->name('check.email');
    
    Route::post('/check-phone', [AuthenticationController::class, 'checkPhoneAvailability'])
         ->middleware('rate.limit:10,1') // 10 attempts per 1 minute
         ->name('check.phone');
    
    // Google login with moderate rate limiting
    Route::post('/google', [AuthenticationController::class, 'googleLogin'])
         ->middleware('rate.limit:10,1') // 10 attempts per 1 minute
         ->name('google.login');
    
    // Password reset endpoints with rate limiting
    Route::post('/forgot-password', [AuthenticationController::class, 'forgotPassword'])
         ->middleware('rate.limit:3,5') // 3 attempts per 5 minutes
         ->name('forgot.password');
    
    Route::post('/verify-otp', [AuthenticationController::class, 'verifyOtp'])
         ->middleware('rate.limit:10,1') // 10 attempts per 1 minute
         ->name('verify.otp');
    
    Route::post('/reset-password', [AuthenticationController::class, 'resetPassword'])
         ->middleware('rate.limit:3,5') // 3 attempts per 5 minutes
         ->name('reset.password');
});
