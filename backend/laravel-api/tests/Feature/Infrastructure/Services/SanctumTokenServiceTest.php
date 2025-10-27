<?php

declare(strict_types=1);

use App\Models\Admin as EloquentAdmin;
use Ddd\Domain\Admin\Entities\Admin;
use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Domain\Admin\ValueObjects\AdminRole;
use Ddd\Domain\Admin\ValueObjects\Email;
use Ddd\Infrastructure\Admin\Services\SanctumTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('SanctumTokenService can create token for admin', function () {
    // Arrange
    $eloquentAdmin = EloquentAdmin::create([
        'name' => 'Test Admin',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $admin = new Admin(
        id: AdminId::fromInt($eloquentAdmin->id),
        email: new Email('test@example.com'),
        name: 'Test Admin',
        role: new AdminRole('admin'),
        isActive: true
    );

    $tokenService = new SanctumTokenService;

    // Act
    $token = $tokenService->createToken($admin);

    // Assert
    expect($token)->toBeString()
        ->and($token)->not->toBeEmpty();

    // Verify token was created in database
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_type' => EloquentAdmin::class,
        'tokenable_id' => $eloquentAdmin->id,
        'name' => 'admin-token',
    ]);
});

test('SanctumTokenService can revoke token', function () {
    // Arrange
    $eloquentAdmin = EloquentAdmin::create([
        'name' => 'Test Admin',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Create a token
    $tokenResult = $eloquentAdmin->createToken('admin-token');
    $tokenId = (string) $tokenResult->accessToken->id;

    $tokenService = new SanctumTokenService;

    // Act
    $tokenService->revokeToken($tokenId);

    // Assert: Token should be soft-deleted
    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenId,
        'deleted_at' => null,
    ]);
});
