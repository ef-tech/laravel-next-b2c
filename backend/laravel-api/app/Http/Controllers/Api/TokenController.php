<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TokenController extends Controller
{
    /**
     * Create a new token for the authenticated user.
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

        return response()->json([
            'token' => $newToken->plainTextToken,
            'name' => $newToken->accessToken->name,
            'created_at' => $newToken->accessToken->created_at?->toISOString() ?? now()->toISOString(),
        ], 201);
    }

    /**
     * Get all tokens for the authenticated user.
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $tokens = $user->tokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at?->toISOString() ?? now()->toISOString(),
                'last_used_at' => $token->last_used_at?->toISOString(),
            ];
        });

        return response()->json([
            'tokens' => $tokens,
        ]);
    }

    /**
     * Delete a specific token.
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
     * Delete all tokens for the authenticated user except the current one.
     */
    public function destroyAll(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Delete all tokens except the current one
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json([
            'message' => 'All tokens deleted successfully',
        ]);
    }
}
