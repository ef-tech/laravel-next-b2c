<?php

declare(strict_types=1);

use App\Models\Admin as EloquentAdmin;
use Ddd\Domain\Admin\Entities\Admin;
use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Domain\Admin\ValueObjects\Email;
use Ddd\Infrastructure\Admin\Repositories\EloquentAdminRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('EloquentAdminRepository can find admin by email', function () {
    // Arrange
    EloquentAdmin::create([
        'name' => 'Test Admin',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $repository = new EloquentAdminRepository;

    // Act
    $admin = $repository->findByEmail(new Email('test@example.com'));

    // Assert
    expect($admin)->toBeInstanceOf(Admin::class)
        ->and($admin->email->value)->toBe('test@example.com')
        ->and($admin->name)->toBe('Test Admin')
        ->and($admin->role->value)->toBe('admin')
        ->and($admin->isActive)->toBeTrue();
});

test('EloquentAdminRepository returns null when admin not found by email', function () {
    // Arrange
    $repository = new EloquentAdminRepository;

    // Act
    $admin = $repository->findByEmail(new Email('nonexistent@example.com'));

    // Assert
    expect($admin)->toBeNull();
});

test('EloquentAdminRepository can find admin by id', function () {
    // Arrange
    $eloquentAdmin = EloquentAdmin::create([
        'name' => 'Test Admin',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    $repository = new EloquentAdminRepository;

    // Act
    $admin = $repository->findById(new AdminId((string) $eloquentAdmin->id));

    // Assert
    expect($admin)->toBeInstanceOf(Admin::class)
        ->and($admin->id->value)->toBe((string) $eloquentAdmin->id)
        ->and($admin->email->value)->toBe('test@example.com')
        ->and($admin->role->value)->toBe('super_admin');
});

test('EloquentAdminRepository returns null when admin not found by id', function () {
    // Arrange
    $repository = new EloquentAdminRepository;

    // Act
    $admin = $repository->findById(new AdminId('99999'));

    // Assert
    expect($admin)->toBeNull();
});

test('EloquentAdminRepository can verify valid credentials', function () {
    // Arrange
    EloquentAdmin::create([
        'name' => 'Test Admin',
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $repository = new EloquentAdminRepository;

    // Act
    $admin = $repository->verifyCredentials(new Email('test@example.com'), 'correct-password');

    // Assert
    expect($admin)->toBeInstanceOf(Admin::class)
        ->and($admin->email->value)->toBe('test@example.com');
});

test('EloquentAdminRepository returns null for invalid credentials', function () {
    // Arrange
    EloquentAdmin::create([
        'name' => 'Test Admin',
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $repository = new EloquentAdminRepository;

    // Act
    $admin = $repository->verifyCredentials(new Email('test@example.com'), 'wrong-password');

    // Assert
    expect($admin)->toBeNull();
});

test('EloquentAdminRepository returns null when verifying credentials for non-existent admin', function () {
    // Arrange
    $repository = new EloquentAdminRepository;

    // Act
    $admin = $repository->verifyCredentials(new Email('nonexistent@example.com'), 'password');

    // Assert
    expect($admin)->toBeNull();
});
