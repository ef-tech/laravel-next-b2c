<?php

declare(strict_types=1);

use App\Http\Middleware\IdempotencyKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

/**
 * IdempotencyKey ミドルウェアのテスト
 *
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8
 */
describe('IdempotencyKey', function () {
    it('Idempotency-Keyヘッダーがない場合は通常処理を行うこと', function () {
        Redis::shouldReceive('connection')->never();

        $middleware = new IdempotencyKey;
        $request = Request::create('/api/users', 'POST', [
            'name' => 'John Doe',
        ]);

        $user = new User;
        $user->id = '123';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Created', 201);
        });

        expect($response->getStatusCode())->toBe(201);
        expect($response->getContent())->toBe('Created');
    });

    it('初回リクエストでIdempotency-Keyを保存しレスポンスを返すこと', function () {
        $redis = Mockery::mock();
        Redis::shouldReceive('connection')->andReturn($redis);

        // @phpstan-ignore-next-line
        $redis->shouldReceive('get')
            ->once()
            ->with('idempotency:test-key-123:123')
            ->andReturn(null);

        // @phpstan-ignore-next-line
        $redis->shouldReceive('setex')
            ->once()
            ->with(
                'idempotency:test-key-123:123',
                86400, // 24 hours
                Mockery::type('string')
            )
            ->andReturn(true);

        $middleware = new IdempotencyKey;
        $request = Request::create('/api/users', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('Idempotency-Key', 'test-key-123');

        $user = new User;
        $user->id = '123';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Created', 201);
        });

        expect($response->getStatusCode())->toBe(201);
        expect($response->getContent())->toBe('Created');
    });

    it('同じペイロードで2回目のリクエストを受けた場合はキャッシュ済みレスポンスを返すこと', function () {
        $redis = Mockery::mock();
        Redis::shouldReceive('connection')->andReturn($redis);

        $payloadJson = (string) json_encode(['name' => 'John Doe']);
        $cachedData = json_encode([
            'payload_fingerprint' => hash('sha256', $payloadJson),
            'response' => [
                'status' => 201,
                'content' => 'Created',
                'headers' => [],
            ],
        ]);

        // @phpstan-ignore-next-line
        $redis->shouldReceive('get')
            ->once()
            ->with('idempotency:test-key-456:456')
            ->andReturn($cachedData);

        $middleware = new IdempotencyKey;
        $request = Request::create('/api/users', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('Idempotency-Key', 'test-key-456');

        $user = new User;
        $user->id = '456';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            throw new Exception('Should not execute');
        });

        expect($response->getStatusCode())->toBe(201);
        expect($response->getContent())->toBe('Created');
    });

    it('異なるペイロードで2回目のリクエストを受けた場合はHTTP 422を返すこと', function () {
        $redis = Mockery::mock();
        Redis::shouldReceive('connection')->andReturn($redis);

        $payloadJson = (string) json_encode(['name' => 'John Doe']);
        $cachedData = json_encode([
            'payload_fingerprint' => hash('sha256', $payloadJson),
            'response' => [
                'status' => 201,
                'content' => 'Created',
                'headers' => [],
            ],
        ]);

        // @phpstan-ignore-next-line
        $redis->shouldReceive('get')
            ->once()
            ->with('idempotency:test-key-789:789')
            ->andReturn($cachedData);

        $middleware = new IdempotencyKey;
        $request = Request::create('/api/users', 'POST', [
            'name' => 'Jane Smith', // Different payload
        ]);
        $request->headers->set('Idempotency-Key', 'test-key-789');

        $user = new User;
        $user->id = '789';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            throw new Exception('Should not execute');
        });

        expect($response->getStatusCode())->toBe(422);
    });

    it('GETリクエストではIdempotency検証をスキップすること', function () {
        Redis::shouldReceive('connection')->never();

        $middleware = new IdempotencyKey;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Idempotency-Key', 'test-key-get');

        $user = new User;
        $user->id = '999';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('未認証リクエストではIdempotency検証をスキップすること', function () {
        Redis::shouldReceive('connection')->never();

        $middleware = new IdempotencyKey;
        $request = Request::create('/api/users', 'POST');
        $request->headers->set('Idempotency-Key', 'test-key-unauth');

        $request->setUserResolver(function () {
            return null;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Unauthorized', 401);
        });

        expect($response->getStatusCode())->toBe(401);
    });

    it('Idempotency-KeyのTTLを24時間に設定すること', function () {
        $redis = Mockery::mock();
        Redis::shouldReceive('connection')->andReturn($redis);

        // @phpstan-ignore-next-line
        $redis->shouldReceive('get')
            ->once()
            ->andReturn(null);

        // @phpstan-ignore-next-line
        $redis->shouldReceive('setex')
            ->once()
            ->with(
                Mockery::type('string'),
                86400, // 24 hours = 86400 seconds
                Mockery::type('string')
            )
            ->andReturn(true);

        $middleware = new IdempotencyKey;
        $request = Request::create('/api/webhooks/payment', 'POST', [
            'amount' => 1000,
        ]);
        $request->headers->set('Idempotency-Key', 'webhook-key-123');

        $user = new User;
        $user->id = '111';
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Processed', 200);
        });

        expect($response->getStatusCode())->toBe(200);
    });
});
