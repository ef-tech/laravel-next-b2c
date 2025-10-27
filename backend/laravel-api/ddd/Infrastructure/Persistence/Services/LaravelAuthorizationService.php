<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Persistence\Services;

use App\Models\Admin;
use App\Models\User;
use Ddd\Application\Shared\Services\Authorization\AuthorizationService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Laravel Authorization Service Implementation
 *
 * AuthorizationServiceポートの具象実装。
 * 簡易的な権限チェックロジックを実装します。
 *
 * Requirements: 5.2, 6.3, 15.2
 */
final readonly class LaravelAuthorizationService implements AuthorizationService
{
    /**
     * ユーザーが指定された権限を持つかを判定する
     *
     * 簡易実装: モデル型とemailドメインで権限を判定
     * - Admin型: admin権限あり
     * - User型（admin@example.com）: admin権限あり
     * - その他: user権限のみ
     *
     * 将来的には、rolesテーブルやpermissionsテーブルを使用した
     * より詳細な権限管理に置き換えることができます。
     *
     * @param  Authenticatable  $user  認証済みユーザー（User または Admin）
     * @param  string  $permission  要求される権限
     * @return bool 権限がある場合はtrue
     */
    public function authorize(Authenticatable $user, string $permission): bool
    {
        return match ($permission) {
            'admin' => $user instanceof Admin || ($user instanceof User && str_starts_with($user->email, 'admin@')),
            'user' => true, // 全認証ユーザーはuser権限を持つ
            default => false,
        };
    }
}
