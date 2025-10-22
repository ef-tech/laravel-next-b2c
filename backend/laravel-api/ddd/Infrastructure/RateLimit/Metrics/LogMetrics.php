<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\RateLimit\Metrics;

use Ddd\Application\RateLimit\Contracts\RateLimitMetrics;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Illuminate\Support\Facades\Log;

/**
 * 構造化ログベースのレート制限メトリクス実装
 *
 * Laravel Log Facadeを使用してJSON形式の構造化ログを出力。
 * ログレベルは重要度に応じて自動的に選択される。
 *
 * ログレベル方針:
 * - info: 正常なレート制限チェック（許可）、通常のレイテンシ（<10ms）
 * - warning: レート制限チェック（拒否）、プライマリストア障害（フェイルオーバー成功）、遅いレイテンシ（>=10ms）
 * - error: レート制限ブロック（429返却）
 * - critical: プライマリ/セカンダリ両方の障害（フェイルオーバー失敗）
 *
 * 出力形式:
 * 全てのログは構造化されたコンテキスト配列とともに出力される。
 * CloudWatch Logs、Datadog、Elasticsearch等の集約システムで検索・分析が容易。
 */
final class LogMetrics implements RateLimitMetrics
{
    /**
     * 遅延とみなすレイテンシ閾値（ミリ秒）
     */
    private const float SLOW_LATENCY_THRESHOLD_MS = 10.0;

    /**
     * レート制限ヒットを記録
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     * @param  bool  $allowed  許可フラグ（true: 許可, false: 拒否）
     * @param  int  $attempts  試行回数
     */
    public function recordHit(RateLimitKey $key, RateLimitRule $rule, bool $allowed, int $attempts): void
    {
        $context = [
            'key' => $key->getKey(),
            'endpoint_type' => $rule->getEndpointType(),
            'max_attempts' => $rule->getMaxAttempts(),
            'decay_minutes' => $rule->getDecayMinutes(),
            'allowed' => $allowed,
            'attempts' => $attempts,
        ];

        if ($allowed) {
            Log::info('rate_limit.hit', $context);
        } else {
            Log::warning('rate_limit.hit', $context);
        }
    }

    /**
     * レート制限ブロックを記録
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     * @param  int  $attempts  試行回数
     * @param  int  $retryAfter  再試行までの秒数
     */
    public function recordBlock(RateLimitKey $key, RateLimitRule $rule, int $attempts, int $retryAfter): void
    {
        $context = [
            'key' => $key->getKey(),
            'endpoint_type' => $rule->getEndpointType(),
            'max_attempts' => $rule->getMaxAttempts(),
            'decay_minutes' => $rule->getDecayMinutes(),
            'attempts' => $attempts,
            'retry_after' => $retryAfter,
        ];

        Log::error('rate_limit.blocked', $context);
    }

    /**
     * レート制限ストア障害を記録
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     * @param  string  $errorMessage  エラーメッセージ
     * @param  bool  $failedOver  フェイルオーバー実行フラグ
     */
    public function recordFailure(RateLimitKey $key, RateLimitRule $rule, string $errorMessage, bool $failedOver): void
    {
        $context = [
            'key' => $key->getKey(),
            'endpoint_type' => $rule->getEndpointType(),
            'max_attempts' => $rule->getMaxAttempts(),
            'decay_minutes' => $rule->getDecayMinutes(),
            'error' => $errorMessage,
            'failed_over' => $failedOver,
        ];

        if ($failedOver) {
            // フェイルオーバー成功（セカンダリストアで継続）
            Log::warning('rate_limit.failure', $context);
        } else {
            // フェイルオーバー失敗（全ストア障害）
            Log::critical('rate_limit.failure', $context);
        }
    }

    /**
     * レート制限チェックのレイテンシを記録
     *
     * @param  float  $latencyMs  レイテンシ（ミリ秒）
     * @param  string  $store  使用したストア名（primary/secondary）
     */
    public function recordLatency(float $latencyMs, string $store): void
    {
        $context = [
            'latency_ms' => $latencyMs,
            'store' => $store,
        ];

        if ($latencyMs >= self::SLOW_LATENCY_THRESHOLD_MS) {
            Log::warning('rate_limit.latency', $context);
        } else {
            Log::info('rate_limit.latency', $context);
        }
    }
}
