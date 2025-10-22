<?php

declare(strict_types=1);

namespace Ddd\Application\RateLimit\Services;

use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;

/**
 * レート制限設定管理サービス
 *
 * config/ratelimit.phpからエンドポイント別レート制限ルールを読み込み、
 * RateLimitRule ValueObjectとして提供する。
 * 設定値のキャッシング、環境変数による上書き、型変換をサポート。
 */
final class RateLimitConfigManager
{
    /**
     * ルールキャッシュ
     *
     * @var array<string, RateLimitRule>
     */
    private array $cache = [];

    /**
     * 指定されたエンドポイントタイプのレート制限ルールを取得
     *
     * @param  string  $endpointType  エンドポイントタイプ（public_unauthenticated, protected_unauthenticated, public_authenticated, protected_authenticated）
     */
    public function getRule(string $endpointType): RateLimitRule
    {
        // キャッシュ確認
        if (isset($this->cache[$endpointType])) {
            return $this->cache[$endpointType];
        }

        // 設定読み込み
        $config = config("ratelimit.endpoint_types.{$endpointType}");

        // 設定が存在しない、またはmax_attempts/decay_minutesが未設定の場合はデフォルトルールを返す
        if (! is_array($config) || ! isset($config['max_attempts'], $config['decay_minutes'])) {
            return $this->getDefaultRule();
        }

        // 型変換（環境変数は文字列として読み込まれる可能性がある）
        $maxAttempts = (int) $config['max_attempts'];
        $decayMinutes = (int) $config['decay_minutes'];

        // RateLimitRule ValueObject生成
        $rule = RateLimitRule::create($endpointType, $maxAttempts, $decayMinutes);

        // キャッシュに保存
        $this->cache[$endpointType] = $rule;

        return $rule;
    }

    /**
     * デフォルトルールを取得
     *
     * 最も厳格な制限（30 req/min）を適用
     */
    public function getDefaultRule(): RateLimitRule
    {
        $cacheKey = 'default';

        // キャッシュ確認
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // デフォルト設定読み込み
        $config = config('ratelimit.default', [
            'max_attempts' => 30,
            'decay_minutes' => 1,
        ]);

        // 型変換
        $maxAttempts = (int) ($config['max_attempts'] ?? 30);
        $decayMinutes = (int) ($config['decay_minutes'] ?? 1);

        // RateLimitRule ValueObject生成
        $rule = RateLimitRule::create('default', $maxAttempts, $decayMinutes);

        // キャッシュに保存
        $this->cache[$cacheKey] = $rule;

        return $rule;
    }

    /**
     * 全エンドポイントタイプのレート制限ルールを取得
     *
     * @return array<string, RateLimitRule>
     */
    public function getAllRules(): array
    {
        $endpointTypes = [
            'public_unauthenticated',
            'protected_unauthenticated',
            'public_authenticated',
            'protected_authenticated',
        ];

        $rules = [];
        foreach ($endpointTypes as $type) {
            $rules[$type] = $this->getRule($type);
        }

        return $rules;
    }
}
