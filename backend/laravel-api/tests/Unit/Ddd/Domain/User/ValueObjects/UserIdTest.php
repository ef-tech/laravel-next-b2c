<?php

declare(strict_types=1);

use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Shared\Exceptions\ValidationException;

test('can create valid UUID', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $userId = UserId::fromString($uuid);

    expect($userId->value())->toBe($uuid);
});

test('throws exception for invalid UUID', function (): void {
    UserId::fromString('invalid-uuid');
})->throws(ValidationException::class, 'Invalid user ID (must be UUID v4): invalid-uuid');

test('equals returns true for same user ID', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $userId1 = UserId::fromString($uuid);
    $userId2 = UserId::fromString($uuid);

    expect($userId1->equals($userId2))->toBeTrue();
});

test('equals returns false for different user ID', function (): void {
    $userId1 = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
    $userId2 = UserId::fromString('660e8400-e29b-41d4-a716-446655440001');

    expect($userId1->equals($userId2))->toBeFalse();
});
