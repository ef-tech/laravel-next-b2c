<?php

declare(strict_types=1);

namespace Ddd\Domain\User\ValueObjects;

use Ddd\Shared\Exceptions\ValidationException;

final readonly class UserId
{
    private function __construct(
        private int $value
    ) {
        // Changed from UUID v4 format to bigint for Issue #100
        // Validate that the ID is a positive integer
        if ($value <= 0) {
            throw ValidationException::invalidUserId((string) $value);
        }
    }

    /**
     * Create UserId from string representation (for backward compatibility)
     */
    public static function fromString(string $value): self
    {
        if (! is_numeric($value)) {
            throw ValidationException::invalidUserId($value);
        }

        return new self((int) $value);
    }

    /**
     * Create UserId from integer value
     */
    public static function fromInt(int $value): self
    {
        return new self($value);
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
