<?php

declare(strict_types=1);

use Carbon\Carbon;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitResult;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Ddd\Infrastructure\RateLimit\Stores\LaravelRateLimiterStore;
use Illuminate\Support\Facades\Cache;

describe('LaravelRateLimiterStore', function () {
    beforeEach(function () {
        // テスト用キャッシュストア（array）をクリア
        Cache::store('array')->flush();
    });

    describe('checkLimit() - 正常系', function () {
        it('最初のリクエストは許可される', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_1');
            $rule = RateLimitRule::create('test', 60, 1);

            $result = $store->checkLimit($key, $rule);

            expect($result)->toBeInstanceOf(RateLimitResult::class)
                ->and($result->isAllowed())->toBeTrue()
                ->and($result->getAttempts())->toBe(1)
                ->and($result->getRemaining())->toBe(59);
        });

        it('複数回のリクエストで試行回数が増加する', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_2');
            $rule = RateLimitRule::create('test', 10, 1);

            $result1 = $store->checkLimit($key, $rule);
            $result2 = $store->checkLimit($key, $rule);
            $result3 = $store->checkLimit($key, $rule);

            expect($result1->getAttempts())->toBe(1)
                ->and($result1->getRemaining())->toBe(9)
                ->and($result2->getAttempts())->toBe(2)
                ->and($result2->getRemaining())->toBe(8)
                ->and($result3->getAttempts())->toBe(3)
                ->and($result3->getRemaining())->toBe(7);
        });

        it('制限値以内は全て許可される', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_3');
            $rule = RateLimitRule::create('test', 5, 1);

            $results = [];
            for ($i = 0; $i < 5; $i++) {
                $results[] = $store->checkLimit($key, $rule);
            }

            foreach ($results as $result) {
                expect($result->isAllowed())->toBeTrue();
            }
        });

        it('リセット時刻は現在時刻＋decay_minutesである', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_4');
            $rule = RateLimitRule::create('test', 60, 5);

            $now = Carbon::now();
            $result = $store->checkLimit($key, $rule);
            $resetAt = $result->getResetAt();

            // リセット時刻は約5分後（誤差を考慮して4分50秒〜5分10秒）
            $expectedMin = $now->copy()->addMinutes(5)->subSeconds(10);
            $expectedMax = $now->copy()->addMinutes(5)->addSeconds(10);

            expect($resetAt->between($expectedMin, $expectedMax))->toBeTrue();
        });
    });

    describe('checkLimit() - レート制限超過', function () {
        it('制限値を超えたリクエストは拒否される', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_5');
            $rule = RateLimitRule::create('test', 3, 1);

            // 3回まで許可
            $store->checkLimit($key, $rule);
            $store->checkLimit($key, $rule);
            $store->checkLimit($key, $rule);

            // 4回目は拒否
            $result = $store->checkLimit($key, $rule);

            expect($result->isBlocked())->toBeTrue()
                ->and($result->getAttempts())->toBe(4)
                ->and($result->getRemaining())->toBe(0);
        });

        it('制限超過後も試行回数は増加する', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_6');
            $rule = RateLimitRule::create('test', 2, 1);

            $store->checkLimit($key, $rule); // 1
            $store->checkLimit($key, $rule); // 2
            $result1 = $store->checkLimit($key, $rule); // 3 (blocked)
            $result2 = $store->checkLimit($key, $rule); // 4 (blocked)

            expect($result1->getAttempts())->toBe(3)
                ->and($result2->getAttempts())->toBe(4);
        });
    });

    describe('resetLimit()', function () {
        it('カウンターをリセットできる', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_7');
            $rule = RateLimitRule::create('test', 5, 1);

            // 3回リクエスト
            $store->checkLimit($key, $rule);
            $store->checkLimit($key, $rule);
            $store->checkLimit($key, $rule);

            // リセット
            $store->resetLimit($key);

            // リセット後は試行回数1から開始
            $result = $store->checkLimit($key, $rule);
            expect($result->getAttempts())->toBe(1)
                ->and($result->getRemaining())->toBe(4);
        });
    });

    describe('getStatus()', function () {
        it('カウンターを増加させずに状態を取得できる', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_8');
            $rule = RateLimitRule::create('test', 10, 1);

            // 3回リクエスト
            $store->checkLimit($key, $rule);
            $store->checkLimit($key, $rule);
            $store->checkLimit($key, $rule);

            // getStatus()でカウンターを増加させずに状態取得
            $status1 = $store->getStatus($key, $rule);
            $status2 = $store->getStatus($key, $rule);

            // 両方とも試行回数3のまま
            expect($status1->getAttempts())->toBe(3)
                ->and($status2->getAttempts())->toBe(3);
        });

        it('未使用キーの状態は試行回数0として返される', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_9');
            $rule = RateLimitRule::create('test', 10, 1);

            $status = $store->getStatus($key, $rule);

            expect($status->getAttempts())->toBe(0)
                ->and($status->getRemaining())->toBe(10)
                ->and($status->isAllowed())->toBeTrue();
        });
    });

    describe('TTL（Time To Live）', function () {
        it('decay_minutes経過後にカウンターが自動削除される', function () {
            $store = new LaravelRateLimiterStore;
            $key = RateLimitKey::create('rate_limit:test:user_10');
            $rule = RateLimitRule::create('test', 10, 1); // 1分

            // リクエストを実行
            $store->checkLimit($key, $rule);

            // TTLが設定されていることを確認（約60秒）
            $ttl = Cache::store('array')->get($key->getKey().':ttl');
            expect($ttl)->toBeGreaterThan(55)
                ->and($ttl)->toBeLessThanOrEqual(60);
        })->skip('TTL実装の詳細に依存するためスキップ');
    });

    describe('異なるキーの分離', function () {
        it('異なるキーは独立してカウントされる', function () {
            $store = new LaravelRateLimiterStore;
            $key1 = RateLimitKey::create('rate_limit:test:user_11');
            $key2 = RateLimitKey::create('rate_limit:test:user_12');
            $rule = RateLimitRule::create('test', 10, 1);

            // key1に3回リクエスト
            $store->checkLimit($key1, $rule);
            $store->checkLimit($key1, $rule);
            $store->checkLimit($key1, $rule);

            // key2の試行回数は0から開始
            $result = $store->checkLimit($key2, $rule);
            expect($result->getAttempts())->toBe(1);
        });
    });
});
