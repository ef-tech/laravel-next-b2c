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
    $user = User::register(
        id: $userId,
        email: Email::fromString('test@example.com'),
        name: 'Test User'
    );

    $repository->save($user);

    $foundUser = $repository->find($userId);

    expect($foundUser)->not->toBeNull()
        ->and($foundUser->id()->equals($userId))->toBeTrue()
        ->and($foundUser->email()->equals(Email::fromString('test@example.com')))->toBeTrue()
        ->and($foundUser->name())->toBe('Test User');
});

test('returns null when user not found by ID', function (): void {
    $repository = app(UserRepository::class);

    // Use a large ID that doesn't exist in DB
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
    $user = User::register(
        id: $userId,
        email: Email::fromString('delete@example.com'),
        name: 'Delete User'
    );

    $repository->save($user);
    $repository->delete($userId);

    $foundUser = $repository->find($userId);

    expect($foundUser)->toBeNull();
});

test('can update existing user', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $user = User::register(
        id: $userId,
        email: Email::fromString('update@example.com'),
        name: 'Original Name'
    );

    $repository->save($user);

    // Retrieve and update
    $retrievedUser = $repository->find($userId);
    $retrievedUser->changeName('Updated Name');
    $repository->save($retrievedUser);

    // Verify update
    $updatedUser = $repository->find($userId);

    expect($updatedUser->name())->toBe('Updated Name');
});

test('mapper converts eloquent model to domain entity correctly', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $email = Email::fromString('mapper@example.com');
    $user = User::register($userId, $email, 'Mapper Test');

    $repository->save($user);

    $foundUser = $repository->find($userId);

    expect($foundUser)->not->toBeNull()
        ->and($foundUser->id()->value())->toBe($userId->value())
        ->and($foundUser->email()->value())->toBe($email->value())
        ->and($foundUser->name())->toBe('Mapper Test')
        ->and($foundUser->registeredAt())->toBeInstanceOf(\Carbon\Carbon::class);
});
