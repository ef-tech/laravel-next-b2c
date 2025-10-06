<?php

declare(strict_types=1);

use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\Events\UserRegistered;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Shared\Exceptions\ValidationException;

test('can register user', function (): void {
    $id = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
    $email = Email::fromString('test@example.com');
    $name = 'Test User';

    $user = User::register($id, $email, $name);

    expect($user->id()->value())->toBe($id->value())
        ->and($user->email()->value())->toBe($email->value())
        ->and($user->name())->toBe($name);
});

test('register records UserRegistered event', function (): void {
    $id = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
    $email = Email::fromString('test@example.com');
    $name = 'Test User';

    $user = User::register($id, $email, $name);
    $events = $user->pullDomainEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(UserRegistered::class)
        ->and($events[0]->userId->value())->toBe($id->value())
        ->and($events[0]->email->value())->toBe($email->value())
        ->and($events[0]->name)->toBe($name);
});

test('pullDomainEvents clears internal collection', function (): void {
    $id = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
    $email = Email::fromString('test@example.com');
    $user = User::register($id, $email, 'Test User');

    $user->pullDomainEvents();
    $events = $user->pullDomainEvents();

    expect($events)->toBeEmpty();
});

test('can change name', function (): void {
    $id = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
    $email = Email::fromString('test@example.com');
    $user = User::register($id, $email, 'Test User');

    $user->changeName('New Name');

    expect($user->name())->toBe('New Name');
});

test('throws exception when name is too short', function (): void {
    $id = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
    $email = Email::fromString('test@example.com');

    User::register($id, $email, 'A');
})->throws(ValidationException::class, 'Invalid name: Name must be at least 2 characters');

test('throws exception when changing name to too short', function (): void {
    $id = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
    $email = Email::fromString('test@example.com');
    $user = User::register($id, $email, 'Test User');

    $user->changeName('X');
})->throws(ValidationException::class, 'Invalid name: Name must be at least 2 characters');
