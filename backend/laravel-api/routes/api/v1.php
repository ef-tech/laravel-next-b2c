<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| V1 APIエンドポイントのルート定義。
| プレフィックス `/api/v1` は bootstrap/app.php で自動付与されます。
|
*/

// Public routes (認証不要)
Route::get('/health', [HealthController::class, 'show'])->name('v1.health');
Route::post('/login', [AuthController::class, 'login'])->name('v1.login');

// Protected routes (認証必須)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('v1.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('v1.user');
});
