<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function ($router) {
            // V1 API Routes with /api/v1 prefix
            Route::prefix('api/v1')
                ->middleware('api')
                ->group(base_path('routes/api/v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API専用化: 認証失敗時のリダイレクトを無効化（常にJSON応答）
        $middleware->redirectGuestsTo(fn () => throw new \Illuminate\Auth\AuthenticationException);

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

        $middleware->append(\App\Http\Middleware\ApiVersion::class);
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle DDD Domain Exceptions (RFC 7807 Problem Details)
        $exceptions->render(function (\Ddd\Shared\Exceptions\DomainException $e, \Illuminate\Http\Request $request) {
            // Request IDを取得（SetRequestId Middlewareが設定している）
            $requestId = $request->header('X-Request-ID');

            // Request IDが設定されていない場合は自動生成
            if (! $requestId) {
                $requestId = (string) \Illuminate\Support\Str::uuid();
            }

            // 構造化ログコンテキストを設定
            \Illuminate\Support\Facades\Log::withContext([
                'trace_id' => $requestId,
                'error_code' => $e->getErrorCode(),
                'user_id' => \App\Support\LogHelper::hashUserId($request->user()?->getAuthIdentifier()),
                'request_path' => $request->getRequestUri(),
            ]);

            // エラーログを記録
            \Illuminate\Support\Facades\Log::error('DomainException occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);

            // RFC 7807形式の配列を取得
            $problemDetails = $e->toProblemDetails();

            // RFC 7807形式のレスポンスを生成
            return response()->json($problemDetails, $e->getStatusCode())
                ->header('Content-Type', 'application/problem+json')
                ->header('X-Request-ID', $requestId);
        });

        // Handle Validation Exceptions (422 Unprocessable Entity)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            // Request IDを取得または生成
            $requestId = $request->header('X-Request-ID') ?: (string) \Illuminate\Support\Str::uuid();

            // 構造化ログコンテキストを設定
            \Illuminate\Support\Facades\Log::withContext([
                'trace_id' => $requestId,
                'error_code' => 'validation_error',
                'user_id' => \App\Support\LogHelper::hashUserId($request->user()?->getAuthIdentifier()),
                'request_path' => $request->getRequestUri(),
            ]);

            // エラーログを記録
            \Illuminate\Support\Facades\Log::error('ValidationException occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'type' => config('app.url').'/errors/validation_error',
                'title' => 'Validation Error',
                'status' => 422,
                'detail' => $e->getMessage(),
                'error_code' => 'validation_error',
                'trace_id' => $requestId,
                'instance' => $request->getRequestUri(),
                'timestamp' => now()->toIso8601String(),
                'errors' => $e->errors(),
            ], 422)
                ->header('Content-Type', 'application/problem+json')
                ->header('X-Request-ID', $requestId);
        });

        // Handle Authentication Exceptions (401 Unauthorized)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            // Request IDを取得または生成
            $requestId = $request->header('X-Request-ID') ?: (string) \Illuminate\Support\Str::uuid();

            // 構造化ログコンテキストを設定
            \Illuminate\Support\Facades\Log::withContext([
                'trace_id' => $requestId,
                'error_code' => 'unauthenticated',
                'user_id' => null, // 認証失敗のため null
                'request_path' => $request->getRequestUri(),
            ]);

            // エラーログを記録
            \Illuminate\Support\Facades\Log::warning('AuthenticationException occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'type' => config('app.url').'/errors/unauthenticated',
                'title' => 'Unauthenticated',
                'status' => 401,
                'detail' => 'Unauthenticated.',
                'error_code' => 'unauthenticated',
                'trace_id' => $requestId,
                'instance' => $request->getRequestUri(),
                'timestamp' => now()->toIso8601String(),
            ], 401)
                ->header('Content-Type', 'application/problem+json')
                ->header('X-Request-ID', $requestId);
        });

        // Handle Rate Limit Exceptions (429 Too Many Requests)
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            // Request IDを取得または生成
            $requestId = $request->header('X-Request-ID') ?: (string) \Illuminate\Support\Str::uuid();

            // 構造化ログコンテキストを設定
            \Illuminate\Support\Facades\Log::withContext([
                'trace_id' => $requestId,
                'error_code' => 'too_many_requests',
                'user_id' => \App\Support\LogHelper::hashUserId($request->user()?->getAuthIdentifier()),
                'request_path' => $request->getRequestUri(),
            ]);

            // エラーログを記録
            \Illuminate\Support\Facades\Log::warning('ThrottleRequestsException occurred', [
                'exception' => get_class($e),
                'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
            ]);

            return response()->json([
                'type' => config('app.url').'/errors/too_many_requests',
                'title' => 'Too Many Requests',
                'status' => 429,
                'detail' => 'Too many requests. Please try again later.',
                'error_code' => 'too_many_requests',
                'trace_id' => $requestId,
                'instance' => $request->getRequestUri(),
                'timestamp' => now()->toIso8601String(),
            ], 429)
                ->header('Content-Type', 'application/problem+json')
                ->header('X-Request-ID', $requestId)
                ->header('Retry-After', $e->getHeaders()['Retry-After'] ?? 60);
        });

        // Handle All Other Exceptions (500 Internal Server Error)
        // 環境別エラーマスキング機能
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Request IDを取得または生成
            $requestId = $request->header('X-Request-ID') ?: (string) \Illuminate\Support\Str::uuid();

            // 構造化ログコンテキストを設定
            \Illuminate\Support\Facades\Log::withContext([
                'trace_id' => $requestId,
                'error_code' => 'internal_server_error',
                'user_id' => \App\Support\LogHelper::hashUserId($request->user()?->getAuthIdentifier()),
                'request_path' => $request->getRequestUri(),
            ]);

            // エラーログを記録
            \Illuminate\Support\Facades\Log::error('Throwable occurred', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // 本番環境判定
            $isProduction = config('app.env') === 'production';
            $isDebug = config('app.debug', false);

            // RFC 7807形式の基本情報
            $problemDetails = [
                'type' => config('app.url').'/errors/internal_server_error',
                'title' => 'Internal Server Error',
                'status' => 500,
                'detail' => $isProduction
                    ? 'An internal server error occurred. Please try again later.'
                    : $e->getMessage(),
                'error_code' => 'internal_server_error',
                'trace_id' => $requestId,
                'instance' => $request->getRequestUri(),
                'timestamp' => now()->toIso8601String(),
            ];

            // 開発環境ではデバッグ情報を追加
            if (! $isProduction && $isDebug) {
                $problemDetails['debug'] = [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->map(function ($trace) {
                        return \Illuminate\Support\Arr::except($trace, ['args']);
                    })->all(),
                ];
            }

            return response()->json($problemDetails, 500)
                ->header('Content-Type', 'application/problem+json')
                ->header('X-Request-ID', $requestId);
        });
    })->create();
