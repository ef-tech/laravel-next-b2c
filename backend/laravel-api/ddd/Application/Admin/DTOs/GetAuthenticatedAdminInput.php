<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\DTOs;

final readonly class GetAuthenticatedAdminInput
{
    public function __construct(
        public string $adminId
    ) {}
}
