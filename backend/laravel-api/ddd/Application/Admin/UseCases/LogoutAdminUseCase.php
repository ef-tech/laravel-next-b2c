<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\UseCases;

use Ddd\Application\Admin\DTOs\LogoutAdminInput;
use Ddd\Domain\Admin\Services\TokenService;

final readonly class LogoutAdminUseCase
{
    public function __construct(
        private TokenService $tokenService
    ) {}

    public function execute(LogoutAdminInput $input): void
    {
        $this->tokenService->revokeToken($input->tokenId);
    }
}
