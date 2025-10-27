<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Ddd\Application\Admin\DTOs\LogoutAdminInput;
use Ddd\Application\Admin\UseCases\LogoutAdminUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        private readonly LogoutAdminUseCase $logoutAdminUseCase
    ) {}

    /**
     * 管理者ログアウト
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        $input = new LogoutAdminInput(
            tokenId: (string) $admin->currentAccessToken()->id
        );

        $this->logoutAdminUseCase->execute($input);

        return response()->json([
            'message' => 'ログアウトしました',
        ]);
    }
}
