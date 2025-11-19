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
     * @return array{id: int, name: string, email: string, created_at: string, updated_at: string}
     */
    public static function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->utc()->toIso8601String() ?? now()->utc()->toIso8601String(),
            'updated_at' => $user->updated_at?->utc()->toIso8601String() ?? now()->utc()->toIso8601String(),
        ];
    }
}
