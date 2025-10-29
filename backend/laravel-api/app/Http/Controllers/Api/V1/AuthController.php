<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * V1 認証コントローラー
 *
 * ユーザー認証に関するエンドポイントを提供します。
 */
class AuthController extends Controller
{
    /**
     * ユーザーログイン
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // ユーザー認証を試行
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password ?? '')) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // APIトークンを生成
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * ユーザーログアウト
     */
    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * 認証済みユーザー情報を取得
     */
    public function user(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
