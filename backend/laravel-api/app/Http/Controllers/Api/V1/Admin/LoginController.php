<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Ddd\Application\Admin\DTOs\LoginAdminInput;
use Ddd\Application\Admin\UseCases\LoginAdminUseCase;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private readonly LoginAdminUseCase $loginAdminUseCase,
    ) {}

    /**
     * 管理者ログイン
     *
     * @throws \Ddd\Domain\Admin\Exceptions\InvalidCredentialsException 認証情報が無効な場合
     * @throws \Ddd\Domain\Admin\Exceptions\AccountDisabledException アカウントが無効な場合
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $input = new LoginAdminInput(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        $output = $this->loginAdminUseCase->execute($input);

        return response()->json([
            'token' => $output->token,
            'admin' => [
                'id' => $output->adminDTO->id,
                'name' => $output->adminDTO->name,
                'email' => $output->adminDTO->email,
                'role' => $output->adminDTO->role,
                'is_active' => $output->adminDTO->isActive,
            ],
        ]);
    }
}
