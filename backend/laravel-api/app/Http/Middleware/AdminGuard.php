<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdminGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        // Admin型チェック
        if (! $admin instanceof Admin) {
            return response()->json([
                'code' => 'AUTH.UNAUTHORIZED',
                'message' => '認証が必要です',
                'errors' => null,
                'trace_id' => $request->header('X-Request-Id') ?? Str::uuid()->toString(),
            ], 401);
        }

        // is_activeチェック
        if (! $admin->is_active) {
            return response()->json([
                'code' => 'AUTH.ACCOUNT_DISABLED',
                'message' => 'アカウントが無効です',
                'errors' => null,
                'trace_id' => $request->header('X-Request-Id') ?? Str::uuid()->toString(),
            ], 403);
        }

        return $next($request);
    }
}
