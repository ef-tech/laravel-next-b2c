<?php

declare(strict_types=1);

use Ddd\Application\RateLimit\Services\KeyResolver;
use Ddd\Domain\RateLimit\ValueObjects\EndpointClassification;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

describe('KeyResolver Service', function () {
    describe('public_unauthenticated エンドポイント - IPアドレスベース', function () {
        it('未認証リクエストのIPアドレスからキーを生成できる', function () {
            $request = Request::create('/api/posts', 'GET');
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('public_unauthenticated', 60, 1);
            $classification = EndpointClassification::create('public_unauthenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            expect($key)->toBeInstanceOf(RateLimitKey::class)
                ->and($key->getKey())->toBe('rate_limit:public_unauthenticated:ip_192.168.1.100');
        });

        it('異なるIPアドレスは異なるキーを生成する', function () {
            $request1 = Request::create('/api/posts', 'GET');
            $request1->server->set('REMOTE_ADDR', '192.168.1.100');

            $request2 = Request::create('/api/posts', 'GET');
            $request2->server->set('REMOTE_ADDR', '192.168.1.200');

            $rule = RateLimitRule::create('public_unauthenticated', 60, 1);
            $classification = EndpointClassification::create('public_unauthenticated', $rule);
            $resolver = new KeyResolver;

            $key1 = $resolver->resolve($request1, $classification);
            $key2 = $resolver->resolve($request2, $classification);

            expect($key1->getKey())->not->toBe($key2->getKey());
        });
    });

    describe('protected_unauthenticated エンドポイント - IP + Emailベース', function () {
        it('ログインリクエストのIP + EmailからSHA-256ハッシュキーを生成できる', function () {
            $request = Request::create('/api/login', 'POST', ['email' => 'user@example.com']);
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('protected_unauthenticated', 5, 10);
            $classification = EndpointClassification::create('protected_unauthenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            $expectedEmailHash = hash('sha256', 'user@example.com');
            $expectedKey = "rate_limit:protected_unauthenticated:ip_192.168.1.100_email_{$expectedEmailHash}";

            expect($key)->toBeInstanceOf(RateLimitKey::class)
                ->and($key->getKey())->toBe($expectedKey);
        });

        it('同じIP + Emailの組み合わせは同じキーを生成する', function () {
            $request1 = Request::create('/api/login', 'POST', ['email' => 'user@example.com']);
            $request1->server->set('REMOTE_ADDR', '192.168.1.100');

            $request2 = Request::create('/api/login', 'POST', ['email' => 'user@example.com']);
            $request2->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('protected_unauthenticated', 5, 10);
            $classification = EndpointClassification::create('protected_unauthenticated', $rule);
            $resolver = new KeyResolver;

            $key1 = $resolver->resolve($request1, $classification);
            $key2 = $resolver->resolve($request2, $classification);

            expect($key1->getKey())->toBe($key2->getKey());
        });

        it('異なるEmailは異なるキーを生成する（同一IP）', function () {
            $request1 = Request::create('/api/login', 'POST', ['email' => 'user1@example.com']);
            $request1->server->set('REMOTE_ADDR', '192.168.1.100');

            $request2 = Request::create('/api/login', 'POST', ['email' => 'user2@example.com']);
            $request2->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('protected_unauthenticated', 5, 10);
            $classification = EndpointClassification::create('protected_unauthenticated', $rule);
            $resolver = new KeyResolver;

            $key1 = $resolver->resolve($request1, $classification);
            $key2 = $resolver->resolve($request2, $classification);

            expect($key1->getKey())->not->toBe($key2->getKey());
        });

        it('Email未送信時はunknownとして扱われる', function () {
            $request = Request::create('/api/login', 'POST');
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('protected_unauthenticated', 5, 10);
            $classification = EndpointClassification::create('protected_unauthenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            $expectedEmailHash = hash('sha256', 'unknown');
            $expectedKey = "rate_limit:protected_unauthenticated:ip_192.168.1.100_email_{$expectedEmailHash}";

            expect($key->getKey())->toBe($expectedKey);
        });
    });

    describe('public_authenticated エンドポイント - User IDベース', function () {
        it('認証済みユーザーのUser IDからキーを生成できる', function () {
            $user = new class
            {
                public int $id = 123;
            };

            $request = Request::create('/api/posts', 'GET');
            $request->setUserResolver(fn () => $user);

            $rule = RateLimitRule::create('public_authenticated', 120, 1);
            $classification = EndpointClassification::create('public_authenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            expect($key)->toBeInstanceOf(RateLimitKey::class)
                ->and($key->getKey())->toBe('rate_limit:public_authenticated:user_123');
        });

        it('異なるUser IDは異なるキーを生成する', function () {
            $user1 = new class
            {
                public int $id = 123;
            };
            $user2 = new class
            {
                public int $id = 456;
            };

            $request1 = Request::create('/api/posts', 'GET');
            $request1->setUserResolver(fn () => $user1);

            $request2 = Request::create('/api/posts', 'GET');
            $request2->setUserResolver(fn () => $user2);

            $rule = RateLimitRule::create('public_authenticated', 120, 1);
            $classification = EndpointClassification::create('public_authenticated', $rule);
            $resolver = new KeyResolver;

            $key1 = $resolver->resolve($request1, $classification);
            $key2 = $resolver->resolve($request2, $classification);

            expect($key1->getKey())->not->toBe($key2->getKey());
        });
    });

    describe('protected_authenticated エンドポイント - User IDベース', function () {
        it('認証済みユーザーのUser IDからキーを生成できる', function () {
            $user = new class
            {
                public int $id = 123;
            };

            $request = Request::create('/api/admin/users', 'GET');
            $request->setUserResolver(fn () => $user);

            $rule = RateLimitRule::create('protected_authenticated', 30, 1);
            $classification = EndpointClassification::create('protected_authenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            expect($key)->toBeInstanceOf(RateLimitKey::class)
                ->and($key->getKey())->toBe('rate_limit:protected_authenticated:user_123');
        });
    });

    describe('User ID → Token ID → IP フォールバックチェーン', function () {
        it('User IDが取得できる場合はUser IDを優先する', function () {
            $user = new class
            {
                public int $id = 123;

                public function currentAccessToken(): ?PersonalAccessToken
                {
                    $token = new PersonalAccessToken;
                    $token->id = 999;

                    return $token;
                }
            };

            $request = Request::create('/api/posts', 'GET');
            $request->setUserResolver(fn () => $user);
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('public_authenticated', 120, 1);
            $classification = EndpointClassification::create('public_authenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            // User ID が優先される（Token IDではない）
            expect($key->getKey())->toBe('rate_limit:public_authenticated:user_123');
        });

        it('User IDが取得できない場合はToken IDにフォールバックする', function () {
            $user = new class
            {
                public ?int $id = null;

                public function currentAccessToken(): ?PersonalAccessToken
                {
                    $token = new PersonalAccessToken;
                    $token->id = 999;

                    return $token;
                }
            };

            $request = Request::create('/api/posts', 'GET');
            $request->setUserResolver(fn () => $user);
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('public_authenticated', 120, 1);
            $classification = EndpointClassification::create('public_authenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            // Token IDにフォールバック
            expect($key->getKey())->toBe('rate_limit:public_authenticated:token_999');
        });

        it('User IDもToken IDも取得できない場合はIPアドレスにフォールバックする', function () {
            $user = new class
            {
                public ?int $id = null;

                public function currentAccessToken(): ?PersonalAccessToken
                {
                    return null;
                }
            };

            $request = Request::create('/api/posts', 'GET');
            $request->setUserResolver(fn () => $user);
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('public_authenticated', 120, 1);
            $classification = EndpointClassification::create('public_authenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            // IPアドレスにフォールバック
            expect($key->getKey())->toBe('rate_limit:public_authenticated:ip_192.168.1.100');
        });

        it('User自体がnullの場合はIPアドレスにフォールバックする', function () {
            $request = Request::create('/api/posts', 'GET');
            $request->setUserResolver(fn () => null);
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('public_authenticated', 120, 1);
            $classification = EndpointClassification::create('public_authenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            // IPアドレスにフォールバック
            expect($key->getKey())->toBe('rate_limit:public_authenticated:ip_192.168.1.100');
        });
    });

    describe('デフォルトエンドポイント - IPアドレスベース', function () {
        it('未定義エンドポイントタイプはIPアドレスベースのキーを生成する', function () {
            $request = Request::create('/api/unknown', 'GET');
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('default', 30, 1);
            $classification = EndpointClassification::create('default', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            expect($key)->toBeInstanceOf(RateLimitKey::class)
                ->and($key->getKey())->toBe('rate_limit:default:ip_192.168.1.100');
        });
    });

    describe('プライバシー保護', function () {
        it('getHashedKey()でSHA-256ハッシュ化されたキーを取得できる', function () {
            $user = new class
            {
                public int $id = 123;
            };

            $request = Request::create('/api/posts', 'GET');
            $request->setUserResolver(fn () => $user);

            $rule = RateLimitRule::create('public_authenticated', 120, 1);
            $classification = EndpointClassification::create('public_authenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            $hashedKey = $key->getHashedKey();

            // ハッシュ値には元の User ID が含まれない
            expect($hashedKey)->not->toContain('123')
                ->and(strlen($hashedKey))->toBe(64); // SHA-256 is 64 hex chars
        });

        it('EmailアドレスがSHA-256ハッシュ化されてキーに含まれる', function () {
            $request = Request::create('/api/login', 'POST', ['email' => 'user@example.com']);
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $rule = RateLimitRule::create('protected_unauthenticated', 5, 10);
            $classification = EndpointClassification::create('protected_unauthenticated', $rule);
            $resolver = new KeyResolver;
            $key = $resolver->resolve($request, $classification);

            // キーに元のEmailアドレスは含まれない（ハッシュ化されている）
            expect($key->getKey())->not->toContain('user@example.com')
                ->and($key->getKey())->toContain('email_'); // but contains email_ prefix
        });
    });
});
