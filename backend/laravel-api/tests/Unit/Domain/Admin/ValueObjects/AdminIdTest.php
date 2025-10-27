<?php

declare(strict_types=1);

use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Shared\Exceptions\ValidationException;

test('can create valid admin ID from int', function (): void {
    $adminId = AdminId::fromInt(1);

    expect($adminId->value())->toBe(1);
});

test('can create valid admin ID from string', function (): void {
    $adminId = AdminId::fromString('123');

    expect($adminId->value())->toBe(123);
});

test('throws exception for zero', function (): void {
    AdminId::fromInt(0);
})->throws(ValidationException::class, 'Invalid admin ID');

test('throws exception for negative integer', function (): void {
    AdminId::fromInt(-1);
})->throws(ValidationException::class, 'Invalid admin ID');

test('throws exception for invalid string', function (): void {
    AdminId::fromString('invalid');
})->throws(ValidationException::class, 'Invalid admin ID');

test('equals returns true for same admin ID', function (): void {
    $adminId1 = AdminId::fromInt(1);
    $adminId2 = AdminId::fromInt(1);

    expect($adminId1->equals($adminId2))->toBeTrue();
});

test('equals returns false for different admin ID', function (): void {
    $adminId1 = AdminId::fromInt(1);
    $adminId2 = AdminId::fromInt(2);

    expect($adminId1->equals($adminId2))->toBeFalse();
});
