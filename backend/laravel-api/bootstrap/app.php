<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API専用化: セッション関連ミドルウェアを除外
        $middleware->remove([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // ========================================
        // グローバルミドルウェア（全リクエストに適用）
        // ========================================
        // 実行順序: TrustProxies → ValidatePostSize → PreventRequestsDuringMaintenance
        //          → HandleCors (Laravel組み込み)
        //          → SetRequestId → CorrelationId → ForceJsonResponse
        //          → SecurityHeaders (最後に実行)

        $middleware->append(\App\Http\Middleware\SetRequestId::class);
        $middleware->append(\App\Http\Middleware\CorrelationId::class);
        $middleware->append(\App\Http\Middleware\ForceJsonResponse::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // ========================================
        // ミドルウェアグループ
        // ========================================

        // api グループ（基底グループ - 全APIエンドポイント共通）
        $middleware->group('api', [
            // Laravel標準
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // カスタムミドルウェア
            \App\Http\Middleware\RequestLogging::class,
            \App\Http\Middleware\PerformanceMonitoring::class,
            \App\Http\Middleware\DynamicRateLimit::class.':api',
            // Note: SecurityHeadersはグローバルミドルウェアとして既に設定済み（CSP含む）
        ]);

        // auth グループ（認証必須エンドポイント）
        $middleware->group('auth', [
            'api', // api グループを継承
            'auth:sanctum', // Laravel Sanctum認証
            \App\Http\Middleware\SanctumTokenVerification::class,
            \App\Http\Middleware\AuditTrail::class,
        ]);

        // guest グループ（公開APIエンドポイント - 認証不要）
        $middleware->group('guest', [
            'api', // api グループを継承
            \App\Http\Middleware\DynamicRateLimit::class.':public',
        ]);

        // internal グループ（内部/管理用エンドポイント）
        $middleware->group('internal', [
            'api', // api グループを継承
            'auth:sanctum',
            \App\Http\Middleware\SanctumTokenVerification::class,
            \App\Http\Middleware\AuthorizationCheck::class.':admin',
            \App\Http\Middleware\DynamicRateLimit::class.':strict',
            \App\Http\Middleware\AuditTrail::class,
        ]);

        // webhook グループ（外部コールバックエンドポイント）
        $middleware->group('webhook', [
            'api', // api グループを継承
            \App\Http\Middleware\IdempotencyKey::class,
            \App\Http\Middleware\DynamicRateLimit::class.':webhook',
        ]);

        // readonly グループ（読み取り専用エンドポイント）
        $middleware->group('readonly', [
            'api', // api グループを継承
            \App\Http\Middleware\CacheHeaders::class,
            \App\Http\Middleware\ETag::class,
        ]);

        // ========================================
        // ミドルウェアエイリアス
        // ========================================
        $middleware->alias([
            'auth.token' => \App\Http\Middleware\SanctumTokenVerification::class,
            'permission' => \App\Http\Middleware\AuthorizationCheck::class,
            'audit' => \App\Http\Middleware\AuditTrail::class,
            'idempotent' => \App\Http\Middleware\IdempotencyKey::class,
            'cache.headers' => \App\Http\Middleware\CacheHeaders::class,
            'etag' => \App\Http\Middleware\ETag::class,
            'admin.guard' => \App\Http\Middleware\AdminGuard::class,
            'user.guard' => \App\Http\Middleware\UserGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle InvalidCredentialsException（認証エラー: 401）
        $exceptions->render(function (\Ddd\Domain\Admin\Exceptions\InvalidCredentialsException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
                'errors' => null,
                'trace_id' => $request->header('X-Request-Id') ?? \Illuminate\Support\Str::uuid()->toString(),
            ], $e->getStatusCode());
        });

        // Handle AccountDisabledException（アカウント無効化: 403）
        $exceptions->render(function (\Ddd\Domain\Admin\Exceptions\AccountDisabledException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
                'errors' => null,
                'trace_id' => $request->header('X-Request-Id') ?? \Illuminate\Support\Str::uuid()->toString(),
            ], $e->getStatusCode());
        });

        // Handle AdminNotFoundException（管理者未検出: 404）
        $exceptions->render(function (\Ddd\Domain\Admin\Exceptions\AdminNotFoundException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
                'errors' => null,
                'trace_id' => $request->header('X-Request-Id') ?? \Illuminate\Support\Str::uuid()->toString(),
            ], $e->getStatusCode());
        });

        // Handle Validation Exceptions（統一エラーレスポンス形式）
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => '入力内容に誤りがあります',
                'errors' => $e->errors(),
                'trace_id' => $request->header('X-Request-Id') ?? \Illuminate\Support\Str::uuid()->toString(),
            ], 422);
        });
    })->create();
