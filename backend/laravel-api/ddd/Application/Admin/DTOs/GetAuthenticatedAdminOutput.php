<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\DTOs;

final readonly class GetAuthenticatedAdminOutput
{
    public function __construct(
        public AdminDTO $adminDTO
    ) {}
}
