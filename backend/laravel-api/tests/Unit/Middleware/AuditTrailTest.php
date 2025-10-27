<?php

declare(strict_types=1);

use App\Http\Middleware\AuditTrail;
use App\Models\User;
use Ddd\Application\Shared\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * AuditTrail ミドルウェアのテスト
 *
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7
 */
describe('AuditTrail', function () {
    it('POSTリクエストで監査イベントを記録すること', function () {
        /** @var AuditService&\Mockery\MockInterface $auditService */
        $auditService = Mockery::mock(AuditService::class);
        // @phpstan-ignore-next-line
        $auditService->shouldReceive('recordEvent')
            ->once()
            ->with(Mockery::on(function ($event) {
                return isset($event['user_id'])
                    && $event['user_id'] === 123
                    && isset($event['action'])
                    && $event['action'] === 'POST'
                    && isset($event['resource'])
                    && $event['resource'] === 'api/users'
                    && isset($event['changes'])
                    && isset($event['ip'])
                    && isset($event['timestamp']);
            }));

        $middleware = new AuditTrail($auditService);
        $request = Request::create('/api/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $user = new User;
        $user->id = 123;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Created', 201);
        });

        $middleware->terminate($request, $response);

        expect($response->getStatusCode())->toBe(201);
    });

    it('PUTリクエストで監査イベントを記録すること', function () {
        /** @var AuditService&\Mockery\MockInterface $auditService */
        $auditService = Mockery::mock(AuditService::class);
        // @phpstan-ignore-next-line
        $auditService->shouldReceive('recordEvent')
            ->once()
            ->with(Mockery::type('array'));

        $middleware = new AuditTrail($auditService);
        $request = Request::create('/api/users/123', 'PUT', [
            'name' => 'Jane Doe',
        ]);

        $user = new User;
        $user->id = 456;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Updated', 200);
        });

        $middleware->terminate($request, $response);

        expect($response->getStatusCode())->toBe(200);
    });

    it('GETリクエストでは監査イベントを記録しないこと', function () {
        /** @var AuditService&\Mockery\MockInterface $auditService */
        $auditService = Mockery::mock(AuditService::class);
        // @phpstan-ignore-next-line
        $auditService->shouldReceive('recordEvent')->never();

        $middleware = new AuditTrail($auditService);
        $request = Request::create('/api/users', 'GET');

        $user = new User;
        $user->id = 789;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);

        expect($response->getStatusCode())->toBe(200);
    });

    it('未認証リクエストでは監査イベントを記録しないこと', function () {
        /** @var AuditService&\Mockery\MockInterface $auditService */
        $auditService = Mockery::mock(AuditService::class);
        // @phpstan-ignore-next-line
        $auditService->shouldReceive('recordEvent')->never();

        $middleware = new AuditTrail($auditService);
        $request = Request::create('/api/users', 'POST');

        $request->setUserResolver(function () {
            return null;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Unauthorized', 401);
        });

        $middleware->terminate($request, $response);

        expect($response->getStatusCode())->toBe(401);
    });

    it('機密データをマスキングすること', function () {
        /** @var AuditService&\Mockery\MockInterface $auditService */
        $auditService = Mockery::mock(AuditService::class);
        // @phpstan-ignore-next-line
        $auditService->shouldReceive('recordEvent')
            ->once()
            ->with(Mockery::on(function ($event) {
                $changes = $event['changes'];

                return isset($changes['password'])
                    && $changes['password'] === '***MASKED***'
                    && isset($changes['token'])
                    && $changes['token'] === '***MASKED***';
            }));

        $middleware = new AuditTrail($auditService);
        $request = Request::create('/api/users', 'POST', [
            'name' => 'John Doe',
            'password' => 'secret123',
            'token' => 'abc123token',
        ]);

        $user = new User;
        $user->id = 123;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Created', 201);
        });

        $middleware->terminate($request, $response);

        expect($response->getStatusCode())->toBe(201);
    });

    it('Application層のAuditServiceポートを経由してイベントを発火すること', function () {
        /** @var AuditService&\Mockery\MockInterface $auditService */
        $auditService = Mockery::mock(AuditService::class);
        // @phpstan-ignore-next-line
        $auditService->shouldReceive('recordEvent')
            ->once()
            ->with(Mockery::type('array'));

        $middleware = new AuditTrail($auditService);
        $request = Request::create('/api/users', 'DELETE');

        $user = new User;
        $user->id = 999;
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Deleted', 204);
        });

        $middleware->terminate($request, $response);

        expect($response->getStatusCode())->toBe(204);
    });
});
