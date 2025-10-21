<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * PerformanceMonitoring Middleware
 *
 * APIリクエストのパフォーマンスメトリクスを収集し、
 * 運用時のパフォーマンス分析とボトルネック特定を支援します。
 * - レスポンス時間をマイクロ秒精度で測定
 * - ピークメモリ使用量を記録
 * - データベースクエリ実行回数をカウント
 * - レスポンス時間が閾値を超過した場合にアラートログ記録
 * - パーセンタイル値計算を可能にする形式でメトリクス記録
 * - 専用monitoringチャンネルに出力（30日間保持）
 * - 非同期でログ出力を実行（terminateメソッド使用）
 *
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8
 */
final class PerformanceMonitoring
{
    /**
     * データベースクエリ実行回数
     */
    private int $queryCount = 0;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // リクエスト開始時刻を記録（マイクロ秒精度）
        $request->attributes->set('performance_start_time', microtime(true));

        // データベースクエリカウンター設定
        DB::listen(function ($query) {
            $this->queryCount++;
        });

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * レスポンス送信後に実行される非同期パフォーマンスメトリクス収集。
     * クライアントのレスポンス時間に影響を与えない。
     */
    public function terminate(Request $request, Response $response): void
    {
        /** @var float $startTime */
        $startTime = $request->attributes->get('performance_start_time');
        $responseTime = microtime(true) - $startTime;

        // ピークメモリ使用量（MB単位）
        $peakMemoryMb = memory_get_peak_usage(true) / 1024 / 1024;

        // パフォーマンスメトリクスデータの構築
        $metricsData = [
            'request_id' => $request->header('X-Request-Id'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'response_time_ms' => round($responseTime * 1000, 2),
            'peak_memory_mb' => round($peakMemoryMb, 2),
            'db_queries_count' => $this->queryCount,
            'timestamp' => now()->toIso8601String(),
        ];

        // monitoringチャンネルにメトリクスを出力
        Log::channel('monitoring')->info('Performance metrics', $metricsData);

        // レスポンス時間が閾値を超過した場合にアラートログを記録
        $threshold = config('monitoring.metrics.response_time.alert_threshold', 200);
        if ($metricsData['response_time_ms'] > $threshold) {
            Log::channel('monitoring')->warning('Performance alert: Response time exceeded threshold', [
                'request_id' => $metricsData['request_id'],
                'url' => $metricsData['url'],
                'response_time_ms' => $metricsData['response_time_ms'],
                'threshold_ms' => $threshold,
                'timestamp' => $metricsData['timestamp'],
            ]);
        }
    }
}
