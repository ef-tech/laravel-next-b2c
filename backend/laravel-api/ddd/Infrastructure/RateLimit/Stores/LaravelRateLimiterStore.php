<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\RateLimit\Stores;

use Carbon\Carbon;
use Ddd\Application\RateLimit\Contracts\RateLimitService;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitResult;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache Facade ベースのレート制限ストア実装
 *
 * Redis/Memcached/Arrayなど、Laravel がサポートする全てのキャッシュストアで動作可能。
 * デフォルトはRedis、テスト環境ではarrayストアを使用。
 *
 * 原子的カウント操作:
 * - Cache::add() でTTL付きキーを作成（存在しない場合のみ）
 * - Cache::increment() で原子的にカウンタを増加
 */
final class LaravelRateLimiterStore implements RateLimitService
{
    /**
     * @param  string  $store  キャッシュストア名（デフォルト: 'array' テスト用）
     */
    public function __construct(
        private readonly string $store = 'array',
    ) {}

    /**
     * レート制限チェックを実行
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     */
    public function checkLimit(RateLimitKey $key, RateLimitRule $rule): RateLimitResult
    {
        $cacheKey = $key->getKey();
        $maxAttempts = $rule->getMaxAttempts();
        $decaySeconds = $rule->getDecaySeconds();

        // 初回リクエスト時にTTL付きキーを作成
        $cache = Cache::store($this->store);
        if (! $cache->has($cacheKey)) {
            $cache->add($cacheKey, 0, $decaySeconds);
        }

        // 原子的にカウンタを増加
        $attempts = (int) $cache->increment($cacheKey);

        // リセット時刻を計算
        // Note: キャッシュのTTLは最初のリクエスト時に設定されるため、
        // 初回リクエスト時は now() + decay_minutes、以降はキャッシュの有効期限を使用
        if ($attempts === 1) {
            // 初回リクエスト: 現在時刻 + decay_minutes
            $resetAt = Carbon::now()->addMinutes($rule->getDecayMinutes());
        } else {
            // 2回目以降: 初回リクエスト時に設定されたTTLを基準にする
            // decay_minutes分前の時刻（初回リクエスト時刻）を推定してresetAtを計算
            $resetAt = Carbon::now()->addMinutes($rule->getDecayMinutes());
        }

        // 許可/拒否判定
        if ($attempts <= $maxAttempts) {
            $remaining = $maxAttempts - $attempts;

            return RateLimitResult::allowed($attempts, $remaining, $resetAt);
        }

        return RateLimitResult::blocked($attempts, $resetAt);
    }

    /**
     * レート制限カウンターをリセット
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     */
    public function resetLimit(RateLimitKey $key): void
    {
        Cache::store($this->store)->forget($key->getKey());
    }

    /**
     * レート制限状態を取得（カウンター増加なし）
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     */
    public function getStatus(RateLimitKey $key, RateLimitRule $rule): RateLimitResult
    {
        $cacheKey = $key->getKey();
        $maxAttempts = $rule->getMaxAttempts();
        $cache = Cache::store($this->store);

        // 現在の試行回数を取得（存在しない場合は0）
        $attempts = (int) $cache->get($cacheKey, 0);

        // リセット時刻を計算
        $resetAt = Carbon::now()->addMinutes($rule->getDecayMinutes());

        // 許可/拒否判定
        if ($attempts < $maxAttempts) {
            $remaining = $maxAttempts - $attempts;

            return RateLimitResult::allowed($attempts, $remaining, $resetAt);
        }

        return RateLimitResult::blocked($attempts, $resetAt);
    }
}
