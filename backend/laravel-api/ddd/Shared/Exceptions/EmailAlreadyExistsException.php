<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

final class EmailAlreadyExistsException extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self("Email already registered: {$email}");
    }

    public function getStatusCode(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'email_already_exists';
    }
}
