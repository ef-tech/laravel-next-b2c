<?php

declare(strict_types=1);

namespace Ddd\Application\RateLimit\Services;

use Ddd\Domain\RateLimit\ValueObjects\EndpointClassification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * エンドポイント分類サービス
 *
 * HTTPリクエストを認証状態（未認証/認証済み）と機密性（公開/保護）の2軸で
 * 4種類に分類し、適切なレート制限ルールを返す。
 *
 * - public_unauthenticated: 未認証 + 公開エンドポイント (60 req/min, IP)
 * - protected_unauthenticated: 未認証 + 保護エンドポイント (5 req/10min, IP+Email)
 * - public_authenticated: 認証済み + 公開エンドポイント (120 req/min, User ID)
 * - protected_authenticated: 認証済み + 保護エンドポイント (30 req/min, User ID)
 */
final class EndpointClassifier
{
    /**
     * @param  RateLimitConfigManager  $configManager  設定管理サービス
     */
    public function __construct(
        private readonly RateLimitConfigManager $configManager,
    ) {}

    /**
     * リクエストを分類し、適切なレート制限ルールを返す
     *
     * Phase 4拡張: EndpointClassification Value Objectを返すように変更
     *
     * @param  Request  $request  HTTPリクエスト
     */
    public function classify(Request $request): EndpointClassification
    {
        $isAuthenticated = $request->user() !== null;
        $isProtected = $this->isProtectedEndpoint($request);

        $type = match (true) {
            ! $isAuthenticated && ! $isProtected => 'public_unauthenticated',
            ! $isAuthenticated && $isProtected => 'protected_unauthenticated',
            $isAuthenticated && ! $isProtected => 'public_authenticated',
            $isAuthenticated && $isProtected => 'protected_authenticated',
            default => throw new \LogicException('Unreachable: All boolean combinations are handled'),
        };

        $rule = $this->configManager->getRule($type);

        return EndpointClassification::create($type, $rule);
    }

    /**
     * 保護エンドポイントかどうかを判定
     *
     * config/ratelimit.phpのprotected_routes配列に定義されたパターンと
     * ルート名をマッチングして判定する。
     *
     * @param  Request  $request  HTTPリクエスト
     */
    private function isProtectedEndpoint(Request $request): bool
    {
        $protectedRoutes = config('ratelimit.protected_routes', [
            'login',
            'register',
            'password.*',
            'admin.*',
            'payment.*',
        ]);

        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            return false;
        }

        foreach ($protectedRoutes as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}
