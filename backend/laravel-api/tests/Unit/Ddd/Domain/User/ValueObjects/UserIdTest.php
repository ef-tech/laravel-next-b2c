<?php

declare(strict_types=1);

use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Shared\Exceptions\ValidationException;

test('can create valid integer ID', function (): void {
    $userId = UserId::fromInt(1);

    expect($userId->value())->toBe(1);
});

test('throws exception for invalid integer ID (zero)', function (): void {
    UserId::fromInt(0);
})->throws(ValidationException::class, 'Invalid user ID (must be positive integer): 0');

test('throws exception for invalid integer ID (negative)', function (): void {
    UserId::fromInt(-1);
})->throws(ValidationException::class, 'Invalid user ID (must be positive integer): -1');

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
