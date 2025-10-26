<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\Services;

use Ddd\Domain\Admin\Entities\Admin;

interface TokenService
{
    /**
     * 管理者用トークンを生成
     */
    public function createToken(Admin $admin): string;

    /**
     * トークンを失効
     */
    public function revokeToken(string $tokenId): void;
}
