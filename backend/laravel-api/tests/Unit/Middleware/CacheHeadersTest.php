<?php

declare(strict_types=1);

use App\Http\Middleware\CacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CacheHeaders ミドルウェアのテスト
 *
 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7
 */
describe('CacheHeaders', function () {
    it('GETリクエストにCache-Controlヘッダーを設定すること', function () {
        config(['cache_headers.enabled' => true]);
        config(['cache_headers.default_ttl' => 300]);
        config(['app.env' => 'production']);

        $middleware = new CacheHeaders;
        $request = Request::create('/api/users', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->headers->has('Cache-Control'))->toBeTrue();
        expect($response->headers->get('Cache-Control'))->toContain('max-age=300');
        expect($response->headers->get('Cache-Control'))->toContain('public');
    });

    it('開発環境ではno-cacheを設定すること', function () {
        config(['cache_headers.enabled' => true]);
        config(['app.env' => 'local']);

        $middleware = new CacheHeaders;
        $request = Request::create('/api/users', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->headers->has('Cache-Control'))->toBeTrue();
        expect($response->headers->get('Cache-Control'))->toContain('no-cache');
        expect($response->headers->get('Cache-Control'))->toContain('no-store');
        expect($response->headers->get('Cache-Control'))->toContain('must-revalidate');
    });

    it('エンドポイントごとに異なるmax-age値を設定すること', function () {
        config(['cache_headers.enabled' => true]);
        config(['app.env' => 'production']);
        config(['cache_headers.ttl_by_path' => [
            'api/health' => 60,
            'api/user' => 300,
            'api/posts' => 600,
        ]]);

        $middleware = new CacheHeaders;
        $request = Request::create('/api/health', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->headers->get('Cache-Control'))->toContain('max-age=60');
        expect($response->headers->get('Cache-Control'))->toContain('public');
    });

    it('Expiresヘッダーを設定すること', function () {
        config(['cache_headers.enabled' => true]);
        config(['app.env' => 'production']);
        config(['cache_headers.default_ttl' => 300]);

        $middleware = new CacheHeaders;
        $request = Request::create('/api/users', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->headers->has('Expires'))->toBeTrue();
        // Expires header should be a valid HTTP date
        $expires = $response->headers->get('Expires');
        expect($expires)->toBeString();
        expect($expires)->not()->toBeNull();
        if ($expires !== null) {
            expect(strtotime($expires))->toBeGreaterThan(time());
        }
    });

    it('POST/PUT/PATCH/DELETEリクエストではキャッシュヘッダーを設定しないこと', function () {
        config(['cache_headers.enabled' => true]);
        config(['app.env' => 'production']);

        $middleware = new CacheHeaders;

        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $request = Request::create('/api/users', $method);

            $response = $middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

            expect($response->headers->has('Cache-Control'))->toBeFalse();
            expect($response->headers->has('Expires'))->toBeFalse();
        }
    });

    it('環境変数でキャッシュヘッダー機能を無効化できること', function () {
        config(['cache_headers.enabled' => false]);

        $middleware = new CacheHeaders;
        $request = Request::create('/api/users', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->headers->has('Cache-Control'))->toBeFalse();
        expect($response->headers->has('Expires'))->toBeFalse();
    });

    it('デフォルトTTL値を使用すること', function () {
        config(['cache_headers.enabled' => true]);
        config(['app.env' => 'production']);
        config(['cache_headers.default_ttl' => 600]);
        config(['cache_headers.ttl_by_path' => []]);

        $middleware = new CacheHeaders;
        $request = Request::create('/api/unknown', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->headers->get('Cache-Control'))->toContain('max-age=600');
        expect($response->headers->get('Cache-Control'))->toContain('public');
    });
});
