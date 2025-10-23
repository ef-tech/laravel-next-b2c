<?php

declare(strict_types=1);

use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;

describe('RateLimitRule ValueObject', function () {
    describe('正常系', function () {
        it('有効な値でRateLimitRuleを生成できる', function () {
            $rule = RateLimitRule::create('api_public', 60, 1);

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('api_public')
                ->and($rule->getMaxAttempts())->toBe(60)
                ->and($rule->getDecayMinutes())->toBe(1)
                ->and($rule->getDecaySeconds())->toBe(60);
        });

        it('最小値（1, 1）でRateLimitRuleを生成できる', function () {
            $rule = RateLimitRule::create('test', 1, 1);

            expect($rule->getMaxAttempts())->toBe(1)
                ->and($rule->getDecayMinutes())->toBe(1);
        });

        it('最大値（10000, 60）でRateLimitRuleを生成できる', function () {
            $rule = RateLimitRule::create('test', 10000, 60);

            expect($rule->getMaxAttempts())->toBe(10000)
                ->and($rule->getDecayMinutes())->toBe(60)
                ->and($rule->getDecaySeconds())->toBe(3600);
        });

        it('4種類のエンドポイントタイプをサポートする', function () {
            $types = [
                'public_unauthenticated',
                'protected_unauthenticated',
                'public_authenticated',
                'protected_authenticated',
            ];

            foreach ($types as $type) {
                $rule = RateLimitRule::create($type, 60, 1);
                expect($rule->getEndpointType())->toBe($type);
            }
        });
    });

    describe('異常系', function () {
        it('maxAttemptsが0の場合は例外をスローする', function () {
            RateLimitRule::create('test', 0, 1);
        })->throws(InvalidArgumentException::class, 'maxAttempts must be between 1 and 10000');

        it('maxAttemptsが10001の場合は例外をスローする', function () {
            RateLimitRule::create('test', 10001, 1);
        })->throws(InvalidArgumentException::class, 'maxAttempts must be between 1 and 10000');

        it('decayMinutesが0の場合は例外をスローする', function () {
            RateLimitRule::create('test', 60, 0);
        })->throws(InvalidArgumentException::class, 'decayMinutes must be between 1 and 60');

        it('decayMinutesが61の場合は例外をスローする', function () {
            RateLimitRule::create('test', 60, 61);
        })->throws(InvalidArgumentException::class, 'decayMinutes must be between 1 and 60');

        it('endpointTypeが空文字列の場合は例外をスローする', function () {
            RateLimitRule::create('', 60, 1);
        })->throws(InvalidArgumentException::class, 'endpointType cannot be empty');

        it('maxAttemptsが負の値の場合は例外をスローする', function () {
            RateLimitRule::create('test', -1, 1);
        })->throws(InvalidArgumentException::class, 'maxAttempts must be between 1 and 10000');

        it('decayMinutesが負の値の場合は例外をスローする', function () {
            RateLimitRule::create('test', 60, -1);
        })->throws(InvalidArgumentException::class, 'decayMinutes must be between 1 and 60');
    });

    describe('不変性', function () {
        it('readonly propertiesにより変更不可である', function () {
            $rule = RateLimitRule::create('api_public', 60, 1);

            // PHP 8.4のreadonly propertyは変更しようとするとエラーになる
            expect(fn () => $rule->endpointType = 'modified')
                ->toThrow(Error::class);
        });
    });

    describe('秒単位変換', function () {
        it('getDecaySeconds()は分を秒に変換する', function () {
            $rule = RateLimitRule::create('test', 60, 5);

            expect($rule->getDecaySeconds())->toBe(300);
        });

        it('1分は60秒である', function () {
            $rule = RateLimitRule::create('test', 60, 1);

            expect($rule->getDecaySeconds())->toBe(60);
        });

        it('60分は3600秒である', function () {
            $rule = RateLimitRule::create('test', 60, 60);

            expect($rule->getDecaySeconds())->toBe(3600);
        });
    });
});
