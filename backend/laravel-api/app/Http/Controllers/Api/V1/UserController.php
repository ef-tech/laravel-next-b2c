<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserInput;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserUseCase;
use Ddd\Domain\User\ValueObjects\Email;
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
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $input = new RegisterUserInput(
            email: Email::fromString($request->input('email')),
            name: $request->input('name')
        );

        $output = $this->registerUserUseCase->execute($input);

        return new JsonResponse([
            'id' => $output->userId->value(),
        ], 201);
    }
}
