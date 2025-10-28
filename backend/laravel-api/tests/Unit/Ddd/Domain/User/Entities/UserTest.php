<?php

declare(strict_types=1);

use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\Events\UserRegistered;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Shared\Exceptions\ValidationException;

test('can register user with ID', function (): void {
    $id = UserId::fromInt(1);
    $email = Email::fromString('test@example.com');
    $name = 'Test User';

    $user = User::register($email, $name, $id);

    expect($user->id()->value())->toBe($id->value())
        ->and($user->email()->value())->toBe($email->value())
        ->and($user->name())->toBe($name);
});

test('can register user without ID (for auto-increment)', function (): void {
    $email = Email::fromString('test@example.com');
    $name = 'Test User';

    $user = User::register($email, $name);

    // ID should not be set yet (will be set after persistence)
    expect(fn () => $user->id())->toThrow(\RuntimeException::class, 'User ID is not set yet (entity not persisted)');
    expect($user->email()->value())->toBe($email->value());
    expect($user->name())->toBe($name);
});

test('setId sets ID after persistence', function (): void {
    $email = Email::fromString('test@example.com');
    $user = User::register($email, 'Test User');

    $id = UserId::fromInt(42);
    $user->setId($id);

    expect($user->id()->value())->toBe(42);
});

test('setId throws exception when ID already set', function (): void {
    $id = UserId::fromInt(1);
    $user = User::register(Email::fromString('test@example.com'), 'Test User', $id);

    $user->setId(UserId::fromInt(2));
})->throws(\RuntimeException::class, 'User ID is already set');

test('setId records UserRegistered event', function (): void {
    $email = Email::fromString('test@example.com');
    $name = 'Test User';
    $user = User::register($email, $name);

    $id = UserId::fromInt(1);
    $user->setId($id);

    $events = $user->pullDomainEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserRegistered::class)
        ->and($events[0]->userId->value())->toBe($id->value())
        ->and($events[0]->email->value())->toBe($email->value())
        ->and($events[0]->name)->toBe($name);
});

test('pullDomainEvents clears internal collection', function (): void {
    $id = UserId::fromInt(1);
    $email = Email::fromString('test@example.com');
    $user = User::register($email, 'Test User', $id);

    $user->pullDomainEvents();
    $events = $user->pullDomainEvents();

    expect($events)->toBeEmpty();
});

test('can change name', function (): void {
    $id = UserId::fromInt(1);
    $email = Email::fromString('test@example.com');
    $user = User::register($email, 'Test User', $id);

    $user->changeName('New Name');

    expect($user->name())->toBe('New Name');
});

test('throws exception when name is too short', function (): void {
    $email = Email::fromString('test@example.com');

    User::register($email, 'A');
})->throws(ValidationException::class, 'Invalid name: Name must be at least 2 characters');

test('throws exception when changing name to too short', function (): void {
    $id = UserId::fromInt(1);
    $email = Email::fromString('test@example.com');
    $user = User::register($email, 'Test User', $id);

    $user->changeName('X');
})->throws(ValidationException::class, 'Invalid name: Name must be at least 2 characters');
