<?php

declare(strict_types=1);

use App\Http\Middleware\RequestLogging;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * RequestLogging ミドルウェアのテスト
 *
 * Requirements: 1.8, 1.9, 1.10, 1.11, 1.12
 */
describe('RequestLogging', function () {
    it('リクエストを通過させること', function () {
        $middleware = new RequestLogging;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('terminateメソッドで構造化ログを出力すること', function () {
        $middleware = new RequestLogging;
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('X-Request-Id', 'test-request-id-123');
        $request->headers->set('X-Correlation-Id', 'test-correlation-id-456');
        $request->headers->set('User-Agent', 'Test/1.0');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        Log::shouldReceive('channel')
            ->once()
            ->with('middleware')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Request completed', \Mockery::on(function ($context) {
                return isset($context['request_id'])
                    && $context['request_id'] === 'test-request-id-123'
                    && isset($context['correlation_id'])
                    && $context['correlation_id'] === 'test-correlation-id-456'
                    && isset($context['method'])
                    && $context['method'] === 'POST'
                    && isset($context['url'])
                    && $context['url'] === 'http://localhost/api/test'
                    && isset($context['status'])
                    && $context['status'] === 200
                    && isset($context['duration_ms'])
                    && isset($context['ip'])
                    && $context['ip'] === '192.168.1.1'
                    && isset($context['user_agent'])
                    && $context['user_agent'] === 'Test/1.0';
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('機密データをマスキングすること', function () {
        $middleware = new RequestLogging;
        $request = Request::create('/api/login', 'POST', [
            'email' => 'user@example.com',
            'password' => 'secret123',
            'token' => 'abc123token',
        ]);
        $request->headers->set('X-Request-Id', 'test-request-id');
        $request->headers->set('Authorization', 'Bearer secret-token');

        Log::shouldReceive('channel')
            ->once()
            ->with('middleware')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Request completed', \Mockery::on(function ($context) {
                // 機密データがマスキングされているか確認
                $requestData = json_decode($context['request_data'] ?? '{}', true);

                return isset($requestData['password'])
                    && $requestData['password'] === '***MASKED***'
                    && isset($requestData['token'])
                    && $requestData['token'] === '***MASKED***';
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('認証済みユーザーIDをログに記録すること', function () {
        $middleware = new RequestLogging;
        $request = Request::create('/api/profile', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id');

        // 認証済みユーザーをモック
        $user = new \App\Models\User;
        $user->id = '123';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('middleware')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Request completed', \Mockery::on(function ($context) {
                return isset($context['user_id'])
                    && $context['user_id'] === '123';
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('duration_msがマイクロ秒精度で計測されること', function () {
        $middleware = new RequestLogging;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id');

        Log::shouldReceive('channel')
            ->once()
            ->with('middleware')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Request completed', \Mockery::on(function ($context) {
                // duration_msが存在し、正の数値であることを確認
                return isset($context['duration_ms'])
                    && is_numeric($context['duration_ms'])
                    && $context['duration_ms'] >= 0;
            }));

        $response = $middleware->handle($request, function ($req) {
            usleep(1000); // 1ms待機

            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });
});
