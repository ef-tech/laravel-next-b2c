<?php

declare(strict_types=1);

namespace Ddd\Domain\RateLimit\ValueObjects;

/**
 * エンドポイント分類 Value Object
 *
 * HTTPリクエストを認証状態と機密性で分類した結果を保持する不変オブジェクト。
 * レート制限ポリシー識別子と適用されるルールをカプセル化する。
 *
 * 分類タイプ:
 * - public_unauthenticated: 未認証 + 公開エンドポイント
 * - protected_unauthenticated: 未認証 + 保護エンドポイント
 * - public_authenticated: 認証済み + 公開エンドポイント
 * - protected_authenticated: 認証済み + 保護エンドポイント
 */
final readonly class EndpointClassification
{
    /**
     * @param  string  $type  分類タイプ（public_unauthenticated等）
     * @param  RateLimitRule  $rule  適用されるレート制限ルール
     */
    public function __construct(
        private string $type,
        private RateLimitRule $rule,
    ) {}

    /**
     * エンドポイント分類を作成
     */
    public static function create(string $type, RateLimitRule $rule): self
    {
        return new self($type, $rule);
    }

    /**
     * 分類タイプを取得（X-RateLimit-Policyヘッダー用）
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * レート制限ルールを取得
     */
    public function getRule(): RateLimitRule
    {
        return $this->rule;
    }
}
