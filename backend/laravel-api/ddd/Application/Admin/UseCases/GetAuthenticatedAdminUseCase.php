<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\UseCases;

use Ddd\Application\Admin\DTOs\AdminDTO;
use Ddd\Application\Admin\DTOs\GetAuthenticatedAdminInput;
use Ddd\Application\Admin\DTOs\GetAuthenticatedAdminOutput;
use Ddd\Domain\Admin\Exceptions\AdminNotFoundException;
use Ddd\Domain\Admin\Repositories\AdminRepository;
use Ddd\Domain\Admin\ValueObjects\AdminId;

final readonly class GetAuthenticatedAdminUseCase
{
    public function __construct(
        private AdminRepository $adminRepository
    ) {}

    public function execute(GetAuthenticatedAdminInput $input): GetAuthenticatedAdminOutput
    {
        $admin = $this->adminRepository->findById(new AdminId($input->adminId));

        if ($admin === null) {
            throw new AdminNotFoundException;
        }

        // AdminDTO にマッピング（isActive=false でも取得可能）
        $adminDTO = new AdminDTO(
            id: $admin->id->value,
            email: $admin->email->value,
            name: $admin->name,
            role: $admin->role->value,
            isActive: $admin->isActive
        );

        return new GetAuthenticatedAdminOutput($adminDTO);
    }
}
