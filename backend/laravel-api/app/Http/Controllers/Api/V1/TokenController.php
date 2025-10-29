<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $tokenName = $request->input('name', 'API Token');
        $newToken = $user->createToken($tokenName);

        /** @var \Carbon\Carbon $createdAt */
        $createdAt = $newToken->accessToken->created_at;

        return response()->json([
            'token' => $newToken->plainTextToken,
            'name' => $newToken->accessToken->name,
            'created_at' => $createdAt->toISOString(),
        ], 201);
    }

    /**
     * 全トークンを取得
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $tokens = $user->tokens()->get()->map(function ($token) {
            /** @var \Carbon\Carbon $createdAt */
            $createdAt = $token->created_at;

            return [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $createdAt->toISOString(),
                'last_used_at' => $token->last_used_at?->toISOString(),
            ];
        });

        return response()->json([
            'tokens' => $tokens,
        ]);
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
            return response()->json([
                'message' => 'Token not found',
            ], 404);
        }

        $token->delete();

        return response()->json([
            'message' => 'Token deleted successfully',
        ]);
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

        return response()->json([
            'message' => 'All tokens deleted successfully',
        ]);
    }
}
