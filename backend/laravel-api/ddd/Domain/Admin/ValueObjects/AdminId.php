<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\ValueObjects;

use Ddd\Shared\Exceptions\ValidationException;

final readonly class AdminId
{
    private function __construct(
        private int $value
    ) {
        if ($value <= 0) {
            throw ValidationException::invalidAdminId((string) $value);
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public static function fromString(string $value): self
    {
        if (! is_numeric($value) || (int) $value != $value) {
            throw ValidationException::invalidAdminId($value);
        }

        return new self((int) $value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
