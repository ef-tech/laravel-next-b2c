<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Admin\Repositories;

use App\Models\Admin as EloquentAdmin;
use Ddd\Domain\Admin\Entities\Admin;
use Ddd\Domain\Admin\Repositories\AdminRepository;
use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Domain\Admin\ValueObjects\AdminRole;
use Ddd\Domain\Admin\ValueObjects\Email;
use Illuminate\Support\Facades\Hash;

final class EloquentAdminRepository implements AdminRepository
{
    public function findById(AdminId $id): ?Admin
    {
        $eloquentAdmin = EloquentAdmin::find($id->value);

        return $eloquentAdmin ? $this->toDomainEntity($eloquentAdmin) : null;
    }

    public function findByEmail(Email $email): ?Admin
    {
        $eloquentAdmin = EloquentAdmin::where('email', $email->value)->first();

        return $eloquentAdmin ? $this->toDomainEntity($eloquentAdmin) : null;
    }

    public function verifyCredentials(Email $email, string $password): ?Admin
    {
        $eloquentAdmin = EloquentAdmin::where('email', $email->value)->first();

        if (! $eloquentAdmin) {
            return null;
        }

        // パスワードを検証
        if (! Hash::check($password, $eloquentAdmin->password)) {
            return null;
        }

        return $this->toDomainEntity($eloquentAdmin);
    }

    public function save(Admin $admin): void
    {
        EloquentAdmin::updateOrCreate(
            ['id' => $admin->id->value],
            [
                'name' => $admin->name,
                'email' => $admin->email->value,
                'role' => $admin->role->value,
                'is_active' => $admin->isActive,
            ]
        );
    }

    /**
     * Eloquent Model を Domain Entity に変換
     */
    private function toDomainEntity(EloquentAdmin $eloquentAdmin): Admin
    {
        return new Admin(
            id: new AdminId((string) $eloquentAdmin->id),
            email: new Email($eloquentAdmin->email),
            name: $eloquentAdmin->name,
            role: new AdminRole($eloquentAdmin->role),
            isActive: $eloquentAdmin->is_active
        );
    }
}
