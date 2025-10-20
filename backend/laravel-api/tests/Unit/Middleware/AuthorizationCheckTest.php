<?php

declare(strict_types=1);

use App\Http\Middleware\AuthorizationCheck;
use App\Models\User;
use Ddd\Application\Shared\Services\Authorization\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * AuthorizationCheck ミドルウェアのテスト
 *
 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7
 */
describe('AuthorizationCheck', function () {
    it('権限を持つユーザーのリクエストを通過させること', function () {
        /** @var AuthorizationService&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthorizationService::class);
        // @phpstan-ignore-next-line
        $authService->shouldReceive('authorize')
            ->once()
            ->with(Mockery::type(User::class), 'admin')
            ->andReturn(true);

        $middleware = new AuthorizationCheck($authService);
        $request = Request::create('/api/admin/users', 'GET');

        // 認証済みユーザーをモック
        $user = new User;
        $user->id = '123';
        $user->email = 'admin@example.com';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Authorization check passed', Mockery::any());

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'admin');

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('権限を持たないユーザーのリクエストをHTTP 403で拒否すること', function () {
        /** @var AuthorizationService&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthorizationService::class);
        // @phpstan-ignore-next-line
        $authService->shouldReceive('authorize')
            ->once()
            ->with(Mockery::type(User::class), 'admin')
            ->andReturn(false);

        $middleware = new AuthorizationCheck($authService);
        $request = Request::create('/api/admin/users', 'GET');

        // 認証済みユーザー（管理者権限なし）をモック
        $user = new User;
        $user->id = '456';
        $user->email = 'user@example.com';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Authorization check failed', Mockery::any());

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'admin');

        expect($response->getStatusCode())->toBe(403);
    });

    it('未認証ユーザーのリクエストをHTTP 401で拒否すること', function () {
        /** @var AuthorizationService&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthorizationService::class);

        $middleware = new AuthorizationCheck($authService);
        $request = Request::create('/api/admin/users', 'GET');

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
            ->with('Authorization check failed: No authenticated user', Mockery::any());

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'admin');

        expect($response->getStatusCode())->toBe(401);
    });

    it('権限検証結果をログに記録すること', function () {
        /** @var AuthorizationService&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthorizationService::class);
        // @phpstan-ignore-next-line
        $authService->shouldReceive('authorize')
            ->once()
            ->with(Mockery::type(User::class), 'user')
            ->andReturn(true);

        $middleware = new AuthorizationCheck($authService);
        $request = Request::create('/api/profile', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id-999');

        $user = new User;
        $user->id = '789';
        $user->email = 'user@example.com';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Authorization check passed', Mockery::on(function ($context) {
                return isset($context['request_id'])
                    && $context['request_id'] === 'test-request-id-999'
                    && isset($context['user_id'])
                    && $context['user_id'] === '789'
                    && isset($context['permission'])
                    && $context['permission'] === 'user'
                    && isset($context['result'])
                    && $context['result'] === true;
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'user');

        expect($response->getStatusCode())->toBe(200);
    });

    it('Application層のAuthorizationServiceポートを経由して権限判定を行うこと', function () {
        /** @var AuthorizationService&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthorizationService::class);
        // @phpstan-ignore-next-line
        $authService->shouldReceive('authorize')
            ->once()
            ->with(Mockery::type(User::class), 'admin')
            ->andReturn(true);

        $middleware = new AuthorizationCheck($authService);
        $request = Request::create('/api/admin/users', 'GET');

        $user = new User;
        $user->id = '123';
        $user->email = 'admin@example.com';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Authorization check passed', Mockery::any());

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'admin');

        expect($response->getStatusCode())->toBe(200);
    });
});
