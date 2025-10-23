<?php

declare(strict_types=1);

namespace Ddd\Domain\RateLimit\ValueObjects;

use Carbon\Carbon;

/**
 * レート制限チェック結果ValueObject
 *
 * レート制限チェックの結果（許可/拒否、試行回数、残り回数、リセット時刻）を保持する不変オブジェクト。
 *
 * @property-read bool $allowed 許可フラグ
 * @property-read int $attempts 試行回数
 * @property-read int $remaining 残り回数
 * @property-read Carbon $resetAt リセット時刻
 */
final readonly class RateLimitResult
{
    /**
     * @param  bool  $allowed  許可フラグ
     * @param  int  $attempts  試行回数
     * @param  int  $remaining  残り回数
     * @param  Carbon  $resetAt  リセット時刻
     */
    public function __construct(
        public bool $allowed,
        public int $attempts,
        public int $remaining,
        public Carbon $resetAt,
    ) {}

    /**
     * 許可状態のRateLimitResultを生成
     *
     * @param  int  $attempts  試行回数
     * @param  int  $remaining  残り回数
     * @param  Carbon  $resetAt  リセット時刻
     */
    public static function allowed(int $attempts, int $remaining, Carbon $resetAt): self
    {
        return new self(true, $attempts, $remaining, $resetAt);
    }

    /**
     * 拒否状態のRateLimitResultを生成
     *
     * @param  int  $attempts  試行回数
     * @param  Carbon  $resetAt  リセット時刻
     */
    public static function blocked(int $attempts, Carbon $resetAt): self
    {
        return new self(false, $attempts, 0, $resetAt);
    }

    /**
     * 許可状態かどうかを判定
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * 拒否状態かどうかを判定
     */
    public function isBlocked(): bool
    {
        return ! $this->allowed;
    }

    /**
     * 試行回数を取得
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * 残り回数を取得
     */
    public function getRemaining(): int
    {
        return $this->remaining;
    }

    /**
     * リセット時刻を取得
     */
    public function getResetAt(): Carbon
    {
        return $this->resetAt;
    }

    /**
     * リセット時刻をUNIXタイムスタンプ形式で取得
     */
    public function getResetTimestamp(): int
    {
        return (int) $this->resetAt->timestamp;
    }
}
