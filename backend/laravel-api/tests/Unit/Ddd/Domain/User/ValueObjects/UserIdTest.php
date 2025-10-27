<?php

declare(strict_types=1);

use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Shared\Exceptions\ValidationException;

test('can create valid user ID from int', function (): void {
    $userId = UserId::fromInt(1);

    expect($userId->value())->toBe(1);
});

test('can create valid user ID from string', function (): void {
    $userId = UserId::fromString('123');

    expect($userId->value())->toBe(123);
});

test('throws exception for zero', function (): void {
    UserId::fromInt(0);
})->throws(ValidationException::class, 'Invalid user ID');

test('throws exception for negative integer', function (): void {
    UserId::fromInt(-1);
})->throws(ValidationException::class, 'Invalid user ID');

test('throws exception for invalid string', function (): void {
    UserId::fromString('invalid');
})->throws(ValidationException::class, 'Invalid user ID');

test('equals returns true for same user ID', function (): void {
    $userId1 = UserId::fromInt(1);
    $userId2 = UserId::fromInt(1);

    expect($userId1->equals($userId2))->toBeTrue();
});

test('equals returns false for different user ID', function (): void {
    $userId1 = UserId::fromInt(1);
    $userId2 = UserId::fromInt(2);

    expect($userId1->equals($userId2))->toBeFalse();
});
