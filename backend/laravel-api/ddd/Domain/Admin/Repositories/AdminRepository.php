<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\Repositories;

use Ddd\Domain\Admin\Entities\Admin;
use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Domain\Admin\ValueObjects\Email;

interface AdminRepository
{
    /**
     * IDで管理者を検索
     */
    public function findById(AdminId $id): ?Admin;

    /**
     * メールアドレスで管理者を検索
     */
    public function findByEmail(Email $email): ?Admin;

    /**
     * 認証情報を検証し、有効な管理者を返す
     */
    public function verifyCredentials(Email $email, string $password): ?Admin;

    /**
     * 管理者情報を保存
     */
    public function save(Admin $admin): void;
}
