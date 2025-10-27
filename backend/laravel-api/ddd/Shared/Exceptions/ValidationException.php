<?php

declare(strict_types=1);

namespace Ddd\Shared\Exceptions;

final class ValidationException extends DomainException
{
    public static function invalidEmail(string $value): self
    {
        return new self("Invalid email address: {$value}");
    }

    public static function invalidUserId(string $value): self
    {
        return new self("Invalid user ID: {$value}");
    }

    public static function invalidAdminId(string $value): self
    {
        return new self("Invalid admin ID: {$value}");
    }

    public static function invalidName(string $reason): self
    {
        return new self("Invalid name: {$reason}");
    }

    public function getStatusCode(): int
    {
        return 400;
    }

    public function getErrorCode(): string
    {
        return 'validation_error';
    }
}
