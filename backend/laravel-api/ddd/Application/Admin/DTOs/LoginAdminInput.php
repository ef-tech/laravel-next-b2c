<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\DTOs;

final readonly class LoginAdminInput
{
    public function __construct(
        public string $email,
        public string $password
    ) {}
}
