<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\DTOs;

final readonly class LogoutAdminInput
{
    public function __construct(
        public string $tokenId
    ) {}
}
