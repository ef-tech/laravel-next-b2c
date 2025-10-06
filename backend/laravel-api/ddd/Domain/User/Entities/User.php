<?php

declare(strict_types=1);

namespace Ddd\Domain\User\Entities;

use Carbon\Carbon;
use Ddd\Domain\User\Events\UserRegistered;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Shared\Exceptions\ValidationException;
use Ddd\Shared\Traits\RecordsDomainEvents;

final class User
{
    use RecordsDomainEvents;

    private function __construct(
        private UserId $id,
        private Email $email,
        private string $name,
        private Carbon $registeredAt
    ) {}

    /**
     * Factory method to register a new user.
     */
    public static function register(
        UserId $id,
        Email $email,
        string $name
    ): self {
        if (strlen($name) < 2) {
            throw ValidationException::invalidName('Name must be at least 2 characters');
        }

        $user = new self(
            id: $id,
            email: $email,
            name: $name,
            registeredAt: Carbon::now()
        );

        $user->recordThat(new UserRegistered(
            userId: $id,
            email: $email,
            name: $name
        ));

        return $user;
    }

    /**
     * Change the user's name.
     */
    public function changeName(string $newName): void
    {
        if (strlen($newName) < 2) {
            throw ValidationException::invalidName('Name must be at least 2 characters');
        }

        $this->name = $newName;
    }

    // Getters

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function registeredAt(): Carbon
    {
        return $this->registeredAt;
    }
}
