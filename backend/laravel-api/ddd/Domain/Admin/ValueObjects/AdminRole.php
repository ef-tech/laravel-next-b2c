<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\ValueObjects;

use InvalidArgumentException;

final readonly class AdminRole
{
    private const ALLOWED_ROLES = ['admin', 'super_admin'];

    public function __construct(
        public string $value
    ) {
        if (! in_array($value, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException("Invalid admin role: {$value}");
        }
    }

    public function isSuperAdmin(): bool
    {
        return $this->value === 'super_admin';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
