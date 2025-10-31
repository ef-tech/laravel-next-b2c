<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CspReportController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| V1 API routes with Sanctum authentication and rate limiting.
| All routes are prefixed with /api/v1 automatically.
|
*/

// Public routes
Route::get('/health', [HealthController::class, 'show'])
    ->name('v1.health');

Route::post('/csp/report', [CspReportController::class, 'report'])
    ->middleware('throttle:100,1')
    ->name('v1.csp.report');

Route::post('/users', [UserController::class, 'register'])
    ->name('v1.users.register');

// Login with rate limiting (5 attempts per minute)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->name('v1.login');
});

// Protected routes with rate limiting (60 requests per minute)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('v1.logout');

    Route::get('/user', [AuthController::class, 'user'])
        ->name('v1.user');

    // Token management
    Route::post('/tokens', [TokenController::class, 'store'])
        ->name('v1.tokens.store');

    Route::get('/tokens', [TokenController::class, 'index'])
        ->name('v1.tokens.index');

    Route::delete('/tokens/{id}', [TokenController::class, 'destroy'])
        ->name('v1.tokens.destroy');

    Route::delete('/tokens', [TokenController::class, 'destroyAll'])
        ->name('v1.tokens.destroyAll');
});
