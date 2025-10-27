<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Handle user login.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        // Attempt to authenticate the user
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password ?? '')) {
            return response()->json([
                'code' => 'AUTH.INVALID_CREDENTIALS',
                'message' => '認証情報が無効です',
                'errors' => null,
                'trace_id' => $request->header('X-Request-Id') ?? Str::uuid()->toString(),
            ], 401);
        }

        // Generate API token
        $token = $user->createToken('user-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
