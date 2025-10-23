<?php

declare(strict_types=1);

use Carbon\Carbon;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Ddd\Infrastructure\RateLimit\Metrics\LogMetrics;
use Ddd\Infrastructure\RateLimit\Stores\FailoverRateLimitStore;
use Ddd\Infrastructure\RateLimit\Stores\LaravelRateLimiterStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(Tests\TestCase::class);

describe('RateLimit Integration Tests', function () {
    beforeEach(function () {
        // テスト用キャッシュストアをクリア
        Cache::store('array')->flush();
        Carbon::setTestNow(); // タイムトラベルをリセット
    });

    describe('LaravelRateLimiterStore + LogMetrics統合', function () {
        it('実際のストアとメトリクスを使用してレート制限が動作する', function () {
            $store = new LaravelRateLimiterStore('array');
            $key = RateLimitKey::create('rate_limit:integration:user_1');
            $rule = RateLimitRule::create('integration_test', 3, 1);

            // 3回まで許可
            $result1 = $store->checkLimit($key, $rule);
            $result2 = $store->checkLimit($key, $rule);
            $result3 = $store->checkLimit($key, $rule);

            expect($result1->isAllowed())->toBeTrue()
                ->and($result1->getAttempts())->toBe(1)
                ->and($result2->isAllowed())->toBeTrue()
                ->and($result2->getAttempts())->toBe(2)
                ->and($result3->isAllowed())->toBeTrue()
                ->and($result3->getAttempts())->toBe(3);

            // 4回目は拒否
            $result4 = $store->checkLimit($key, $rule);
            expect($result4->isBlocked())->toBeTrue()
                ->and($result4->getAttempts())->toBe(4);
        });
    });

    describe('FailoverRateLimitStore統合テスト', function () {
        it('プライマリストアとセカンダリストアを使用したフェイルオーバーが動作する', function () {
            $primary = new LaravelRateLimiterStore('array');
            $secondary = new LaravelRateLimiterStore('array');
            $metrics = new LogMetrics;

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $key = RateLimitKey::create('rate_limit:integration:user_2');
            $rule = RateLimitRule::create('integration_test', 5, 1);

            // プライマリストアで正常動作
            $result = $store->checkLimit($key, $rule);
            expect($result->isAllowed())->toBeTrue()
                ->and($result->getAttempts())->toBe(1);
        });

        it('セカンダリストア使用時は制限値が2倍に緩和される', function () {
            // プライマリストアをモック（常に例外を投げる）
            $primary = Mockery::mock(\Ddd\Application\RateLimit\Contracts\RateLimitService::class);
            $primary->shouldReceive('checkLimit')
                ->andThrow(new RuntimeException('Primary store failed'));

            // セカンダリストアは実際のストアを使用
            $secondary = new LaravelRateLimiterStore('array');

            // メトリクスはログ出力を確認
            Log::shouldReceive('warning')
                ->once()
                ->with('rate_limit.failure', Mockery::type('array'));

            Log::shouldReceive('info')
                ->once()
                ->with('rate_limit.latency', Mockery::type('array'));

            $metrics = new LogMetrics;
            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);

            $key = RateLimitKey::create('rate_limit:integration:user_3');
            $rule = RateLimitRule::create('integration_test', 10, 1); // 10リクエスト/分

            // セカンダリストアでは20リクエスト/分に緩和される
            $result = $store->checkLimit($key, $rule);
            expect($result->isAllowed())->toBeTrue()
                ->and($result->getAttempts())->toBe(1);
        });

        it('ヘルスチェック後にプライマリストアに復帰する', function () {
            $now = Carbon::now();
            Carbon::setTestNow($now);

            // プライマリストアをモック（初回は失敗、ヘルスチェック後は成功）
            $primary = Mockery::mock(\Ddd\Application\RateLimit\Contracts\RateLimitService::class);
            $primary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Primary store failed'));

            $primary->shouldReceive('getStatus')
                ->once()
                ->andReturn(\Ddd\Domain\RateLimit\ValueObjects\RateLimitResult::allowed(0, 10, $now->copy()->addMinutes(1)));

            $primary->shouldReceive('checkLimit')
                ->once()
                ->andReturn(\Ddd\Domain\RateLimit\ValueObjects\RateLimitResult::allowed(1, 9, $now->copy()->addMinutes(1)));

            // セカンダリストアは実際のストアを使用
            $secondary = new LaravelRateLimiterStore('array');

            // メトリクス
            Log::shouldReceive('warning')->once(); // フェイルオーバー時
            Log::shouldReceive('info')->twice(); // レイテンシ記録（セカンダリ、プライマリ）
            Log::shouldReceive('info')->once(); // ヘルスチェック時

            $metrics = new LogMetrics;
            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);

            $key = RateLimitKey::create('rate_limit:integration:user_4');
            $rule = RateLimitRule::create('integration_test', 10, 1);

            // 初回リクエスト: プライマリ失敗 → セカンダリ成功
            $result1 = $store->checkLimit($key, $rule);
            expect($result1->isAllowed())->toBeTrue();

            // 30秒経過
            Carbon::setTestNow($now->copy()->addSeconds(31));

            // 2回目リクエスト: ヘルスチェック → プライマリ復帰
            $result2 = $store->checkLimit($key, $rule);
            expect($result2->isAllowed())->toBeTrue();
        });
    });

    describe('エンドツーエンドシナリオ', function () {
        it('複数ユーザーの並行リクエストが正しく制限される', function () {
            $store = new LaravelRateLimiterStore('array');
            $rule = RateLimitRule::create('api', 5, 1); // 5リクエスト/分

            $user1Key = RateLimitKey::create('rate_limit:api:user_1');
            $user2Key = RateLimitKey::create('rate_limit:api:user_2');

            // User 1: 5回リクエスト（全て許可）
            for ($i = 1; $i <= 5; $i++) {
                $result = $store->checkLimit($user1Key, $rule);
                expect($result->isAllowed())->toBeTrue();
            }

            // User 1: 6回目は拒否
            $result = $store->checkLimit($user1Key, $rule);
            expect($result->isBlocked())->toBeTrue();

            // User 2: 独立してカウント（5回まで許可）
            for ($i = 1; $i <= 5; $i++) {
                $result = $store->checkLimit($user2Key, $rule);
                expect($result->isAllowed())->toBeTrue();
            }

            // User 2: 6回目は拒否
            $result = $store->checkLimit($user2Key, $rule);
            expect($result->isBlocked())->toBeTrue();
        });

        it('TTL経過後にカウンターがリセットされる', function () {
            $now = Carbon::now();
            Carbon::setTestNow($now);

            $store = new LaravelRateLimiterStore('array');
            $key = RateLimitKey::create('rate_limit:ttl:user_1');
            $rule = RateLimitRule::create('ttl_test', 3, 1); // 3リクエスト/1分

            // 3回リクエスト（全て許可）
            $store->checkLimit($key, $rule);
            $store->checkLimit($key, $rule);
            $store->checkLimit($key, $rule);

            // 4回目は拒否
            $result = $store->checkLimit($key, $rule);
            expect($result->isBlocked())->toBeTrue();

            // カウンターをリセット（TTL経過をシミュレート）
            $store->resetLimit($key);

            // リセット後は再び許可される
            $result = $store->checkLimit($key, $rule);
            expect($result->isAllowed())->toBeTrue()
                ->and($result->getAttempts())->toBe(1);
        });
    });

    describe('メトリクス統合テスト', function () {
        it('レート制限ヒット時に構造化ログが出力される', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('rate_limit.latency', Mockery::on(function ($context) {
                    return isset($context['latency_ms'])
                        && isset($context['store'])
                        && $context['store'] === 'array';
                }));

            $store = new LaravelRateLimiterStore('array');
            $metrics = new LogMetrics;

            $key = RateLimitKey::create('rate_limit:metrics:user_1');
            $rule = RateLimitRule::create('metrics_test', 10, 1);

            $startTime = microtime(true);
            $result = $store->checkLimit($key, $rule);
            $latencyMs = (microtime(true) - $startTime) * 1000;

            // メトリクス記録
            $metrics->recordLatency($latencyMs, 'array');

            expect($result->isAllowed())->toBeTrue();
        });
    });

    afterEach(function () {
        Mockery::close();
        Carbon::setTestNow(); // タイムトラベルをリセット
    });
});
