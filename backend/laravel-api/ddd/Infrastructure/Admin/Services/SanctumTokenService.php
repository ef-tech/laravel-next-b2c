<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Admin\Services;

use App\Models\Admin as EloquentAdmin;
use Ddd\Domain\Admin\Entities\Admin;
use Ddd\Domain\Admin\Services\TokenService;
use Laravel\Sanctum\PersonalAccessToken;

final class SanctumTokenService implements TokenService
{
    public function createToken(Admin $admin): string
    {
        // Eloquent Model を取得
        $eloquentAdmin = EloquentAdmin::find($admin->id->value());

        if (! $eloquentAdmin) {
            throw new \RuntimeException("Admin not found for token creation: {$admin->id->value()}");
        }

        // Sanctum トークンを生成
        $tokenResult = $eloquentAdmin->createToken('admin-token');

        return $tokenResult->plainTextToken;
    }

    public function revokeToken(string $tokenId): void
    {
        $token = PersonalAccessToken::find($tokenId);

        if ($token) {
            $token->delete(); // Soft delete
        }
    }
}
