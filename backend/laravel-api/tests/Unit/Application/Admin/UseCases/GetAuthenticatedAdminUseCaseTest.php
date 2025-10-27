<?php

declare(strict_types=1);

use Ddd\Application\Admin\DTOs\AdminDTO;
use Ddd\Application\Admin\DTOs\GetAuthenticatedAdminInput;
use Ddd\Application\Admin\DTOs\GetAuthenticatedAdminOutput;
use Ddd\Application\Admin\UseCases\GetAuthenticatedAdminUseCase;
use Ddd\Domain\Admin\Entities\Admin;
use Ddd\Domain\Admin\Exceptions\AdminNotFoundException;
use Ddd\Domain\Admin\Repositories\AdminRepository;
use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Domain\Admin\ValueObjects\AdminRole;
use Ddd\Domain\Admin\ValueObjects\Email;

test('GetAuthenticatedAdminUseCase can get authenticated admin', function () {
    // Arrange
    $mockRepository = Mockery::mock(AdminRepository::class);
    $admin = new Admin(
        id: AdminId::fromInt(123),
        email: new Email('admin@example.com'),
        name: 'Admin User',
        role: new AdminRole('super_admin'),
        isActive: true
    );

    $mockRepository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn ($id) => $id->value() === 123))
        ->andReturn($admin);

    $useCase = new GetAuthenticatedAdminUseCase($mockRepository);
    $input = new GetAuthenticatedAdminInput('123');

    // Act
    $output = $useCase->execute($input);

    // Assert
    expect($output)->toBeInstanceOf(GetAuthenticatedAdminOutput::class)
        ->and($output->adminDTO)->toBeInstanceOf(AdminDTO::class)
        ->and($output->adminDTO->id)->toBe(123)
        ->and($output->adminDTO->email)->toBe('admin@example.com')
        ->and($output->adminDTO->name)->toBe('Admin User')
        ->and($output->adminDTO->role)->toBe('super_admin')
        ->and($output->adminDTO->isActive)->toBeTrue();
});

test('GetAuthenticatedAdminUseCase can get disabled admin', function () {
    // Arrange: 認証済みなので isActive=false でも取得可能
    $mockRepository = Mockery::mock(AdminRepository::class);
    $admin = new Admin(
        id: AdminId::fromInt(123),
        email: new Email('admin@example.com'),
        name: 'Disabled Admin',
        role: new AdminRole('admin'),
        isActive: false
    );

    $mockRepository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn ($id) => $id->value() === 123))
        ->andReturn($admin);

    $useCase = new GetAuthenticatedAdminUseCase($mockRepository);
    $input = new GetAuthenticatedAdminInput('123');

    // Act
    $output = $useCase->execute($input);

    // Assert
    expect($output)->toBeInstanceOf(GetAuthenticatedAdminOutput::class)
        ->and($output->adminDTO->isActive)->toBeFalse();
});

test('GetAuthenticatedAdminUseCase throws AdminNotFoundException when admin not found', function () {
    // Arrange
    $mockRepository = Mockery::mock(AdminRepository::class);
    $mockRepository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn ($id) => $id->value() === 999))
        ->andReturn(null);

    $useCase = new GetAuthenticatedAdminUseCase($mockRepository);
    $input = new GetAuthenticatedAdminInput('999');

    // Act & Assert
    $useCase->execute($input);
})->throws(AdminNotFoundException::class);
