<?php

declare(strict_types=1);

use Ddd\Domain\Admin\Exceptions\AdminNotFoundException;

test('AdminNotFoundException has correct error code', function (): void {
    $exception = new AdminNotFoundException;

    expect($exception->getErrorCode())->toBe('ADMIN_NOT_FOUND')
        ->and($exception->getMessage())->toBe('Admin not found');
});

test('AdminNotFoundException extends RuntimeException', function (): void {
    $exception = new AdminNotFoundException;

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

test('AdminNotFoundException accepts custom message', function (): void {
    $exception = new AdminNotFoundException('Custom admin message');

    expect($exception->getMessage())->toBe('Custom admin message')
        ->and($exception->getErrorCode())->toBe('ADMIN_NOT_FOUND');
});
