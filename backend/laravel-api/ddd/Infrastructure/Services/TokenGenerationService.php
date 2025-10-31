<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Services;

use App\Models\User as EloquentUser;
use Ddd\Domain\User\ValueObjects\UserId;

/**
 * トークン生成サービス
 *
 * Laravel Sanctumを使用したトークン生成を担当します。
 * DDD アーキテクチャにおいて、ControllerがModelを直接使用することを避けるため、
 * Infrastructure層でトークン生成ロジックをカプセル化します。
 */
final class TokenGenerationService
{
    /**
     * ユーザーIDからAPIトークンを生成
     *
     * @param  UserId  $userId  ドメインユーザーID
     * @param  string  $tokenName  トークン名（デフォルト: "API Token"）
     * @return array{token: string, user: EloquentUser} トークンとユーザーモデル
     *
     * @throws \RuntimeException ユーザーが見つからない場合
     */
    public function generateToken(UserId $userId, string $tokenName = 'API Token'): array
    {
        // EloquentモデルをDBから取得
        // Note: Sanctumのトークン生成にはEloquentモデルが必要なため、
        // この層でモデルを扱うことが正当化されます
        $eloquentUser = EloquentUser::find($userId->value());

        if (! $eloquentUser) {
            throw new \RuntimeException("User not found: {$userId->value()}");
        }

        $token = $eloquentUser->createToken($tokenName)->plainTextToken;

        return [
            'token' => $token,
            'user' => $eloquentUser,
        ];
    }

    /**
     * 現在のトークンを削除（ログアウト）
     *
     * @param  UserId  $userId  ドメインユーザーID
     * @param  string  $tokenId  削除するトークンID
     *
     * @throws \RuntimeException ユーザーが見つからない場合
     */
    public function revokeToken(UserId $userId, string $tokenId): void
    {
        $eloquentUser = EloquentUser::find($userId->value());

        if (! $eloquentUser) {
            throw new \RuntimeException("User not found: {$userId->value()}");
        }

        $eloquentUser->tokens()->where('id', $tokenId)->delete();
    }

    /**
     * 全トークンを削除
     *
     * @param  UserId  $userId  ドメインユーザーID
     *
     * @throws \RuntimeException ユーザーが見つからない場合
     */
    public function revokeAllTokens(UserId $userId): void
    {
        $eloquentUser = EloquentUser::find($userId->value());

        if (! $eloquentUser) {
            throw new \RuntimeException("User not found: {$userId->value()}");
        }

        $eloquentUser->tokens()->delete();
    }
}
