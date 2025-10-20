<?php

declare(strict_types=1);

use App\Http\Middleware\ETag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ETag ミドルウェアのテスト
 *
 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7
 */
describe('ETag', function () {
    it('GETリクエストのレスポンスにETagヘッダーを設定すること', function () {
        $middleware = new ETag;
        $request = Request::create('/api/users', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('{"users":[{"id":1,"name":"John"}]}', 200);
        });

        expect($response->headers->has('ETag'))->toBeTrue();
        expect($response->headers->get('ETag'))->toBeString();
        expect($response->headers->get('ETag'))->toStartWith('"');
        expect($response->headers->get('ETag'))->toEndWith('"');
    });

    it('If-None-MatchヘッダーとETagが一致する場合はHTTP 304を返すこと', function () {
        $middleware = new ETag;
        $content = '{"users":[{"id":1,"name":"John"}]}';
        $etag = '"'.hash('sha256', $content).'"';

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('If-None-Match', $etag);

        $response = $middleware->handle($request, function ($req) use ($content) {
            return new Response($content, 200);
        });

        expect($response->getStatusCode())->toBe(304);
        expect($response->getContent())->toBe('');
    });

    it('If-None-MatchヘッダーとETagが異なる場合はHTTP 200を返すこと', function () {
        $middleware = new ETag;
        $content = '{"users":[{"id":1,"name":"John"}]}';
        $differentEtag = '"different-etag"';

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('If-None-Match', $differentEtag);

        $response = $middleware->handle($request, function ($req) use ($content) {
            return new Response($content, 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe($content);
    });

    it('レスポンスボディサイズが1MB以上の場合はETag生成をスキップすること', function () {
        $middleware = new ETag;
        $largeContent = str_repeat('a', 1024 * 1024 + 1); // 1MB + 1 byte

        $request = Request::create('/api/large', 'GET');

        $response = $middleware->handle($request, function ($req) use ($largeContent) {
            return new Response($largeContent, 200);
        });

        expect($response->headers->has('ETag'))->toBeFalse();
    });

    it('POST/PUT/PATCH/DELETEリクエストではETagを生成しないこと', function () {
        $middleware = new ETag;

        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $request = Request::create('/api/users', $method);

            $response = $middleware->handle($request, function ($req) {
                return new Response('{"message":"Success"}', 200);
            });

            expect($response->headers->has('ETag'))->toBeFalse();
        }
    });

    it('SHA256ハッシュからETagを生成すること', function () {
        $middleware = new ETag;
        $content = '{"test":"data"}';
        $expectedEtag = '"'.hash('sha256', $content).'"';

        $request = Request::create('/api/test', 'GET');

        $response = $middleware->handle($request, function ($req) use ($content) {
            return new Response($content, 200);
        });

        expect($response->headers->get('ETag'))->toBe($expectedEtag);
    });

    it('空のレスポンスボディの場合もETagを生成すること', function () {
        $middleware = new ETag;
        $request = Request::create('/api/empty', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('', 200);
        });

        expect($response->headers->has('ETag'))->toBeTrue();
        expect($response->headers->get('ETag'))->toBeString();
    });

    it('HTTP 304レスポンスではレスポンスボディを空にすること', function () {
        $middleware = new ETag;
        $content = '{"users":[{"id":1,"name":"John"}]}';
        $etag = '"'.hash('sha256', $content).'"';

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('If-None-Match', $etag);

        $response = $middleware->handle($request, function ($req) use ($content) {
            return new Response($content, 200);
        });

        expect($response->getStatusCode())->toBe(304);
        expect($response->getContent())->toBe('');
        expect($response->headers->has('ETag'))->toBeTrue();
    });
});
