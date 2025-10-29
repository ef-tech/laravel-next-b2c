<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Http\Presenters\V1;

use Illuminate\Support\Collection;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * V1 トークン Presenter
 *
 * トークン情報をV1形式のレスポンスに変換します。
 */
final class TokenPresenter
{
    /**
     * 新規作成トークンレスポンスを生成
     *
     * @param  NewAccessToken  $newToken  新規作成トークン
     * @return array{token: string, name: string, created_at: string}
     */
    public static function presentNewToken(NewAccessToken $newToken): array
    {
        return [
            'token' => $newToken->plainTextToken,
            'name' => $newToken->accessToken->name,
            'created_at' => $newToken->accessToken->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    /**
     * 既存トークンレスポンスを生成
     *
     * @param  PersonalAccessToken  $token  トークンモデル
     * @return array{id: int, name: string, created_at: string, last_used_at: string|null}
     */
    public static function presentToken(PersonalAccessToken $token): array
    {
        return [
            'id' => $token->id,
            'name' => $token->name,
            'created_at' => $token->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'last_used_at' => $token->last_used_at?->toIso8601String(),
        ];
    }

    /**
     * トークンリストレスポンスを生成
     *
     * @param  Collection<int, PersonalAccessToken>  $tokens  トークンコレクション
     * @return array{tokens: array<int, array{id: int, name: string, created_at: string, last_used_at: string|null}>}
     */
    public static function presentTokenList(Collection $tokens): array
    {
        return [
            'tokens' => $tokens->map(fn (PersonalAccessToken $token) => self::presentToken($token))->toArray(),
        ];
    }
}
