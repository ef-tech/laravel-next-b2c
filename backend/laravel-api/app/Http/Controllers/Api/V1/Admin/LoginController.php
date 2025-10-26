<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Ddd\Application\Admin\DTOs\LoginAdminInput;
use Ddd\Application\Admin\UseCases\LoginAdminUseCase;
use Ddd\Domain\Admin\Exceptions\AccountDisabledException;
use Ddd\Domain\Admin\Exceptions\InvalidCredentialsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function __construct(
        private readonly LoginAdminUseCase $loginAdminUseCase,
    ) {}

    /**
     * 管理者ログイン
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
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
        } catch (InvalidCredentialsException $e) {
            return $this->errorResponse(
                'AUTH.INVALID_CREDENTIALS',
                $e->getMessage(),
                401,
                $request
            );
        } catch (AccountDisabledException $e) {
            return $this->errorResponse(
                'AUTH.ACCOUNT_DISABLED',
                $e->getMessage(),
                403,
                $request
            );
        }
    }

    /**
     * 統一エラーレスポンス生成
     *
     * @param  string  $code  エラーコード
     * @param  string  $message  エラーメッセージ
     * @param  int  $statusCode  HTTPステータスコード
     * @param  LoginRequest  $request  リクエストオブジェクト
     */
    private function errorResponse(
        string $code,
        string $message,
        int $statusCode,
        LoginRequest $request
    ): JsonResponse {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'errors' => null,
            'trace_id' => $request->header('X-Request-Id') ?? Str::uuid()->toString(),
        ], $statusCode);
    }
}
