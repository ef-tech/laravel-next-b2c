<?php

declare(strict_types=1);

namespace Ddd\Application\Admin\UseCases;

use Ddd\Application\Admin\DTOs\AdminDTO;
use Ddd\Application\Admin\DTOs\LoginAdminInput;
use Ddd\Application\Admin\DTOs\LoginAdminOutput;
use Ddd\Domain\Admin\Exceptions\AccountDisabledException;
use Ddd\Domain\Admin\Exceptions\InvalidCredentialsException;
use Ddd\Domain\Admin\Repositories\AdminRepository;
use Ddd\Domain\Admin\Services\TokenService;
use Ddd\Domain\Admin\ValueObjects\Email;

final readonly class LoginAdminUseCase
{
    public function __construct(
        private AdminRepository $adminRepository,
        private TokenService $tokenService
    ) {}

    public function execute(LoginAdminInput $input): LoginAdminOutput
    {
        // 認証情報を検証
        $admin = $this->adminRepository->verifyCredentials(
            new Email($input->email),
            $input->password
        );

        if ($admin === null) {
            throw new InvalidCredentialsException;
        }

        // アカウントが有効かチェック
        if (! $admin->isActive) {
            throw new AccountDisabledException;
        }

        // トークン生成
        $token = $this->tokenService->createToken($admin);

        // AdminDTO にマッピング
        $adminDTO = new AdminDTO(
            id: $admin->id->value,
            email: $admin->email->value,
            name: $admin->name,
            role: $admin->role->value,
            isActive: $admin->isActive
        );

        return new LoginAdminOutput($token, $adminDTO);
    }
}
