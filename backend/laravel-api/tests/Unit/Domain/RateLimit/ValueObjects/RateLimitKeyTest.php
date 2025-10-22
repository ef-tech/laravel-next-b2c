<?php

declare(strict_types=1);

use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;

describe('RateLimitKey ValueObject', function () {
    describe('正常系', function () {
        it('有効なキーでRateLimitKeyを生成できる', function () {
            $key = RateLimitKey::create('rate_limit:api_public:user_123');

            expect($key)->toBeInstanceOf(RateLimitKey::class)
                ->and($key->getKey())->toBe('rate_limit:api_public:user_123');
        });

        it('getHashedKey()はSHA-256ハッシュ値を返す', function () {
            $keyString = 'rate_limit:api_public:user_123';
            $key = RateLimitKey::create($keyString);

            $expected = hash('sha256', $keyString);

            expect($key->getHashedKey())->toBe($expected)
                ->and(strlen($key->getHashedKey()))->toBe(64);
        });

        it('同じキー文字列は常に同じハッシュ値を返す', function () {
            $key1 = RateLimitKey::create('rate_limit:test:user_1');
            $key2 = RateLimitKey::create('rate_limit:test:user_1');

            expect($key1->getHashedKey())->toBe($key2->getHashedKey());
        });

        it('異なるキー文字列は異なるハッシュ値を返す', function () {
            $key1 = RateLimitKey::create('rate_limit:test:user_1');
            $key2 = RateLimitKey::create('rate_limit:test:user_2');

            expect($key1->getHashedKey())->not->toBe($key2->getHashedKey());
        });

        it('IPアドレスベースのキーを生成できる', function () {
            $key = RateLimitKey::create('rate_limit:public_unauthenticated:ip_192.168.1.1');

            expect($key->getKey())->toBe('rate_limit:public_unauthenticated:ip_192.168.1.1');
        });

        it('User IDベースのキーを生成できる', function () {
            $key = RateLimitKey::create('rate_limit:public_authenticated:user_123');

            expect($key->getKey())->toBe('rate_limit:public_authenticated:user_123');
        });

        it('IP + Emailベースのキーを生成できる', function () {
            $emailHash = hash('sha256', 'user@example.com');
            $keyString = "rate_limit:protected_unauthenticated:ip_192.168.1.1_email_{$emailHash}";
            $key = RateLimitKey::create($keyString);

            expect($key->getKey())->toBe($keyString);
        });

        it('255文字の最大長キーを生成できる', function () {
            $keyString = 'rate_limit:test:'.str_repeat('a', 239);
            $key = RateLimitKey::create($keyString);

            expect(strlen($key->getKey()))->toBe(255);
        });
    });

    describe('異常系', function () {
        it('プレフィックスがrate_limit:で始まらない場合は例外をスローする', function () {
            RateLimitKey::create('invalid_prefix:test');
        })->throws(InvalidArgumentException::class, 'key must start with rate_limit:');

        it('キー文字列が空の場合は例外をスローする', function () {
            RateLimitKey::create('');
        })->throws(InvalidArgumentException::class, 'key must start with rate_limit:');

        it('キー文字列が255文字を超える場合は例外をスローする', function () {
            $keyString = 'rate_limit:test:'.str_repeat('a', 240);
            RateLimitKey::create($keyString);
        })->throws(InvalidArgumentException::class, 'key must not exceed 255 characters');

        it('rate_limit:のみの場合は例外をスローする', function () {
            RateLimitKey::create('rate_limit:');
        })->throws(InvalidArgumentException::class, 'key must have endpoint type and identifier');
    });

    describe('不変性', function () {
        it('readonly propertiesにより変更不可である', function () {
            $key = RateLimitKey::create('rate_limit:test:user_1');

            expect(fn () => $key->key = 'modified')
                ->toThrow(Error::class);
        });
    });

    describe('プライバシー保護', function () {
        it('ハッシュ化により元のUser IDを隠蔽する', function () {
            $key = RateLimitKey::create('rate_limit:test:user_123');
            $hashed = $key->getHashedKey();

            // ハッシュ値には元の "user_123" が含まれない
            expect(str_contains($hashed, 'user_123'))->toBeFalse();
        });

        it('ハッシュ化により元のIPアドレスを隠蔽する', function () {
            $key = RateLimitKey::create('rate_limit:test:ip_192.168.1.1');
            $hashed = $key->getHashedKey();

            // ハッシュ値には元の IPアドレス が含まれない
            expect(str_contains($hashed, '192.168.1.1'))->toBeFalse();
        });

        it('ハッシュ化により元のEmailアドレスを隠蔽する', function () {
            $email = 'user@example.com';
            $keyString = "rate_limit:test:email_{$email}";
            $key = RateLimitKey::create($keyString);
            $hashed = $key->getHashedKey();

            // ハッシュ値には元の Emailアドレス が含まれない
            expect(str_contains($hashed, 'user@example.com'))->toBeFalse();
        });
    });
});
