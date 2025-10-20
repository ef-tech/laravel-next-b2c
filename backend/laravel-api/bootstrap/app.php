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

        // セキュリティヘッダーミドルウェアをグローバルに追加
        // Note: append()により最後に実行されるため、Laravel組み込みのHandleCorsミドルウェアの後に実行される
        //       これによりCORSヘッダー（Access-Control-Allow-Origin等）を上書きせず、セキュリティヘッダーのみ追加
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle DDD Domain Exceptions
        $exceptions->render(function (\Ddd\Shared\Exceptions\DomainException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'error' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        });
    })->create();
