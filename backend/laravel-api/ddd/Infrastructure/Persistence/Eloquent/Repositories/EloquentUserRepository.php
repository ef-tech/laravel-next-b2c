<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Persistence\Eloquent\Repositories;

use App\Models\User as EloquentUser;
use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\Repositories\UserRepository;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(
        private UserMapper $mapper
    ) {}

    public function find(UserId $id): ?User
    {
        $model = EloquentUser::find($id->value());

        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = EloquentUser::where('email', $email->value())->first();

        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return EloquentUser::where('email', $email->value())->exists();
    }

    public function save(User $user): void
    {
        // Check if this is a new user (ID not set yet)
        try {
            $userId = $user->id();
            // User has ID - update existing
            $model = EloquentUser::findOrNew($userId->value());
        } catch (\RuntimeException $e) {
            // User doesn't have ID - create new
            $model = new EloquentUser;
        }

        $this->mapper->toModel($user, $model);
        $model->save();

        // Set auto-generated ID for new users
        if (! isset($userId)) {
            // After save(), Eloquent automatically sets the ID
            if (! $model->id) {
                throw new \RuntimeException('Failed to generate ID after save');
            }
            $user->setId(UserId::fromInt($model->id));
        }
    }

    public function delete(UserId $id): void
    {
        EloquentUser::destroy($id->value());
    }
}
