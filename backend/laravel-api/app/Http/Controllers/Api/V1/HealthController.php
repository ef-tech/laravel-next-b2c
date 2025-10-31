<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Ddd\Infrastructure\Http\Presenters\V1\HealthPresenter;
use Illuminate\Http\JsonResponse;

/**
 * V1 ヘルスチェックコントローラー
 *
 * APIサーバーの稼働状態を確認するエンドポイントを提供します。
 */
class HealthController extends Controller
{
    /**
     * ヘルスチェック
     */
    public function show(): JsonResponse
    {
        return response()->json(
            HealthPresenter::present(now())
        )->header('Cache-Control', 'no-store');
    }
}
