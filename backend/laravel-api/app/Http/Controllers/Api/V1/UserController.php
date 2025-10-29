<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterUserRequest;
use App\Models\User;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserInput;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserUseCase;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Infrastructure\Http\Presenters\V1\AuthPresenter;
use Illuminate\Http\JsonResponse;

/**
 * V1 ユーザーコントローラー
 *
 * ユーザー管理に関するエンドポイントを提供します。
 */
final class UserController extends Controller
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase
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

        // 作成したユーザーを取得してトークンを発行
        $user = User::find($output->userId->value());

        if (! $user) {
            return response()->json([
                'message' => 'User registration failed',
            ], 500);
        }

        // APIトークンを生成
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json(
            AuthPresenter::presentLogin($user, $token),
            201
        );
    }
}
