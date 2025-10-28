<?php

declare(strict_types=1);

use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\Repositories\UserRepository;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can generate next user ID', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();

    expect($userId)->toBeInstanceOf(UserId::class);
});

test('can save and find user by ID', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $email = Email::fromString('test@example.com');
    $user = User::register(
        id: $userId,
        email: $email,
        name: 'Test User'
    );

    $repository->save($user);

    // For auto-increment IDs, find by email first to get the actual ID
    $foundUser = $repository->findByEmail($email);

    expect($foundUser)->not->toBeNull()
        ->and($foundUser->id()->value())->toBeInt()
        ->and($foundUser->id()->value())->toBeGreaterThan(0)
        ->and($foundUser->email()->equals($email))->toBeTrue()
        ->and($foundUser->name())->toBe('Test User');

    // Now test find by actual ID
    $actualId = $foundUser->id();
    $foundById = $repository->find($actualId);

    expect($foundById)->not->toBeNull()
        ->and($foundById->id()->equals($actualId))->toBeTrue();
});

test('returns null when user not found by ID', function (): void {
    $repository = app(UserRepository::class);

    // Use a large integer ID that doesn't exist in DB
    $nonExistentId = UserId::fromInt(999999);

    $user = $repository->find($nonExistentId);

    expect($user)->toBeNull();
});

test('can find user by email', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $email = Email::fromString('find@example.com');
    $user = User::register($userId, $email, 'Find User');

    $repository->save($user);

    $foundUser = $repository->findByEmail($email);

    expect($foundUser)->not->toBeNull()
        ->and($foundUser->email()->equals($email))->toBeTrue();
});

test('returns null when user not found by email', function (): void {
    $repository = app(UserRepository::class);

    $email = Email::fromString('nonexistent@example.com');

    $user = $repository->findByEmail($email);

    expect($user)->toBeNull();
});

test('can check if email exists', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $email = Email::fromString('exists@example.com');
    $user = User::register($userId, $email, 'Exists User');

    $repository->save($user);

    expect($repository->existsByEmail($email))->toBeTrue()
        ->and($repository->existsByEmail(Email::fromString('not-exists@example.com')))->toBeFalse();
});

test('can delete user', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $email = Email::fromString('delete@example.com');
    $user = User::register(
        id: $userId,
        email: $email,
        name: 'Delete User'
    );

    $repository->save($user);

    // Get actual ID from saved user
    $savedUser = $repository->findByEmail($email);
    $actualId = $savedUser->id();

    $repository->delete($actualId);

    $foundUser = $repository->find($actualId);

    expect($foundUser)->toBeNull();
});

test('can update existing user', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $email = Email::fromString('update@example.com');
    $user = User::register(
        id: $userId,
        email: $email,
        name: 'Original Name'
    );

    $repository->save($user);

    // Retrieve and update using email
    $retrievedUser = $repository->findByEmail($email);
    $actualId = $retrievedUser->id();
    $retrievedUser->changeName('Updated Name');
    $repository->save($retrievedUser);

    // Verify update
    $updatedUser = $repository->find($actualId);

    expect($updatedUser->name())->toBe('Updated Name');
});

test('mapper converts eloquent model to domain entity correctly', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $email = Email::fromString('mapper@example.com');
    $user = User::register($userId, $email, 'Mapper Test');

    $repository->save($user);

    $foundUser = $repository->findByEmail($email);

    expect($foundUser)->not->toBeNull()
        ->and($foundUser->id()->value())->toBeInt()
        ->and($foundUser->id()->value())->toBeGreaterThan(0)
        ->and($foundUser->email()->value())->toBe($email->value())
        ->and($foundUser->name())->toBe('Mapper Test')
        ->and($foundUser->registeredAt())->toBeInstanceOf(\Carbon\Carbon::class);
});
