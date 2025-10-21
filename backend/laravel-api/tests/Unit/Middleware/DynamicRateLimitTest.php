<?php

declare(strict_types=1);

use App\Http\Middleware\DynamicRateLimit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * DynamicRateLimit ミドルウェアのテスト
 *
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11
 */
describe('DynamicRateLimit', function () {
    it('リクエストを通過させること', function () {
        $middleware = new DynamicRateLimit;
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        Cache::shouldReceive('store')
            ->with('redis')
            ->andReturnSelf();

        Cache::shouldReceive('get')
            ->andReturn(0);

        Cache::shouldReceive('put')
            ->andReturn(true);

        Cache::shouldReceive('add')
            ->andReturn(true);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'api');

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('レート制限ヘッダーを設定すること', function () {
        $middleware = new DynamicRateLimit;
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        Cache::shouldReceive('store')
            ->with('redis')
            ->andReturnSelf();

        // 2回目以降のリクエスト（キーが存在）
        Cache::shouldReceive('has')
            ->andReturn(true);

        // increment() は現在のカウント（増分後）を返す
        Cache::shouldReceive('increment')
            ->with(Mockery::type('string'), 1)
            ->andReturn(5);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'api');

        expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();
        expect($response->headers->has('X-RateLimit-Remaining'))->toBeTrue();
        expect($response->headers->has('X-RateLimit-Reset'))->toBeTrue();
    });

    it('レート制限超過時にHTTP 429を返すこと', function () {
        $middleware = new DynamicRateLimit;
        $request = Request::create('/api/admin', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        Cache::shouldReceive('store')
            ->with('redis')
            ->andReturnSelf();

        Cache::shouldReceive('has')
            ->andReturn(true);

        // Strictエンドポイントは10回/分の制限
        // 11リクエスト目でレート制限を返す（increment後の値が11）
        Cache::shouldReceive('increment')
            ->with(Mockery::type('string'), 1)
            ->andReturn(11);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'strict');

        expect($response->getStatusCode())->toBe(429);
    });

    it('IPアドレスベースのレート制限識別子を使用すること', function () {
        $middleware = new DynamicRateLimit;
        $request = Request::create('/api/public', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        Cache::shouldReceive('store')
            ->with('redis')
            ->andReturnSelf();

        // 初回リクエスト
        Cache::shouldReceive('has')
            ->andReturn(false);

        // rate_limit:public:192.168.1.100 の形式でキーが生成されることを確認
        Cache::shouldReceive('add')
            ->andReturn(true);

        Cache::shouldReceive('increment')
            ->with(\Mockery::on(function ($key) {
                return str_contains($key, 'rate_limit:public:192.168.1.100');
            }), 1)
            ->andReturn(1);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'public');

        expect($response->getStatusCode())->toBe(200);
    });

    it('ユーザーIDベースのレート制限識別子を使用すること', function () {
        $middleware = new DynamicRateLimit;
        $request = Request::create('/api/users', 'GET');

        // 認証済みユーザーをモック
        $user = new \App\Models\User;
        $user->id = '123';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        Cache::shouldReceive('store')
            ->with('redis')
            ->andReturnSelf();

        Cache::shouldReceive('has')
            ->andReturn(false);

        Cache::shouldReceive('add')
            ->andReturn(true);

        // rate_limit:api:123 の形式でキーが生成されることを確認
        Cache::shouldReceive('increment')
            ->with(\Mockery::on(function ($key) {
                return str_contains($key, 'rate_limit:api:123');
            }), 1)
            ->andReturn(1);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'api');

        expect($response->getStatusCode())->toBe(200);
    });

    it('Redisダウン時にレート制限をスキップすること', function () {
        $middleware = new DynamicRateLimit;
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        Cache::shouldReceive('store')
            ->with('redis')
            ->andThrow(new \Exception('Redis connection failed'));

        // Redis障害時でもリクエストは通過する
        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'api');

        expect($response->getStatusCode())->toBe(200);
    });

    it('エンドポイントタイプ別の制限値を適用すること', function () {
        $middleware = new DynamicRateLimit;

        // Strictエンドポイント: 10回/分
        $strictRequest = Request::create('/api/admin', 'POST');
        $strictRequest->server->set('REMOTE_ADDR', '192.168.1.1');

        Cache::shouldReceive('store')
            ->with('redis')
            ->andReturnSelf();

        Cache::shouldReceive('has')
            ->andReturn(false);

        Cache::shouldReceive('add')
            ->andReturn(true);

        Cache::shouldReceive('increment')
            ->andReturn(1);

        $response = $middleware->handle($strictRequest, function ($req) {
            return new Response('OK', 200);
        }, 'strict');

        // X-RateLimit-Limitが10であることを確認
        expect($response->headers->get('X-RateLimit-Limit'))->toBe('10');
    });
});
