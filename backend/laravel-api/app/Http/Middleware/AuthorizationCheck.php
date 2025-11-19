<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Ddd\Application\Shared\Services\Authorization\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuthorizationCheck Middleware
 *
 * ユーザーの権限に基づいたきめ細かなアクセス制御を実現します。
 * Application層のAuthorizationServiceポートを経由して権限判定を行い、
 * 最小権限の原則に従ったセキュアなAPI設計を可能にします。
 * - 認証済みユーザーの権限検証
 * - 権限不足時にHTTP 403返却
 * - 未認証時にHTTP 401返却
 * - 権限検証結果のログ記録
 * - DDD/クリーンアーキテクチャ準拠
 *
 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7
 */
final class AuthorizationCheck
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        private readonly AuthorizationService $authorizationService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  要求される権限（admin/user等）
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // 認証済みユーザーの取得
        $user = $request->user();

        if ($user === null) {
            // 未認証: HTTP 401を返す
            $this->logAuthorizationFailure($request, $permission, 'No authenticated user', 401);

            return new Response('Unauthorized', 401);
        }

        // Application層のAuthorizationServiceポートを経由して権限判定
        $authorized = $this->authorizationService->authorize($user, $permission);

        if (! $authorized) {
            // 権限不足: HTTP 403を返す
            $this->logAuthorizationFailure($request, $permission, 'Insufficient permissions', 403, $user->id);

            return new Response('Forbidden', 403);
        }

        // 権限検証成功: ログ記録
        $this->logAuthorizationSuccess($request, $permission, $user->id);

        return $next($request);
    }

    /**
     * 権限検証失敗時のログ記録
     */
    private function logAuthorizationFailure(
        Request $request,
        string $permission,
        string $reason,
        int $statusCode,
        string|int|null $userId = null
    ): void {
        $logData = [
            'request_id' => $request->header('X-Request-Id'),
            'user_id' => $userId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'permission' => $permission,
            'result' => false,
            'reason' => $reason,
            'status_code' => $statusCode,
            'ip' => $request->ip(),
            'timestamp' => now()->utc()->toIso8601String(),
        ];

        $message = $userId === null
            ? 'Authorization check failed: '.$reason
            : 'Authorization check failed';

        Log::channel('security')->warning($message, $logData);
    }

    /**
     * 権限検証成功時のログ記録
     */
    private function logAuthorizationSuccess(Request $request, string $permission, string|int $userId): void
    {
        Log::channel('security')->info('Authorization check passed', [
            'request_id' => $request->header('X-Request-Id'),
            'user_id' => $userId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'permission' => $permission,
            'result' => true,
            'timestamp' => now()->utc()->toIso8601String(),
        ]);
    }
}
