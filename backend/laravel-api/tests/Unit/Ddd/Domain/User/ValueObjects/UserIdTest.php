<?php

declare(strict_types=1);

use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Shared\Exceptions\ValidationException;

test('can create valid integer user ID from string', function (): void {
    $userId = UserId::fromString('123');

    expect($userId->value())->toBe(123);
    expect($userId->value())->toBeInt();
});

test('can create valid integer user ID from int', function (): void {
    $userId = UserId::fromInt(456);

    expect($userId->value())->toBe(456);
    expect($userId->value())->toBeInt();
});

test('throws exception for non-numeric string', function (): void {
    UserId::fromString('invalid-id');
})->throws(ValidationException::class);

test('throws exception for zero ID', function (): void {
    UserId::fromInt(0);
})->throws(ValidationException::class);

test('throws exception for negative ID', function (): void {
    UserId::fromInt(-1);
})->throws(ValidationException::class);

test('equals returns true for same user ID', function (): void {
    $userId1 = UserId::fromInt(123);
    $userId2 = UserId::fromInt(123);

    expect($userId1->equals($userId2))->toBeTrue();
});

test('equals returns false for different user ID', function (): void {
    $userId1 = UserId::fromInt(123);
    $userId2 = UserId::fromInt(456);

    expect($userId1->equals($userId2))->toBeFalse();
});
