<?php

declare(strict_types=1);

namespace Ddd\Application\User\UseCases\RegisterUser;

use Ddd\Domain\User\ValueObjects\UserId;

final readonly class RegisterUserOutput
{
    public function __construct(
        public UserId $userId
    ) {}
}
