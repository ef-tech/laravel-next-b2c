<?php

declare(strict_types=1);

use App\Http\Middleware\ApiVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

describe('ApiVersion Middleware', function () {
    test('URLからバージョン番号を抽出してリクエスト属性に保存する', function () {
        $middleware = new ApiVersion;
        $request = Request::create('/api/v1/health', 'GET');

        $response = $middleware->handle($request, function ($req) {
            expect($req->attributes->get('api_version'))->toBe('v1');

            return new Response('OK');
        });

        expect($response->getStatusCode())->toBe(200);
    });

    test('バージョン指定なしの場合はデフォルトバージョンを適用する', function () {
        $middleware = new ApiVersion;
        $request = Request::create('/api/health', 'GET');

        $response = $middleware->handle($request, function ($req) {
            expect($req->attributes->get('api_version'))->toBe('v1');

            return new Response('OK');
        });

        expect($response->getStatusCode())->toBe(200);
    });

    test('サポート外バージョンへのリクエストは404を返す', function () {
        $middleware = new ApiVersion;
        $request = Request::create('/api/v99/health', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->getStatusCode())->toBe(404);
    });

    test('レスポンスヘッダーにX-API-Versionを付与する', function () {
        $middleware = new ApiVersion;
        $request = Request::create('/api/v1/health', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->headers->get('X-API-Version'))->toBe('v1');
    });

    test('ヘッダーバージョンがURLバージョンより優先される', function () {
        $middleware = new ApiVersion;
        $request = Request::create('/api/v1/health', 'GET', [], [], [], [
            'HTTP_X_API_VERSION' => 'v1',
        ]);

        $response = $middleware->handle($request, function ($req) {
            expect($req->attributes->get('api_version'))->toBe('v1');

            return new Response('OK');
        });

        expect($response->headers->get('X-API-Version'))->toBe('v1');
    });

    test('エラーレスポンスにもX-API-Versionヘッダーが含まれる', function () {
        $middleware = new ApiVersion;
        $request = Request::create('/api/v99/health', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->getStatusCode())->toBe(404)
            ->and($response->headers->has('X-API-Version'))->toBeTrue();
    });
});
