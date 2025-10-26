<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\Entities;

use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Domain\Admin\ValueObjects\AdminRole;
use Ddd\Domain\Admin\ValueObjects\Email;

final readonly class Admin
{
    public function __construct(
        public AdminId $id,
        public Email $email,
        public string $name,
        public AdminRole $role,
        public bool $isActive
    ) {}

    public function canAccessAdminPanel(): bool
    {
        return $this->isActive;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
