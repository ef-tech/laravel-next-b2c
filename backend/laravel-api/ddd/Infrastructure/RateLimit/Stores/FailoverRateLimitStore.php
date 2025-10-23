<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\RateLimit\Stores;

use Carbon\Carbon;
use Ddd\Application\RateLimit\Contracts\RateLimitMetrics;
use Ddd\Application\RateLimit\Contracts\RateLimitService;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitResult;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Throwable;

/**
 * フェイルオーバー対応レート制限ストア実装
 *
 * Redis障害時の高可用性を実現するプライマリ/セカンダリストア構成。
 * プライマリストア（Redis）が障害時、セカンダリストア（Array/File）に自動フェイルオーバー。
 *
 * アーキテクチャ特性:
 * - 自動障害検知とフェイルオーバー（1リクエスト以内）
 * - 30秒間隔のヘルスチェックによる自動復旧
 * - フェイルオーバー時の制限値緩和（2倍）
 * - 完全なメトリクス記録（障害・レイテンシ）
 *
 * 設計方針:
 * - プライマリストア障害を検知したら即座にセカンダリに切り替え
 * - ヘルスチェック成功でプライマリに自動ロールバック
 * - セカンダリストア使用時はユーザー体験維持のため制限を緩和
 * - 全ての操作をメトリクス記録（監視・アラート用途）
 */
final class FailoverRateLimitStore implements RateLimitService
{
    /**
     * ヘルスチェック間隔（秒）
     */
    private const int HEALTH_CHECK_INTERVAL_SECONDS = 30;

    /**
     * セカンダリストア使用時の制限値緩和倍率
     */
    private const int RATE_LIMIT_RELAXATION_FACTOR = 2;

    /**
     * プライマリストアが使用可能かどうか
     */
    private bool $isPrimaryAvailable = true;

    /**
     * フェイルオーバー発生時刻（ヘルスチェック基準時刻）
     */
    private ?Carbon $failoverAt = null;

    /**
     * @param  RateLimitService  $primary  プライマリストア（Redis）
     * @param  RateLimitService  $secondary  セカンダリストア（Array/File）
     * @param  RateLimitMetrics  $metrics  メトリクス記録
     */
    public function __construct(
        private readonly RateLimitService $primary,
        private readonly RateLimitService $secondary,
        private readonly RateLimitMetrics $metrics,
    ) {}

    /**
     * レート制限チェックを実行
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     */
    public function checkLimit(RateLimitKey $key, RateLimitRule $rule): RateLimitResult
    {
        // プライマリストアが使用不可の場合、ヘルスチェックを実行
        if (! $this->isPrimaryAvailable && $this->shouldRunHealthCheck()) {
            $this->runHealthCheck();
        }

        // プライマリストア使用可能時はプライマリを使用
        if ($this->isPrimaryAvailable) {
            return $this->executeWithPrimary($key, $rule);
        }

        // セカンダリストアにフェイルオーバー
        return $this->executeWithSecondary($key, $rule);
    }

    /**
     * レート制限カウンターをリセット
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     */
    public function resetLimit(RateLimitKey $key): void
    {
        $startTime = microtime(true);

        try {
            if ($this->isPrimaryAvailable) {
                $this->primary->resetLimit($key);
                $this->recordLatency($startTime, 'primary');
            } else {
                $this->secondary->resetLimit($key);
                $this->recordLatency($startTime, 'secondary');
            }
        } catch (Throwable $e) {
            $this->recordLatency($startTime, 'secondary');

            throw $e;
        }
    }

    /**
     * レート制限状態を取得（カウンター増加なし）
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     */
    public function getStatus(RateLimitKey $key, RateLimitRule $rule): RateLimitResult
    {
        $startTime = microtime(true);

        try {
            if ($this->isPrimaryAvailable) {
                $result = $this->primary->getStatus($key, $rule);
                $this->recordLatency($startTime, 'primary');

                return $result;
            }

            $relaxedRule = $this->relaxRule($rule);
            $result = $this->secondary->getStatus($key, $relaxedRule);
            $this->recordLatency($startTime, 'secondary');

            return $result;
        } catch (Throwable $e) {
            $this->recordLatency($startTime, 'secondary');

            throw $e;
        }
    }

    /**
     * ヘルスチェックを実行すべきか判定
     */
    private function shouldRunHealthCheck(): bool
    {
        // フェイルオーバーが発生していない場合は実行しない
        if ($this->failoverAt === null) {
            return false;
        }

        // フェイルオーバーから30秒経過したか
        $diff = $this->failoverAt->diffInSeconds(Carbon::now());

        return $diff >= self::HEALTH_CHECK_INTERVAL_SECONDS;
    }

    /**
     * プライマリストアのヘルスチェックを実行
     */
    private function runHealthCheck(): void
    {
        $healthCheckKey = RateLimitKey::create('rate_limit:health_check:'.uniqid());
        $healthCheckRule = RateLimitRule::create('health_check', 1, 1);

        $startTime = microtime(true);

        try {
            $this->primary->getStatus($healthCheckKey, $healthCheckRule);
            $this->recordLatency($startTime, 'primary');
            // ヘルスチェック成功 → プライマリ復帰
            $this->isPrimaryAvailable = true;
            $this->failoverAt = null; // フェイルオーバー状態をクリア
        } catch (Throwable) {
            // ヘルスチェック失敗 → セカンダリ継続使用（次回30秒後に再試行）
            $this->isPrimaryAvailable = false;
            $this->failoverAt = Carbon::now(); // 次回ヘルスチェックタイマーをリセット
        }
    }

    /**
     * プライマリストアでレート制限チェックを実行
     */
    private function executeWithPrimary(RateLimitKey $key, RateLimitRule $rule): RateLimitResult
    {
        $startTime = microtime(true);

        try {
            $result = $this->primary->checkLimit($key, $rule);
            $this->recordLatency($startTime, 'primary');

            return $result;
        } catch (Throwable $e) {
            // プライマリストア障害 → フェイルオーバー
            $this->isPrimaryAvailable = false;
            $this->failoverAt = Carbon::now(); // フェイルオーバー発生時刻を記録

            $primaryException = $e;

            try {
                $result = $this->executeWithSecondary($key, $rule);
                // セカンダリ成功 → フェイルオーバー成功を記録
                $this->metrics->recordFailure($key, $rule, $primaryException->getMessage(), true);

                return $result;
            } catch (Throwable $secondaryException) {
                // セカンダリも障害 → フェイルオーバー失敗を記録して例外再スロー
                $this->metrics->recordFailure($key, $rule, $secondaryException->getMessage(), false);

                throw $secondaryException;
            }
        }
    }

    /**
     * セカンダリストアでレート制限チェックを実行
     */
    private function executeWithSecondary(RateLimitKey $key, RateLimitRule $rule): RateLimitResult
    {
        $startTime = microtime(true);

        $relaxedRule = $this->relaxRule($rule);
        $result = $this->secondary->checkLimit($key, $relaxedRule);
        $this->recordLatency($startTime, 'secondary');

        return $result;
    }

    /**
     * 制限値を緩和したルールを生成
     */
    private function relaxRule(RateLimitRule $rule): RateLimitRule
    {
        return RateLimitRule::create(
            $rule->getEndpointType(),
            $rule->getMaxAttempts() * self::RATE_LIMIT_RELAXATION_FACTOR,
            $rule->getDecayMinutes()
        );
    }

    /**
     * レイテンシをメトリクスに記録
     */
    private function recordLatency(float $startTime, string $store): void
    {
        $latencyMs = (microtime(true) - $startTime) * 1000;
        $this->metrics->recordLatency($latencyMs, $store);
    }
}
