<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\DTOs;

final readonly class LoginAdminOutput
{
    public function __construct(
        public string $token,
        public AdminDTO $adminDTO
    ) {}
}
