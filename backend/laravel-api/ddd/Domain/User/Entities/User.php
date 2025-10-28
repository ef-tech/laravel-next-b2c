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
        private ?UserId $id,
        private Email $email,
        private string $name,
        private Carbon $registeredAt
    ) {}

    /**
     * Factory method to register a new user.
     * ID will be set after persistence (bigint auto_increment).
     */
    public static function register(
        Email $email,
        string $name,
        ?UserId $id = null
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

        // Domain event will be recorded after ID is set (in repository)

        return $user;
    }

    /**
     * Set the user ID after persistence (for auto-increment).
     */
    public function setId(UserId $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException('User ID is already set');
        }

        $this->id = $id;

        // Record domain event after ID is set
        $this->recordThat(new UserRegistered(
            userId: $id,
            email: $this->email,
            name: $this->name
        ));
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
        if ($this->id === null) {
            throw new \RuntimeException('User ID is not set yet (entity not persisted)');
        }

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
