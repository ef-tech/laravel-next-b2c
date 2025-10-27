<?php

declare(strict_types=1);

use App\Http\Middleware\SanctumTokenVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * SanctumTokenVerification ミドルウェアのテスト
 *
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
 */
describe('SanctumTokenVerification', function () {
    it('認証済みリクエストを通過させること', function () {
        $middleware = new SanctumTokenVerification;
        $request = Request::create('/api/users', 'GET');

        // 認証済みユーザーをモック
        $user = new User;
        $user->id = 123;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('未認証リクエストをHTTP 401で拒否すること', function () {
        $middleware = new SanctumTokenVerification;
        $request = Request::create('/api/users', 'GET');

        // 未認証状態（user() === null）
        $request->setUserResolver(function () {
            return null;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Token verification failed: No authenticated user', \Mockery::any());

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(401);
    });

    it('トークン検証失敗時に詳細なエラーログを記録すること', function () {
        $middleware = new SanctumTokenVerification;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id-456');
        $request->server->set('REMOTE_ADDR', '192.168.1.50');

        // 未認証状態
        $request->setUserResolver(function () {
            return null;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Token verification failed: No authenticated user', \Mockery::on(function ($context) {
                return isset($context['request_id'])
                    && $context['request_id'] === 'test-request-id-456'
                    && isset($context['ip'])
                    && $context['ip'] === '192.168.1.50'
                    && isset($context['url'])
                    && isset($context['method']);
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(401);
    });

    it('認証成功時にログを記録すること', function () {
        $middleware = new SanctumTokenVerification;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id-789');

        // 認証済みユーザーをモック
        $user = new User;
        $user->id = 456;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Token verification successful', \Mockery::on(function ($context) {
                return isset($context['request_id'])
                    && $context['request_id'] === 'test-request-id-789'
                    && isset($context['user_id'])
                    && $context['user_id'] === 456;
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
    });

    it('auth:sanctumミドルウェアと併用できること', function () {
        // このテストはauth:sanctumが先に実行され、
        // SanctumTokenVerificationが追加の詳細検証として動作することを確認

        $middleware = new SanctumTokenVerification;
        $request = Request::create('/api/users', 'GET');

        // auth:sanctumが既に認証を完了している状態をシミュレート
        $user = new User;
        $user->id = 789;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Token verification successful', \Mockery::any());

        $response = $middleware->handle($request, function ($req) {
            // auth:sanctumの後に実行されるミドルウェアチェーン
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
    });
});
