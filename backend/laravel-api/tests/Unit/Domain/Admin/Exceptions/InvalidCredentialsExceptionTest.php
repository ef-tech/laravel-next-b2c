<?php

declare(strict_types=1);

use Ddd\Domain\Admin\Exceptions\InvalidCredentialsException;

test('InvalidCredentialsException has correct error code', function (): void {
    $exception = new InvalidCredentialsException;

    expect($exception->getErrorCode())->toBe('AUTH.INVALID_CREDENTIALS')
        ->and($exception->getMessage())->toBe('メールアドレスまたはパスワードが正しくありません');
});

test('InvalidCredentialsException extends DomainException', function (): void {
    $exception = new InvalidCredentialsException;

    expect($exception)->toBeInstanceOf(DomainException::class);
});
