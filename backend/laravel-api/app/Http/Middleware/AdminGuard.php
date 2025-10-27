<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
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
                'message' => 'Unauthorized',
            ], 401);
        }

        // is_activeチェック
        if (! $admin->is_active) {
            return response()->json([
                'message' => 'Account is disabled',
            ], 403);
        }

        return $next($request);
    }
}
