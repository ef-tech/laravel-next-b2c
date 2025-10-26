<?php

declare(strict_types=1);

use Ddd\Domain\Admin\ValueObjects\Email;

test('Email can be created with valid email address', function () {
    $email = new Email('test@example.com');

    expect($email->value)->toBe('test@example.com');
});

test('Email throws exception for invalid email format', function () {
    new Email('invalid-email');
})->throws(InvalidArgumentException::class, 'Invalid email address: invalid-email');

test('Email equals returns true for same value', function () {
    $email1 = new Email('test@example.com');
    $email2 = new Email('test@example.com');

    expect($email1->equals($email2))->toBeTrue();
});

test('Email equals returns false for different value', function () {
    $email1 = new Email('test1@example.com');
    $email2 = new Email('test2@example.com');

    expect($email1->equals($email2))->toBeFalse();
});
