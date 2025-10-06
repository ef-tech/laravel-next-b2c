<?php

declare(strict_types=1);

use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Shared\Exceptions\ValidationException;

test('can create valid email', function (): void {
    $email = Email::fromString('test@example.com');

    expect($email->value())->toBe('test@example.com');
});

test('throws exception for invalid email', function (): void {
    Email::fromString('invalid-email');
})->throws(ValidationException::class, 'Invalid email address: invalid-email');

test('equals returns true for same email', function (): void {
    $email1 = Email::fromString('test@example.com');
    $email2 = Email::fromString('test@example.com');

    expect($email1->equals($email2))->toBeTrue();
});

test('equals returns false for different email', function (): void {
    $email1 = Email::fromString('test1@example.com');
    $email2 = Email::fromString('test2@example.com');

    expect($email1->equals($email2))->toBeFalse();
});
