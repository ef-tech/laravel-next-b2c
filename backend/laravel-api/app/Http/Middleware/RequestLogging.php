<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequestLogging Middleware
 *
 * 全てのAPIリクエストとレスポンスをJSON構造化ログとして記録し、
 * 運用時のトラブルシューティングを支援します。
 * - リクエスト開始時刻を記録
 * - レスポンス送信後（terminateメソッド）に非同期でログ出力
 * - 機密データ（パスワード、トークン）を自動マスキング
 * - 専用middlewareチャンネルに出力（30日間保持）
 *
 * Requirements: 1.8, 1.9, 1.10, 1.11, 1.12
 */
final class RequestLogging
{
    /**
     * マスキング対象のフィールド名
     *
     * @var array<int, string>
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // リクエスト開始時刻を記録（マイクロ秒精度）
        $request->attributes->set('logging_start_time', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * レスポンス送信後に実行される非同期ログ出力。
     * クライアントのレスポンス時間に影響を与えない。
     */
    public function terminate(Request $request, Response $response): void
    {
        /** @var float $startTime */
        $startTime = $request->attributes->get('logging_start_time');
        $duration = microtime(true) - $startTime;

        // リクエストデータの取得と機密データマスキング
        $requestData = $this->maskSensitiveData($request->all());

        // 構造化ログデータの構築
        $logData = [
            'request_id' => $request->header('X-Request-Id'),
            'correlation_id' => $request->header('X-Correlation-Id'),
            'user_id' => $request->user()?->id,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_data' => json_encode($requestData),
            'timestamp' => now()->utc()->toIso8601String(),
        ];

        // middlewareチャンネルに構造化ログを出力
        Log::channel('middleware')->info('Request completed', $logData);
    }

    /**
     * 機密データをマスキングする
     *
     * @param  array<string, mixed>  $data  マスキング対象のデータ
     * @return array<string, mixed> マスキング済みデータ
     */
    private function maskSensitiveData(array $data): array
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***MASKED***';
            }
        }

        return $data;
    }
}
