<?php

declare(strict_types=1);

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ForceJsonResponse ミドルウェアのテスト
 *
 * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5
 */
describe('ForceJsonResponse', function () {
    it('Acceptヘッダーがapplication/jsonの場合はリクエストを通過させること', function () {
        $middleware = new ForceJsonResponse;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('Acceptヘッダーがapplication/json以外の場合はHTTP 406を返すこと', function () {
        $middleware = new ForceJsonResponse;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'text/html');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(406);
        expect($response->headers->get('Content-Type'))->toContain('application/json');

        $responseContent = $response->getContent();
        expect($responseContent)->not()->toBeFalse();
        if ($responseContent !== false) {
            $content = json_decode($responseContent, true);
            expect($content)->toBeArray();
            expect($content)->toHaveKey('error');
            expect($content['error'])->toBe('Not Acceptable');
        }
    });

    it('Acceptヘッダーが設定されていない場合はHTTP 406を返すこと', function () {
        $middleware = new ForceJsonResponse;
        $request = Request::create('/api/users', 'GET');
        // Acceptヘッダーを明示的に削除
        $request->headers->remove('Accept');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(406);
        expect($response->headers->get('Content-Type'))->toContain('application/json');

        $responseContent = $response->getContent();
        expect($responseContent)->not()->toBeFalse();
        if ($responseContent !== false) {
            $content = json_decode($responseContent, true);
            expect($content)->toBeArray();
            expect($content)->toHaveKey('error');
        }
    });

    it('POST/PUT/PATCHリクエストでContent-Typeがapplication/jsonの場合はリクエストを通過させること', function () {
        $middleware = new ForceJsonResponse;

        foreach (['POST', 'PUT', 'PATCH'] as $method) {
            $jsonContent = json_encode(['name' => 'John']);
            if ($jsonContent === false) {
                $jsonContent = '{}';
            }
            $request = Request::create('/api/users', $method, [], [], [], [], $jsonContent);
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('Content-Type', 'application/json');

            $response = $middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

            expect($response->getStatusCode())->toBe(200);
            expect($response->getContent())->toBe('OK');
        }
    });

    it('POST/PUT/PATCHリクエストでContent-Typeがapplication/json以外の場合はHTTP 415を返すこと', function () {
        $middleware = new ForceJsonResponse;

        foreach (['POST', 'PUT', 'PATCH'] as $method) {
            $request = Request::create('/api/users', $method, [], [], [], [], 'name=John');
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

            $response = $middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });

            expect($response->getStatusCode())->toBe(415);
            expect($response->headers->get('Content-Type'))->toContain('application/json');

            $responseContent = $response->getContent();
            expect($responseContent)->not()->toBeFalse();
            if ($responseContent !== false) {
                $content = json_decode($responseContent, true);
                expect($content)->toBeArray();
                expect($content)->toHaveKey('error');
                expect($content['error'])->toBe('Unsupported Media Type');
            }
        }
    });

    it('GETリクエストではContent-Type検証をスキップすること', function () {
        $middleware = new ForceJsonResponse;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/json');
        // Content-Typeは設定しない

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('DELETEリクエストではContent-Type検証をスキップすること', function () {
        $middleware = new ForceJsonResponse;
        $request = Request::create('/api/users/1', 'DELETE');
        $request->headers->set('Accept', 'application/json');
        // Content-Typeは設定しない

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 204);
        });

        expect($response->getStatusCode())->toBe(204);
    });

    it('Accept: */* の場合はリクエストを通過させること', function () {
        $middleware = new ForceJsonResponse;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', '*/*');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('Accept: application/* の場合はリクエストを通過させること', function () {
        $middleware = new ForceJsonResponse;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/*');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('Accept: application/json, text/html の場合はリクエストを通過させること（複数指定）', function () {
        $middleware = new ForceJsonResponse;
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/json, text/html');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });
});
