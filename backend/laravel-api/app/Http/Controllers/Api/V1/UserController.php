<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterUserRequest;
use App\Models\User as EloquentUser;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserInput;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserUseCase;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Infrastructure\Http\Presenters\V1\AuthPresenter;
use Ddd\Infrastructure\Services\TokenGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * V1 ユーザーコントローラー
 *
 * ユーザー管理に関するエンドポイントを提供します。
 */
final class UserController extends Controller
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
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

        // パスワードをEloquentモデルに直接保存
        // Note: パスワードはまだDomainモデルに含まれていないため、
        // ここでEloquentモデルに直接ハッシュ化して保存します
        $user = EloquentUser::find($output->userId->value());
        if ($user) {
            $user->password = Hash::make($request->input('password'));
            $user->save();
        }

        // APIトークンを生成
        // Note: Infrastructure層のTokenGenerationServiceを使用することで、
        // Controller層がModel層を直接参照することを回避します
        $result = $this->tokenGenerationService->generateToken($output->userId);

        return response()->json(
            AuthPresenter::presentLogin($result['user'], $result['token']),
            201
        );
    }
}
