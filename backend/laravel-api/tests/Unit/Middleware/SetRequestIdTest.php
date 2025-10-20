<?php

declare(strict_types=1);

use App\Http\Middleware\SetRequestId;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * SetRequestId ミドルウェアのテスト
 *
 * Requirements: 1.1, 1.2, 1.3
 */
describe('SetRequestId', function () {
    it('リクエストIDが生成されること', function () {
        $middleware = new SetRequestId;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->headers->has('X-Request-Id'))->toBeTrue();
        /** @var string $requestId */
        $requestId = $response->headers->get('X-Request-Id');
        expect(Uuid::isValid($requestId))->toBeTrue();
    });

    it('既存のリクエストIDを継承すること', function () {
        $middleware = new SetRequestId;
        $existingId = (string) Uuid::uuid4();
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Request-Id', $existingId);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->headers->get('X-Request-Id'))->toBe($existingId);
    });

    it('リクエストIDがUUIDv4形式であること', function () {
        $middleware = new SetRequestId;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        /** @var string $requestId */
        $requestId = $response->headers->get('X-Request-Id');
        $uuid = Uuid::fromString($requestId);

        expect($uuid->getVersion())->toBe(4);
    });

    it('リクエストオブジェクトにもリクエストIDが設定されること', function () {
        $middleware = new SetRequestId;
        $request = Request::create('/test', 'GET');

        $middleware->handle($request, function ($req) {
            expect($req->headers->has('X-Request-Id'))->toBeTrue();
            /** @var string $requestId */
            $requestId = $req->headers->get('X-Request-Id');
            expect(Uuid::isValid($requestId))->toBeTrue();

            return new Response('OK');
        });
    });

    it('ログコンテキストにリクエストIDが追加されること', function () {
        $middleware = new SetRequestId;
        $request = Request::create('/test', 'GET');

        Log::shouldReceive('withContext')
            ->once()
            ->with(\Mockery::on(function ($context) {
                return isset($context['request_id']) && Uuid::isValid($context['request_id']);
            }));

        $middleware->handle($request, function ($req) {
            return new Response('OK');
        });
    });
});
