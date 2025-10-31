<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Http\Presenters\V1;

use App\Models\User;

/**
 * V1 認証 Presenter
 *
 * 認証関連のレスポンスをV1形式に変換します。
 */
final class AuthPresenter
{
    /**
     * ログイン成功レスポンスを生成
     *
     * @param  User  $user  ユーザーモデル
     * @param  string  $token  プレーンテキストトークン
     * @return array{token: string, user: array{id: int, name: string, email: string}, token_type: string}
     */
    public static function presentLogin(User $user, string $token): array
    {
        return [
            'token' => $token,
            'user' => UserPresenter::present($user),
            'token_type' => 'Bearer',
        ];
    }

    /**
     * ログアウト成功レスポンスを生成
     *
     * @return array{message: string}
     */
    public static function presentLogout(): array
    {
        return [
            'message' => 'Logged out successfully',
        ];
    }

    /**
     * ログインエラーレスポンスを生成
     *
     * @return array{message: string}
     */
    public static function presentLoginError(): array
    {
        return [
            'message' => 'Invalid credentials',
        ];
    }
}
