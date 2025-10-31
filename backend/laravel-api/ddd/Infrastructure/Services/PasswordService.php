<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Services;

use App\Models\User as EloquentUser;
use Ddd\Domain\User\ValueObjects\UserId;
use Illuminate\Support\Facades\Hash;

/**
 * パスワード管理サービス
 *
 * ユーザーのパスワードをハッシュ化して保存します。
 * Note: パスワードはまだDomainモデルに含まれていないため、
 * Infrastructure層でEloquentモデルに直接保存します。
 */
final class PasswordService
{
    /**
     * ユーザーのパスワードを設定
     *
     * @param  UserId  $userId  ユーザーID
     * @param  string  $plainPassword  平文パスワード
     */
    public function setPassword(UserId $userId, string $plainPassword): void
    {
        $user = EloquentUser::find($userId->value());

        if ($user === null) {
            throw new \RuntimeException("User not found: {$userId->value()}");
        }

        $user->password = Hash::make($plainPassword);
        $user->save();
    }
}
