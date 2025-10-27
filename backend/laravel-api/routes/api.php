<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Api\V1\Admin\LogoutController as AdminLogoutController;
use App\Http\Controllers\CspReportController;
use Illuminate\Support\Facades\Route;

// Health check endpoint (no rate limiting, no authentication)
Route::get('/health', function () {
    return response()->json(['status' => 'ok'])
        ->header('Cache-Control', 'no-store');
})->withoutMiddleware('throttle:api')->name('health');

// CSP violation report endpoint (no authentication, but with rate limiting)
Route::post('/csp/report', [CspReportController::class, 'report'])
    ->middleware('throttle:100,1')
    ->name('csp.report');

// Public routes
Route::post('/users', [UserController::class, 'register']);

// Login with rate limiting (5 attempts per minute)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes with rate limiting (60 requests per minute)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Token management
    Route::post('/tokens', [TokenController::class, 'store']);
    Route::get('/tokens', [TokenController::class, 'index']);
    Route::delete('/tokens/{id}', [TokenController::class, 'destroy']);
    Route::delete('/tokens', [TokenController::class, 'destroyAll']);
});

// API v1 routes (APIバージョニング戦略)
Route::prefix('v1')->name('v1.')->group(function () {
    // Admin authentication routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Login with rate limiting (5 attempts per minute)
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/login', AdminLoginController::class)->name('login');
        });

        // Protected routes (authentication required)
        Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            Route::post('/logout', AdminLogoutController::class)->name('logout');
            Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        });
    });
});
