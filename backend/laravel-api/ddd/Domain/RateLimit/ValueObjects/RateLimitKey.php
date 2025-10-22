<?php

declare(strict_types=1);

namespace Ddd\Domain\RateLimit\ValueObjects;

use InvalidArgumentException;

/**
 * レート制限識別キーValueObject
 *
 * レート制限キー文字列とSHA-256ハッシュ値を提供する不変オブジェクト。
 * プライバシー保護のため、HTTPヘッダーでハッシュ値のみを公開する。
 *
 * @property-read string $key レート制限キー文字列（rate_limit:{endpoint_type}:{identifier}形式）
 */
final readonly class RateLimitKey
{
    /**
     * @param  string  $key  レート制限キー文字列（rate_limit:プレフィックス必須、最大255文字）
     *
     * @throws InvalidArgumentException バリデーションエラー時
     */
    public function __construct(
        public string $key,
    ) {
        $this->validate();
    }

    /**
     * RateLimitKeyファクトリメソッド
     *
     * @param  string  $key  レート制限キー文字列
     *
     * @throws InvalidArgumentException バリデーションエラー時
     */
    public static function create(string $key): self
    {
        return new self($key);
    }

    /**
     * レート制限キー文字列を取得
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * SHA-256ハッシュ化されたキー値を取得
     *
     * プライバシー保護のため、実際のUser IDやIPアドレスを隠蔽する。
     */
    public function getHashedKey(): string
    {
        return hash('sha256', $this->key);
    }

    /**
     * バリデーション実行
     *
     * @throws InvalidArgumentException バリデーションエラー時
     */
    private function validate(): void
    {
        if (! str_starts_with($this->key, 'rate_limit:')) {
            throw new InvalidArgumentException('key must start with rate_limit:');
        }

        if (strlen($this->key) > 255) {
            throw new InvalidArgumentException('key must not exceed 255 characters');
        }

        // rate_limit: のみの場合はエラー
        if ($this->key === 'rate_limit:') {
            throw new InvalidArgumentException('key must have endpoint type and identifier');
        }
    }
}
