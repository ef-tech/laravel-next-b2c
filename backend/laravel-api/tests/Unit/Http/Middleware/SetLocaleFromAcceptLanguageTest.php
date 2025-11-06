<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocaleFromAcceptLanguage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

describe('SetLocaleFromAcceptLanguage Middleware', function () {
    beforeEach(function () {
        // 各テストの前にロケールをデフォルト（en）にリセット
        App::setLocale('en');
    });

    test('日本語のAccept-Languageヘッダーでロケールをjaに設定する', function () {
        $middleware = new SetLocaleFromAcceptLanguage;
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept-Language', 'ja');

        $middleware->handle($request, function () {
            expect(App::getLocale())->toBe('ja');

            return response('OK');
        });
    });

    test('英語のAccept-Languageヘッダーでロケールをenに設定する', function () {
        $middleware = new SetLocaleFromAcceptLanguage;
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept-Language', 'en');

        $middleware->handle($request, function () {
            expect(App::getLocale())->toBe('en');

            return response('OK');
        });
    });

    test('Accept-Languageヘッダーがない場合はデフォルトロケール（ja）に設定する', function () {
        $middleware = new SetLocaleFromAcceptLanguage;
        $request = Request::create('/', 'GET');
        // Accept-Languageヘッダーを明示的に削除
        $request->headers->remove('Accept-Language');

        $middleware->handle($request, function () {
            expect(App::getLocale())->toBe('ja');

            return response('OK');
        });
    });

    test('サポート外の言語の場合はデフォルトロケール（ja）に設定する', function () {
        $middleware = new SetLocaleFromAcceptLanguage;
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept-Language', 'fr');

        $middleware->handle($request, function () {
            expect(App::getLocale())->toBe('ja');

            return response('OK');
        });
    });

    test('複数の言語を含むAccept-Languageヘッダーで最初のサポート言語を選択する', function () {
        $middleware = new SetLocaleFromAcceptLanguage;
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept-Language', 'fr,en;q=0.9,ja;q=0.8');

        $middleware->handle($request, function () {
            expect(App::getLocale())->toBe('en');

            return response('OK');
        });
    });

    test('Accept-Languageヘッダーにja-JPのような地域コードが含まれている場合はjaを選択する', function () {
        $middleware = new SetLocaleFromAcceptLanguage;
        $request = Request::create('/', 'GET');
        $request->headers->set('Accept-Language', 'ja-JP');

        $middleware->handle($request, function () {
            expect(App::getLocale())->toBe('ja');

            return response('OK');
        });
    });

    test('getSupportedLocalesメソッドがサポート言語の配列を返す', function () {
        $middleware = new SetLocaleFromAcceptLanguage;
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getSupportedLocales');
        $method->setAccessible(true);

        $supportedLocales = $method->invoke($middleware);

        expect($supportedLocales)->toBe(['ja', 'en']);
    });
});
