<?php

declare(strict_types=1);

namespace Ddd\Domain\User\ValueObjects;

use Ddd\Shared\Exceptions\ValidationException;

final readonly class UserId
{
    private function __construct(
        private string $value
    ) {
        // UUID v4 format validation
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            throw ValidationException::invalidUserId($value);
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

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;
    }
}
