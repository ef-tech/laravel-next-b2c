<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * SanctumTokenVerification Middleware
 *
 * Laravel Sanctumトークンの詳細な検証とライフサイクル管理を強化します。
 * auth:sanctumミドルウェアと併用し、追加の詳細検証とエラーロギングを提供します。
 * - トークン有効期限検証（Sanctumが自動実行）
 * - 未認証リクエストの検出とHTTP 401返却
 * - トークン検証失敗時の詳細エラーログ記録
 * - トークン検証成功時のログ記録
 * - auth:sanctumとの併用対応
 *
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
 */
final class SanctumTokenVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // auth:sanctumミドルウェアが認証を完了しているかチェック
        $user = $request->user();

        if ($user === null) {
            // 認証失敗: 詳細なエラーログを記録
            $this->logVerificationFailure($request, 'No authenticated user');

            return new Response('Unauthorized', 401);
        }

        // 認証成功: 詳細ログを記録
        $this->logVerificationSuccess($request, $user);

        return $next($request);
    }

    /**
     * トークン検証失敗時のログ記録
     */
    private function logVerificationFailure(Request $request, string $reason): void
    {
        Log::channel('security')->warning('Token verification failed: '.$reason, [
            'request_id' => $request->header('X-Request-Id'),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * トークン検証成功時のログ記録
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable&\App\Models\User  $user
     */
    private function logVerificationSuccess(Request $request, $user): void
    {
        Log::channel('security')->info('Token verification successful', [
            'request_id' => $request->header('X-Request-Id'),
            'user_id' => $user->id,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
