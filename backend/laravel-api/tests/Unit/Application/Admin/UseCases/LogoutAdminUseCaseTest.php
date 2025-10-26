<?php

declare(strict_types=1);

use Ddd\Application\Admin\DTOs\LogoutAdminInput;
use Ddd\Application\Admin\UseCases\LogoutAdminUseCase;
use Ddd\Domain\Admin\Services\TokenService;

test('LogoutAdminUseCase can logout with valid token', function () {
    // Arrange
    $mockTokenService = Mockery::mock(TokenService::class);
    $mockTokenService->shouldReceive('revokeToken')
        ->once()
        ->with('token-123');

    $useCase = new LogoutAdminUseCase($mockTokenService);
    $input = new LogoutAdminInput('token-123');

    // Act
    $useCase->execute($input);

    // Assert: No exception thrown
    expect(true)->toBeTrue();
});
