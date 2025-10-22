<?php

declare(strict_types=1);

use Ddd\Application\RateLimit\Services\EndpointClassifier;
use Ddd\Application\RateLimit\Services\RateLimitConfigManager;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

describe('EndpointClassifier Service', function () {
    beforeEach(function () {
        // 設定をセットアップ
        config()->set('ratelimit', [
            'endpoint_types' => [
                'public_unauthenticated' => [
                    'max_attempts' => 60,
                    'decay_minutes' => 1,
                ],
                'protected_unauthenticated' => [
                    'max_attempts' => 5,
                    'decay_minutes' => 10,
                ],
                'public_authenticated' => [
                    'max_attempts' => 120,
                    'decay_minutes' => 1,
                ],
                'protected_authenticated' => [
                    'max_attempts' => 30,
                    'decay_minutes' => 1,
                ],
            ],
            'default' => [
                'max_attempts' => 30,
                'decay_minutes' => 1,
            ],
            'protected_routes' => [
                'login',
                'register',
                'password.*',
                'admin.*',
                'payment.*',
            ],
        ]);
    });

    describe('正常系 - 4種類のエンドポイント分類', function () {
        it('未認証 + 公開エンドポイント = public_unauthenticated (60 req/min)', function () {
            $request = Request::create('/api/posts', 'GET');
            $route = new Route('GET', '/api/posts', []);
            $route->name('posts.index');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('public_unauthenticated')
                ->and($rule->getMaxAttempts())->toBe(60)
                ->and($rule->getDecayMinutes())->toBe(1);
        });

        it('未認証 + 保護エンドポイント(login) = protected_unauthenticated (5 req/10min)', function () {
            $request = Request::create('/api/login', 'POST');
            $route = new Route('POST', '/api/login', []);
            $route->name('login');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('protected_unauthenticated')
                ->and($rule->getMaxAttempts())->toBe(5)
                ->and($rule->getDecayMinutes())->toBe(10);
        });

        it('認証済み + 公開エンドポイント = public_authenticated (120 req/min)', function () {
            $user = new class
            {
                public int $id = 123;
            };

            $request = Request::create('/api/posts', 'GET');
            $route = new Route('GET', '/api/posts', []);
            $route->name('posts.index');
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => $user);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('public_authenticated')
                ->and($rule->getMaxAttempts())->toBe(120)
                ->and($rule->getDecayMinutes())->toBe(1);
        });

        it('認証済み + 保護エンドポイント(admin) = protected_authenticated (30 req/min)', function () {
            $user = new class
            {
                public int $id = 123;
            };

            $request = Request::create('/api/admin/users', 'GET');
            $route = new Route('GET', '/api/admin/users', []);
            $route->name('admin.users.index');
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => $user);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('protected_authenticated')
                ->and($rule->getMaxAttempts())->toBe(30)
                ->and($rule->getDecayMinutes())->toBe(1);
        });
    });

    describe('保護ルートパターンマッチング', function () {
        it('loginルートは保護エンドポイントと判定される', function () {
            $request = Request::create('/api/login', 'POST');
            $route = new Route('POST', '/api/login', []);
            $route->name('login');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('protected_unauthenticated');
        });

        it('registerルートは保護エンドポイントと判定される', function () {
            $request = Request::create('/api/register', 'POST');
            $route = new Route('POST', '/api/register', []);
            $route->name('register');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('protected_unauthenticated');
        });

        it('password.*パターンは保護エンドポイントと判定される', function () {
            $request = Request::create('/api/password/reset', 'POST');
            $route = new Route('POST', '/api/password/reset', []);
            $route->name('password.reset');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('protected_unauthenticated');
        });

        it('admin.*パターンは保護エンドポイントと判定される', function () {
            $request = Request::create('/api/admin/users', 'GET');
            $route = new Route('GET', '/api/admin/users', []);
            $route->name('admin.users.index');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('protected_unauthenticated');
        });

        it('payment.*パターンは保護エンドポイントと判定される', function () {
            $request = Request::create('/api/payment/checkout', 'POST');
            $route = new Route('POST', '/api/payment/checkout', []);
            $route->name('payment.checkout');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('protected_unauthenticated');
        });

        it('保護パターンに一致しないルートは公開エンドポイントと判定される', function () {
            $request = Request::create('/api/posts', 'GET');
            $route = new Route('GET', '/api/posts', []);
            $route->name('posts.index');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('public_unauthenticated');
        });
    });

    describe('エッジケース', function () {
        it('ルート名がnullの場合は公開エンドポイントと判定される', function () {
            $request = Request::create('/api/unknown', 'GET');
            $route = new Route('GET', '/api/unknown', []);
            // route name is not set (null)
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('public_unauthenticated');
        });

        it('ルートがnullの場合はデフォルトルールを返す', function () {
            $request = Request::create('/api/unknown', 'GET');
            // route is not set (null)

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('public_unauthenticated');
        });

        it('認証状態がnullの場合は未認証として扱われる', function () {
            $request = Request::create('/api/posts', 'GET');
            $route = new Route('GET', '/api/posts', []);
            $route->name('posts.index');
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => null);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('public_unauthenticated');
        });
    });

    describe('カスタム保護ルート設定', function () {
        it('設定ファイルで追加した保護ルートパターンが適用される', function () {
            config()->set('ratelimit.protected_routes', [
                'custom.protected.*',
            ]);

            $request = Request::create('/api/custom/protected/endpoint', 'GET');
            $route = new Route('GET', '/api/custom/protected/endpoint', []);
            $route->name('custom.protected.endpoint');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('protected_unauthenticated');
        });

        it('保護ルート設定が空の場合は全て公開エンドポイントと判定される', function () {
            config()->set('ratelimit.protected_routes', []);

            $request = Request::create('/api/login', 'POST');
            $route = new Route('POST', '/api/login', []);
            $route->name('login');
            $request->setRouteResolver(fn () => $route);

            $configManager = new RateLimitConfigManager;
            $classifier = new EndpointClassifier($configManager);
            $rule = $classifier->classify($request);

            expect($rule->getEndpointType())->toBe('public_unauthenticated');
        });
    });
});
