<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterUserRequest;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserInput;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserUseCase;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Infrastructure\Http\Presenters\V1\AuthPresenter;
use Ddd\Infrastructure\Services\PasswordService;
use Ddd\Infrastructure\Services\TokenGenerationService;
use Illuminate\Http\JsonResponse;

/**
 * V1 ユーザーコントローラー
 *
 * ユーザー管理に関するエンドポイントを提供します。
 */
final class UserController extends Controller
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly PasswordService $passwordService,
        private readonly TokenGenerationService $tokenGenerationService
    ) {}

    /**
     * ユーザー登録
     *
     * 新規ユーザーを作成し、自動的にログイン状態にする（トークン発行）
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $input = new RegisterUserInput(
            email: Email::fromString($request->input('email')),
            name: $request->input('name')
        );

        $output = $this->registerUserUseCase->execute($input);

        // パスワードを設定
        // Note: パスワードはまだDomainモデルに含まれていないため、
        // Infrastructure層のPasswordServiceを使用してEloquentモデルに保存します
        $this->passwordService->setPassword($output->userId, $request->input('password'));

        // APIトークンを生成
        $result = $this->tokenGenerationService->generateToken($output->userId);

        return response()->json(
            AuthPresenter::presentLogin($result['user'], $result['token']),
            201
        );
    }
}
