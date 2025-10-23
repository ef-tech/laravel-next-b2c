<?php

declare(strict_types=1);

use Ddd\Application\RateLimit\Services\RateLimitConfigManager;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;

describe('RateLimitConfigManager Service', function () {
    beforeEach(function () {
        // 設定をリセット
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
        ]);
    });

    describe('正常系 - 設定読み込み', function () {
        it('public_unauthenticatedのルールを取得できる', function () {
            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_unauthenticated');

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('public_unauthenticated')
                ->and($rule->getMaxAttempts())->toBe(60)
                ->and($rule->getDecayMinutes())->toBe(1);
        });

        it('protected_unauthenticatedのルールを取得できる', function () {
            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('protected_unauthenticated');

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('protected_unauthenticated')
                ->and($rule->getMaxAttempts())->toBe(5)
                ->and($rule->getDecayMinutes())->toBe(10);
        });

        it('public_authenticatedのルールを取得できる', function () {
            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_authenticated');

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('public_authenticated')
                ->and($rule->getMaxAttempts())->toBe(120)
                ->and($rule->getDecayMinutes())->toBe(1);
        });

        it('protected_authenticatedのルールを取得できる', function () {
            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('protected_authenticated');

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('protected_authenticated')
                ->and($rule->getMaxAttempts())->toBe(30)
                ->and($rule->getDecayMinutes())->toBe(1);
        });
    });

    describe('デフォルトルール取得', function () {
        it('未定義のエンドポイントタイプはデフォルトルールを返す', function () {
            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('unknown_type');

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('default')
                ->and($rule->getMaxAttempts())->toBe(30)
                ->and($rule->getDecayMinutes())->toBe(1);
        });

        it('getDefaultRule()で明示的にデフォルトルールを取得できる', function () {
            $manager = new RateLimitConfigManager;
            $rule = $manager->getDefaultRule();

            expect($rule)->toBeInstanceOf(RateLimitRule::class)
                ->and($rule->getEndpointType())->toBe('default')
                ->and($rule->getMaxAttempts())->toBe(30)
                ->and($rule->getDecayMinutes())->toBe(1);
        });
    });

    describe('環境変数による上書き', function () {
        it('環境変数でmax_attemptsを上書きできる', function () {
            config()->set('ratelimit.endpoint_types.public_unauthenticated.max_attempts', 100);

            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_unauthenticated');

            expect($rule->getMaxAttempts())->toBe(100);
        });

        it('環境変数でdecay_minutesを上書きできる', function () {
            config()->set('ratelimit.endpoint_types.public_unauthenticated.decay_minutes', 5);

            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_unauthenticated');

            expect($rule->getDecayMinutes())->toBe(5);
        });

        it('環境変数でデフォルトルールを上書きできる', function () {
            config()->set('ratelimit.default', [
                'max_attempts' => 10,
                'decay_minutes' => 5,
            ]);

            $manager = new RateLimitConfigManager;
            $rule = $manager->getDefaultRule();

            expect($rule->getMaxAttempts())->toBe(10)
                ->and($rule->getDecayMinutes())->toBe(5);
        });
    });

    describe('型変換', function () {
        it('文字列型のmax_attemptsを整数に変換できる', function () {
            config()->set('ratelimit.endpoint_types.public_unauthenticated.max_attempts', '50');

            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_unauthenticated');

            expect($rule->getMaxAttempts())->toBe(50)
                ->and($rule->getMaxAttempts())->toBeInt();
        });

        it('文字列型のdecay_minutesを整数に変換できる', function () {
            config()->set('ratelimit.endpoint_types.public_unauthenticated.decay_minutes', '3');

            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_unauthenticated');

            expect($rule->getDecayMinutes())->toBe(3)
                ->and($rule->getDecayMinutes())->toBeInt();
        });
    });

    describe('キャッシング', function () {
        it('同じエンドポイントタイプを複数回取得しても同じインスタンスを返す', function () {
            $manager = new RateLimitConfigManager;
            $rule1 = $manager->getRule('public_unauthenticated');
            $rule2 = $manager->getRule('public_unauthenticated');

            expect($rule1)->toBe($rule2); // 同一インスタンス
        });

        it('異なるエンドポイントタイプは異なるインスタンスを返す', function () {
            $manager = new RateLimitConfigManager;
            $rule1 = $manager->getRule('public_unauthenticated');
            $rule2 = $manager->getRule('protected_unauthenticated');

            expect($rule1)->not->toBe($rule2);
        });

        it('デフォルトルールもキャッシュされる', function () {
            $manager = new RateLimitConfigManager;
            $rule1 = $manager->getDefaultRule();
            $rule2 = $manager->getDefaultRule();

            expect($rule1)->toBe($rule2);
        });
    });

    describe('全エンドポイントタイプ取得', function () {
        it('getAllRules()で4種類のエンドポイントタイプを取得できる', function () {
            $manager = new RateLimitConfigManager;
            $rules = $manager->getAllRules();

            expect($rules)->toBeArray()
                ->and($rules)->toHaveCount(4)
                ->and($rules)->toHaveKeys([
                    'public_unauthenticated',
                    'protected_unauthenticated',
                    'public_authenticated',
                    'protected_authenticated',
                ]);
        });

        it('getAllRules()の各要素はRateLimitRuleインスタンスである', function () {
            $manager = new RateLimitConfigManager;
            $rules = $manager->getAllRules();

            foreach ($rules as $rule) {
                expect($rule)->toBeInstanceOf(RateLimitRule::class);
            }
        });
    });

    describe('異常系', function () {
        it('max_attemptsが未設定の場合はデフォルトルールを返す', function () {
            config()->set('ratelimit.endpoint_types.public_unauthenticated', [
                'decay_minutes' => 1,
            ]);

            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_unauthenticated');

            expect($rule->getEndpointType())->toBe('default')
                ->and($rule->getMaxAttempts())->toBe(30);
        });

        it('decay_minutesが未設定の場合はデフォルトルールを返す', function () {
            config()->set('ratelimit.endpoint_types.public_unauthenticated', [
                'max_attempts' => 60,
            ]);

            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_unauthenticated');

            expect($rule->getEndpointType())->toBe('default')
                ->and($rule->getDecayMinutes())->toBe(1);
        });

        it('設定が完全に欠落している場合はデフォルトルールを返す', function () {
            config()->set('ratelimit', []);

            $manager = new RateLimitConfigManager;
            $rule = $manager->getRule('public_unauthenticated');

            expect($rule->getEndpointType())->toBe('default')
                ->and($rule->getMaxAttempts())->toBe(30)
                ->and($rule->getDecayMinutes())->toBe(1);
        });
    });
});
