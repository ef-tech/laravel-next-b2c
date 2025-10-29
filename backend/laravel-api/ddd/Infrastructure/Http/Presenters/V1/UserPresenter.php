<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Http\Presenters\V1;

use App\Models\User;

/**
 * V1 ユーザー Presenter
 *
 * UserモデルをV1形式のレスポンスに変換します。
 */
final class UserPresenter
{
    /**
     * Userレスポンスを生成
     *
     * @param  User  $user  ユーザーモデル
     * @return array{id: int, name: string, email: string}
     */
    public static function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
