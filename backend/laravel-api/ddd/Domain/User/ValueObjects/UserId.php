<?php

declare(strict_types=1);

namespace Ddd\Domain\User\ValueObjects;

use Ddd\Shared\Exceptions\ValidationException;

final readonly class UserId
{
    private function __construct(
        private int $value
    ) {
        // Validate positive integer
        if ($value <= 0) {
            throw ValidationException::invalidUserId((string) $value);
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public static function fromString(string $value): self
    {
        if (! is_numeric($value) || (int) $value != $value) {
            throw ValidationException::invalidUserId($value);
        }

        return new self((int) $value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }
}
