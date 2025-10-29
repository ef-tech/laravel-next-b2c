<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
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
