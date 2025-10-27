<?php

declare(strict_types=1);

namespace Ddd\Application\Shared\Services\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization Service Port
 *
 * DDD/クリーンアーキテクチャに準拠した認可サービスのインターフェース。
 * HTTP層のミドルウェアからDIされ、ユーザーの権限を検証します。
 *
 * Requirements: 5.2, 5.3, 5.7, 15.3
 *
 * @psalm-api
 */
interface AuthorizationService
{
    /**
     * ユーザーが指定された権限を持つかを判定する
     *
     * @param  Authenticatable  $user  認証済みユーザー（User または Admin）
     * @param  string  $permission  要求される権限（例: 'admin', 'user.edit', 'post.delete'）
     * @return bool 権限がある場合はtrue、ない場合はfalse
     */
    public function authorize(Authenticatable $user, string $permission): bool;
}
