<?php

declare(strict_types=1);

namespace Ddd\Application\RateLimit\Services;

use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Illuminate\Http\Request;

/**
 * レート制限識別キー解決サービス
 *
 * エンドポイントタイプに応じて最適な識別子を選択し、
 * RateLimitKey ValueObjectを生成する。
 *
 * 識別子選択戦略:
 * - public_unauthenticated: IPアドレス
 * - protected_unauthenticated: IP + Email (SHA-256ハッシュ化)
 * - public_authenticated: User ID → Token ID → IP (フォールバック)
 * - protected_authenticated: User ID → Token ID → IP (フォールバック)
 * - default: IPアドレス
 */
final class KeyResolver
{
    /**
     * リクエストとルールから適切なレート制限キーを解決
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  RateLimitRule  $rule  レート制限ルール
     */
    public function resolve(Request $request, RateLimitRule $rule): RateLimitKey
    {
        $identifier = match ($rule->getEndpointType()) {
            'public_unauthenticated' => $this->getIpAddress($request),
            'protected_unauthenticated' => $this->getIpAndEmail($request),
            'public_authenticated', 'protected_authenticated' => $this->getUserIdentifier($request),
            default => $this->getIpAddress($request),
        };

        $keyString = "rate_limit:{$rule->getEndpointType()}:{$identifier}";

        return RateLimitKey::create($keyString);
    }

    /**
     * IPアドレスベースの識別子を取得
     *
     * @param  Request  $request  HTTPリクエスト
     */
    private function getIpAddress(Request $request): string
    {
        return 'ip_'.$request->ip();
    }

    /**
     * IP + Emailベースの識別子を取得
     *
     * Emailアドレスは必ずSHA-256ハッシュ化してプライバシーを保護。
     * ログインエンドポイント等のブルートフォース攻撃対策に使用。
     *
     * @param  Request  $request  HTTPリクエスト
     */
    private function getIpAndEmail(Request $request): string
    {
        $ip = $request->ip();
        $email = $request->input('email', 'unknown');
        $emailHash = hash('sha256', $email);

        return "ip_{$ip}_email_{$emailHash}";
    }

    /**
     * User IDベースの識別子を取得（フォールバックチェーン付き）
     *
     * 優先順位:
     * 1. User ID (認証済みユーザー)
     * 2. Token ID (Personal Access Token)
     * 3. IPアドレス (フォールバック)
     *
     * @param  Request  $request  HTTPリクエスト
     */
    private function getUserIdentifier(Request $request): string
    {
        $user = $request->user();

        // User ID 優先
        // @phpstan-ignore-next-line (PHPDoc type narrowing)
        if ($user !== null && property_exists($user, 'id') && $user->id !== null) {
            return "user_{$user->id}";
        }

        // Token ID フォールバック
        // @phpstan-ignore-next-line (PHPDoc type narrowing)
        if ($user !== null && method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();
            // @phpstan-ignore-next-line (PHPDoc type narrowing)
            if ($token !== null && isset($token->id)) {
                return "token_{$token->id}";
            }
        }

        // IPアドレス フォールバック
        return 'ip_'.$request->ip();
    }
}
