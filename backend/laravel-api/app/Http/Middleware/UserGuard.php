<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UserGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('api');

        // User型チェック
        if (! $user instanceof User) {
            return response()->json([
                'code' => 'AUTH.UNAUTHORIZED',
                'message' => '認証が必要です',
                'errors' => null,
                'trace_id' => $request->header('X-Request-Id') ?? Str::uuid()->toString(),
            ], 401);
        }

        return $next($request);
    }
}
