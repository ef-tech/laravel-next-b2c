<?php

declare(strict_types=1);

namespace Ddd\Application\RateLimit\Contracts;

use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;

/**
 * レート制限メトリクスインターフェース
 *
 * Application層がInfrastructure層のメトリクス実装に依存しないための抽象化。
 * 依存性逆転原則（DIP）に基づき、Application層がこのインターフェースに依存し、
 * Infrastructure層が実装を提供する。
 *
 * 実装例:
 * - LogMetrics: 構造化ログ出力（JSON形式）
 * - PrometheusMetrics: Prometheusメトリクスエクスポート
 * - CloudWatchMetrics: AWS CloudWatch統合
 * - NullMetrics: メトリクス記録を無効化（本番以外の環境用）
 *
 * 設計方針:
 * - 非同期・非ブロッキングな記録を想定
 * - メトリクス記録の失敗がアプリケーションの動作に影響しない
 * - パフォーマンス影響を最小化（1ms以下）
 */
interface RateLimitMetrics
{
    /**
     * レート制限ヒットを記録
     *
     * レート制限チェックが実行された際に呼び出される。
     * 許可・拒否どちらの場合も記録する。
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     * @param  bool  $allowed  許可フラグ（true: 許可, false: 拒否）
     * @param  int  $attempts  試行回数
     */
    public function recordHit(RateLimitKey $key, RateLimitRule $rule, bool $allowed, int $attempts): void;

    /**
     * レート制限ブロックを記録
     *
     * リクエストがレート制限により拒否された際に呼び出される。
     * HTTPステータス429返却時に記録。
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     * @param  int  $attempts  試行回数
     * @param  int  $retryAfter  再試行までの秒数
     */
    public function recordBlock(RateLimitKey $key, RateLimitRule $rule, int $attempts, int $retryAfter): void;

    /**
     * レート制限ストア障害を記録
     *
     * Redis障害等によりレート制限チェックが失敗した際に呼び出される。
     * フェイルオーバー発生時のアラート用途。
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     * @param  string  $errorMessage  エラーメッセージ
     * @param  bool  $failedOver  フェイルオーバー実行フラグ
     */
    public function recordFailure(RateLimitKey $key, RateLimitRule $rule, string $errorMessage, bool $failedOver): void;

    /**
     * レート制限チェックのレイテンシを記録
     *
     * レート制限チェック処理の実行時間をミリ秒単位で記録。
     * パフォーマンス監視用途。
     *
     * @param  float  $latencyMs  レイテンシ（ミリ秒）
     * @param  string  $store  使用したストア名（primary/secondary）
     */
    public function recordLatency(float $latencyMs, string $store): void;
}
