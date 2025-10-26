<?php

declare(strict_types=1);

use Ddd\Application\Admin\DTOs\AdminDTO;
use Ddd\Application\Admin\DTOs\LoginAdminInput;
use Ddd\Application\Admin\DTOs\LoginAdminOutput;
use Ddd\Application\Admin\UseCases\LoginAdminUseCase;
use Ddd\Domain\Admin\Entities\Admin;
use Ddd\Domain\Admin\Exceptions\AccountDisabledException;
use Ddd\Domain\Admin\Exceptions\InvalidCredentialsException;
use Ddd\Domain\Admin\Repositories\AdminRepository;
use Ddd\Domain\Admin\Services\TokenService;
use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Domain\Admin\ValueObjects\AdminRole;
use Ddd\Domain\Admin\ValueObjects\Email;

test('LoginAdminUseCase can login with valid credentials', function () {
    // Arrange
    $mockRepository = Mockery::mock(AdminRepository::class);
    $mockTokenService = Mockery::mock(TokenService::class);

    $admin = new Admin(
        id: new AdminId('123'),
        email: new Email('admin@example.com'),
        name: 'Admin User',
        role: new AdminRole('super_admin'),
        isActive: true
    );

    $mockRepository->shouldReceive('verifyCredentials')
        ->once()
        ->with(Mockery::on(fn ($email) => $email->value === 'admin@example.com'), 'password')
        ->andReturn($admin);

    $mockTokenService->shouldReceive('createToken')
        ->once()
        ->with($admin)
        ->andReturn('test-token-12345');

    $useCase = new LoginAdminUseCase($mockRepository, $mockTokenService);
    $input = new LoginAdminInput('admin@example.com', 'password');

    // Act
    $output = $useCase->execute($input);

    // Assert
    expect($output)->toBeInstanceOf(LoginAdminOutput::class)
        ->and($output->token)->toBe('test-token-12345')
        ->and($output->adminDTO)->toBeInstanceOf(AdminDTO::class)
        ->and($output->adminDTO->id)->toBe('123')
        ->and($output->adminDTO->email)->toBe('admin@example.com')
        ->and($output->adminDTO->name)->toBe('Admin User')
        ->and($output->adminDTO->role)->toBe('super_admin')
        ->and($output->adminDTO->isActive)->toBeTrue();
});

test('LoginAdminUseCase throws InvalidCredentialsException for invalid credentials', function () {
    // Arrange
    $mockRepository = Mockery::mock(AdminRepository::class);
    $mockTokenService = Mockery::mock(TokenService::class);

    $mockRepository->shouldReceive('verifyCredentials')
        ->once()
        ->with(Mockery::on(fn ($email) => $email->value === 'admin@example.com'), 'wrong-password')
        ->andReturn(null);

    $useCase = new LoginAdminUseCase($mockRepository, $mockTokenService);
    $input = new LoginAdminInput('admin@example.com', 'wrong-password');

    // Act & Assert
    $useCase->execute($input);
})->throws(InvalidCredentialsException::class);

test('LoginAdminUseCase throws AccountDisabledException for disabled account', function () {
    // Arrange
    $mockRepository = Mockery::mock(AdminRepository::class);
    $mockTokenService = Mockery::mock(TokenService::class);

    $admin = new Admin(
        id: new AdminId('123'),
        email: new Email('admin@example.com'),
        name: 'Admin User',
        role: new AdminRole('admin'),
        isActive: false
    );

    $mockRepository->shouldReceive('verifyCredentials')
        ->once()
        ->with(Mockery::on(fn ($email) => $email->value === 'admin@example.com'), 'password')
        ->andReturn($admin);

    $useCase = new LoginAdminUseCase($mockRepository, $mockTokenService);
    $input = new LoginAdminInput('admin@example.com', 'password');

    // Act & Assert
    $useCase->execute($input);
})->throws(AccountDisabledException::class);
