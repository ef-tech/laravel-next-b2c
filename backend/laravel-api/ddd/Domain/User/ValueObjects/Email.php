<?php

declare(strict_types=1);

namespace Ddd\Domain\User\ValueObjects;

use Ddd\Shared\Exceptions\ValidationException;

final readonly class Email
{
    private function __construct(
        private string $value
    ) {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidEmail($value);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }
}
