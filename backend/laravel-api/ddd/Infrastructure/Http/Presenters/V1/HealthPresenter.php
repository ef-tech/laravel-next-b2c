<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Http\Presenters\V1;

use Illuminate\Support\Carbon;

/**
 * V1 ヘルスチェック Presenter
 *
 * ヘルスチェックレスポンスをV1形式に変換します。
 */
final class HealthPresenter
{
    /**
     * ヘルスチェックレスポンスを生成
     *
     * @param  Carbon  $timestamp  現在時刻
     * @return array{status: string, timestamp: string}
     */
    public static function present(Carbon $timestamp): array
    {
        return [
            'status' => 'ok',
            'timestamp' => $timestamp->toIso8601String(),
        ];
    }
}
