<?php

declare(strict_types=1);

namespace Ddd\Domain\RateLimit\ValueObjects;

use InvalidArgumentException;

/**
 * レート制限ルールValueObject
 *
 * エンドポイント分類、最大試行回数、制限時間（分単位）をカプセル化する不変オブジェクト。
 *
 * @property-read string $endpointType エンドポイントタイプ（public_unauthenticated, protected_unauthenticated, etc.）
 * @property-read int $maxAttempts 最大試行回数（1-10000の範囲）
 * @property-read int $decayMinutes 制限時間（1-60分の範囲）
 */
final readonly class RateLimitRule
{
    /**
     * @param  string  $endpointType  エンドポイントタイプ（非空文字列）
     * @param  int  $maxAttempts  最大試行回数（1-10000）
     * @param  int  $decayMinutes  制限時間（1-60分）
     *
     * @throws InvalidArgumentException バリデーションエラー時
     */
    public function __construct(
        public string $endpointType,
        public int $maxAttempts,
        public int $decayMinutes,
    ) {
        $this->validate();
    }

    /**
     * RateLimitRuleファクトリメソッド
     *
     * @param  string  $endpointType  エンドポイントタイプ
     * @param  int  $maxAttempts  最大試行回数
     * @param  int  $decayMinutes  制限時間（分）
     *
     * @throws InvalidArgumentException バリデーションエラー時
     */
    public static function create(string $endpointType, int $maxAttempts, int $decayMinutes): self
    {
        return new self($endpointType, $maxAttempts, $decayMinutes);
    }

    /**
     * エンドポイントタイプを取得
     */
    public function getEndpointType(): string
    {
        return $this->endpointType;
    }

    /**
     * 最大試行回数を取得
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * 制限時間（分単位）を取得
     */
    public function getDecayMinutes(): int
    {
        return $this->decayMinutes;
    }

    /**
     * 制限時間（秒単位）を取得
     */
    public function getDecaySeconds(): int
    {
        return $this->decayMinutes * 60;
    }

    /**
     * バリデーション実行
     *
     * @throws InvalidArgumentException バリデーションエラー時
     */
    private function validate(): void
    {
        if ($this->endpointType === '') {
            throw new InvalidArgumentException('endpointType cannot be empty');
        }

        if ($this->maxAttempts < 1 || $this->maxAttempts > 10000) {
            throw new InvalidArgumentException('maxAttempts must be between 1 and 10000');
        }

        if ($this->decayMinutes < 1 || $this->decayMinutes > 60) {
            throw new InvalidArgumentException('decayMinutes must be between 1 and 60');
        }
    }
}
