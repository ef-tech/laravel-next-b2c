<?php

declare(strict_types=1);

namespace Ddd\Domain\Admin\ValueObjects;

final readonly class AdminId
{
    public function __construct(
        public string $value
    ) {}

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
