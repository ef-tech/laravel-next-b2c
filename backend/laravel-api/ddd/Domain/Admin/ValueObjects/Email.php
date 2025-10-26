<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\ValueObjects;

use InvalidArgumentException;

final readonly class Email
{
    public function __construct(
        public string $value
    ) {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$value}");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
