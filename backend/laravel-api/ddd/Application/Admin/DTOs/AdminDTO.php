<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\DTOs;

final readonly class AdminDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $role,
        public bool $isActive
    ) {}
}
