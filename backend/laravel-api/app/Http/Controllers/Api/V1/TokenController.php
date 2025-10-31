<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateTokenRequest;
use App\Models\User;
use Ddd\Infrastructure\Http\Presenters\V1\TokenPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * V1 トークンコントローラー
 *
 * APIトークン管理に関するエンドポイントを提供します。
 */
class TokenController extends Controller
{
    /**
     * 新しいトークンを作成
     */
    public function store(CreateTokenRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $tokenName = $request->input('name', 'API Token');
        $newToken = $user->createToken($tokenName);

        return response()->json(
            TokenPresenter::presentNewToken($newToken),
            201
        );
    }

    /**
     * 全トークンを取得
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $tokens = $user->tokens()->get();

        return response()->json(
            TokenPresenter::presentTokenList($tokens)
        );
    }

    /**
     * 特定のトークンを削除
     */
    public function destroy(string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $token = $user->tokens()->find($id);

        if (! $token) {
            return response()->json(
                TokenPresenter::presentTokenNotFound(),
                404
            );
        }

        $token->delete();

        return response()->json(
            TokenPresenter::presentTokenDeleted()
        );
    }

    /**
     * 現在のトークン以外の全トークンを削除
     */
    public function destroyAll(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // 現在のトークン以外を削除
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(
            TokenPresenter::presentAllTokensDeleted()
        );
    }
}
