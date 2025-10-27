<?php

declare(strict_types=1);

use Ddd\Domain\Admin\Exceptions\AccountDisabledException;

test('AccountDisabledException has correct error code', function (): void {
    $exception = new AccountDisabledException;

    expect($exception->getErrorCode())->toBe('AUTH.ACCOUNT_DISABLED')
        ->and($exception->getMessage())->toBe('アカウントが無効化されています');
});

test('AccountDisabledException extends DomainException', function (): void {
    $exception = new AccountDisabledException;

    expect($exception)->toBeInstanceOf(DomainException::class);
});
