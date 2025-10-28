<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Persistence\Eloquent\Mappers;

use App\Models\User as EloquentUser;
use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;

final class UserMapper
{
    /**
     * Convert Eloquent Model to Domain Entity.
     */
    public function toEntity(EloquentUser $model): User
    {
        // Use reflection to bypass private constructor
        $reflection = new \ReflectionClass(User::class);
        $entity = $reflection->newInstanceWithoutConstructor();

        // Set private properties using reflection
        $idProperty = $reflection->getProperty('id');
        // Changed from fromString() to fromInt() for bigint primary key migration (Issue #100)
        $idProperty->setValue($entity, UserId::fromInt($model->id));

        $emailProperty = $reflection->getProperty('email');
        $emailProperty->setValue($entity, Email::fromString($model->email));

        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setValue($entity, $model->name);

        $registeredAtProperty = $reflection->getProperty('registeredAt');
        $registeredAtProperty->setValue($entity, $model->created_at);

        return $entity;
    }

    /**
     * Convert Domain Entity to Eloquent Model.
     */
    public function toModel(User $entity, EloquentUser $model): void
    {
        // Don't set ID if it's a placeholder (PHP_INT_MAX) for new entities
        // Let the database auto-increment handle ID generation (Issue #100)
        if ($entity->id()->value() !== PHP_INT_MAX) {
            $model->id = $entity->id()->value();
        }

        $model->email = $entity->email()->value();
        $model->name = $entity->name();
        $model->created_at = $entity->registeredAt();
    }
}
