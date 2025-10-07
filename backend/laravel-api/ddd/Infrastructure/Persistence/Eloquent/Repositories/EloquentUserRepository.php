<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Persistence\Eloquent\Repositories;

use App\Models\User as EloquentUser;
use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\Repositories\UserRepository;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;
use Illuminate\Support\Str;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(
        private UserMapper $mapper
    ) {}

    public function nextId(): UserId
    {
        return UserId::fromString((string) Str::uuid());
    }

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
        $model = EloquentUser::findOrNew($user->id()->value());
        $this->mapper->toModel($user, $model);
        $model->save();
    }

    public function delete(UserId $id): void
    {
        EloquentUser::destroy($id->value());
    }
}
