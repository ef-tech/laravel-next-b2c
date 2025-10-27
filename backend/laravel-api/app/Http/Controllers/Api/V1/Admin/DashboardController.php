<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Ddd\Application\Admin\DTOs\GetAuthenticatedAdminInput;
use Ddd\Application\Admin\UseCases\GetAuthenticatedAdminUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GetAuthenticatedAdminUseCase $getAuthenticatedAdminUseCase
    ) {}

    /**
     * 管理者ダッシュボード情報取得
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        $input = new GetAuthenticatedAdminInput(
            adminId: (string) $admin->id
        );

        $output = $this->getAuthenticatedAdminUseCase->execute($input);

        return response()->json([
            'admin' => [
                'id' => $output->adminDTO->id,
                'email' => $output->adminDTO->email,
                'name' => $output->adminDTO->name,
                'role' => $output->adminDTO->role,
                'isActive' => $output->adminDTO->isActive,
            ],
        ]);
    }
}
