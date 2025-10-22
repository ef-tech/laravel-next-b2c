<?php

declare(strict_types=1);

namespace Ddd\Application\RateLimit\Contracts;

use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitResult;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;

/**
 * レート制限サービスインターフェース
 *
 * Application層がInfrastructure層のレート制限実装に依存しないための抽象化。
 * 依存性逆転原則（DIP）に基づき、Application層がこのインターフェースに依存し、
 * Infrastructure層が実装を提供する。
 *
 * 実装例:
 * - LaravelRateLimiterStore: Laravel標準RateLimiterを使用
 * - FailoverRateLimitStore: Redis障害時にArray/Fileキャッシュへフェイルオーバー
 * - InMemoryRateLimitStore: テスト用インメモリ実装
 */
interface RateLimitService
{
    /**
     * レート制限チェックを実行
     *
     * 指定されたキーとルールに基づいてレート制限チェックを行い、
     * 許可/拒否の判定結果を返す。
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     * @return RateLimitResult レート制限チェック結果（allowed, attempts, remaining, resetAt）
     */
    public function checkLimit(RateLimitKey $key, RateLimitRule $rule): RateLimitResult;

    /**
     * レート制限カウンターをリセット
     *
     * 指定されたキーのレート制限カウンターを削除する。
     * 主にテストやデバッグ用途で使用。
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     */
    public function resetLimit(RateLimitKey $key): void;

    /**
     * レート制限状態を取得（カウンター増加なし）
     *
     * 現在のレート制限状態を取得するが、カウンターは増加させない。
     * モニタリングやデバッグ用途で使用。
     *
     * @param  RateLimitKey  $key  レート制限識別キー
     * @param  RateLimitRule  $rule  レート制限ルール
     * @return RateLimitResult レート制限状態（allowed, attempts, remaining, resetAt）
     */
    public function getStatus(RateLimitKey $key, RateLimitRule $rule): RateLimitResult;
}
