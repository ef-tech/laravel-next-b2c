<?php

declare(strict_types=1);

namespace Ddd\Application\User\UseCases\RegisterUser;

use Ddd\Domain\User\ValueObjects\Email;

final readonly class RegisterUserInput
{
    public function __construct(
        public Email $email,
        public string $name
    ) {}
}
