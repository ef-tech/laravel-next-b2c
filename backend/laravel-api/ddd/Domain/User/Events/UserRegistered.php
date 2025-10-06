<?php

declare(strict_types=1);

namespace Ddd\Domain\User\Events;

use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;

final readonly class UserRegistered
{
    public function __construct(
        public UserId $userId,
        public Email $email,
        public string $name
    ) {}
}
