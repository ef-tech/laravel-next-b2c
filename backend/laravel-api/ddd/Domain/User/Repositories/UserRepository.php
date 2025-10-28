<?php

declare(strict_types=1);

namespace Ddd\Domain\User\Repositories;

use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;

interface UserRepository
{
    /**
     * Find a user by ID.
     */
    public function find(UserId $id): ?User;

    /**
     * Find a user by email address.
     */
    public function findByEmail(Email $email): ?User;

    /**
     * Check if a user exists with the given email.
     */
    public function existsByEmail(Email $email): bool;

    /**
     * Save a user (create or update).
     */
    public function save(User $user): void;

    /**
     * Delete a user by ID.
     */
    public function delete(UserId $id): void;
}
