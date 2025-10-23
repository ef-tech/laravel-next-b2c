<?php

declare(strict_types=1);

use Carbon\Carbon;
use Ddd\Application\RateLimit\Contracts\RateLimitMetrics;
use Ddd\Application\RateLimit\Contracts\RateLimitService;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitResult;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Ddd\Infrastructure\RateLimit\Stores\FailoverRateLimitStore;
use Illuminate\Support\Facades\Cache;

describe('FailoverRateLimitStore', function () {
    beforeEach(function () {
        // テスト用キャッシュストアをクリア
        Cache::store('array')->flush();
    });

    describe('正常系 - プライマリストア動作', function () {
        it('プライマリストアが正常時はプライマリストアを使用する', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_1');
            $rule = RateLimitRule::create('test', 60, 1);
            $result = RateLimitResult::allowed(1, 59, Carbon::now()->addMinutes(1));

            // プライマリストアが正常にレスポンス
            $primary->shouldReceive('checkLimit')
                ->once()
                ->with($key, $rule)
                ->andReturn($result);

            // セカンダリストアは呼ばれない
            $secondary->shouldNotReceive('checkLimit');

            // メトリクス記録（レイテンシのみ）
            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::type('float'), 'primary');

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $actualResult = $store->checkLimit($key, $rule);

            expect($actualResult)->toBe($result);
        });

        it('プライマリストア正常時はgetStatus()もプライマリストアを使用する', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_2');
            $rule = RateLimitRule::create('test', 60, 1);
            $result = RateLimitResult::allowed(3, 57, Carbon::now()->addMinutes(1));

            $primary->shouldReceive('getStatus')
                ->once()
                ->with($key, $rule)
                ->andReturn($result);

            $secondary->shouldNotReceive('getStatus');

            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::type('float'), 'primary');

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $actualResult = $store->getStatus($key, $rule);

            expect($actualResult)->toBe($result);
        });

        it('プライマリストア正常時はresetLimit()もプライマリストアを使用する', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_3');

            $primary->shouldReceive('resetLimit')
                ->once()
                ->with($key);

            $secondary->shouldNotReceive('resetLimit');

            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::type('float'), 'primary');

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $store->resetLimit($key);
        });
    });

    describe('フェイルオーバー - Redis障害検知', function () {
        it('プライマリストアが例外を投げた場合、セカンダリストアにフェイルオーバーする', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_4');
            $rule = RateLimitRule::create('test', 60, 1);
            $resultFromSecondary = RateLimitResult::allowed(1, 119, Carbon::now()->addMinutes(1)); // 2倍制限

            // プライマリストアが例外を投げる
            $primary->shouldReceive('checkLimit')
                ->once()
                ->with($key, $rule)
                ->andThrow(new RuntimeException('Redis connection failed'));

            // セカンダリストアにフェイルオーバー（制限値2倍のルール）
            $secondary->shouldReceive('checkLimit')
                ->once()
                ->with($key, Mockery::on(function ($relaxedRule) use ($rule) {
                    return $relaxedRule->getMaxAttempts() === $rule->getMaxAttempts() * 2
                        && $relaxedRule->getDecayMinutes() === $rule->getDecayMinutes()
                        && $relaxedRule->getEndpointType() === $rule->getEndpointType();
                }))
                ->andReturn($resultFromSecondary);

            // メトリクス記録（障害・レイテンシ）
            $metrics->shouldReceive('recordFailure')
                ->once()
                ->with($key, $rule, 'Redis connection failed', true);

            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::type('float'), 'secondary');

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $actualResult = $store->checkLimit($key, $rule);

            expect($actualResult)->toBe($resultFromSecondary);
        });

        it('フェイルオーバー後の次回リクエストもセカンダリストアを使用する', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_5');
            $rule = RateLimitRule::create('test', 60, 1);

            // 1回目: プライマリ障害 → セカンダリにフェイルオーバー
            $primary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Redis connection failed'));

            $secondary->shouldReceive('checkLimit')
                ->twice() // 1回目のフェイルオーバー + 2回目のリクエスト
                ->andReturn(RateLimitResult::allowed(1, 119, Carbon::now()->addMinutes(1)));

            $metrics->shouldReceive('recordFailure')->once();
            $metrics->shouldReceive('recordLatency')->twice();

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);

            // 1回目のリクエスト（フェイルオーバー）
            $store->checkLimit($key, $rule);

            // 2回目のリクエスト（セカンダリストア継続使用）
            $store->checkLimit($key, $rule);
        });

        it('セカンダリストアも障害の場合は例外を再スローする', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_6');
            $rule = RateLimitRule::create('test', 60, 1);

            // プライマリストア障害
            $primary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Redis connection failed'));

            // セカンダリストアも障害
            $secondary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Array cache failed'));

            $metrics->shouldReceive('recordFailure')
                ->once()
                ->with($key, $rule, 'Array cache failed', false); // failedOver=false

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);

            expect(fn () => $store->checkLimit($key, $rule))
                ->toThrow(RuntimeException::class, 'Array cache failed');
        });
    });

    describe('ヘルスチェック - Redis復旧検知', function () {
        it('30秒ごとにプライマリストアのヘルスチェックを実行する', function () {
            // 時刻を固定
            $now = Carbon::now();
            Carbon::setTestNow($now);

            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $testKey = RateLimitKey::create('rate_limit:health_check');
            $testRule = RateLimitRule::create('health_check', 1, 1);

            // 初回: プライマリ障害 → フェイルオーバー
            $primary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Redis connection failed'));

            $secondary->shouldReceive('checkLimit')
                ->once()
                ->andReturn(RateLimitResult::allowed(1, 119, $now->copy()->addMinutes(1)));

            $metrics->shouldReceive('recordFailure')->once();
            $metrics->shouldReceive('recordLatency')->once();

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $store->checkLimit($testKey, $testRule);

            // 30秒経過後のヘルスチェック（プライマリ復旧確認）
            Carbon::setTestNow($now->copy()->addSeconds(31));

            $primary->shouldReceive('getStatus')
                ->once()
                ->with(Mockery::type(RateLimitKey::class), Mockery::type(RateLimitRule::class))
                ->andReturn(RateLimitResult::allowed(0, 1, $now->copy()->addMinutes(1)));

            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::type('float'), 'primary');

            // 次回リクエストでヘルスチェック実行 → プライマリ復帰
            $primary->shouldReceive('checkLimit')
                ->once()
                ->andReturn(RateLimitResult::allowed(1, 59, $now->copy()->addMinutes(1)));

            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::type('float'), 'primary');

            $store->checkLimit($testKey, $testRule);
        });

        it('ヘルスチェック成功後はプライマリストアに戻る', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_7');
            $rule = RateLimitRule::create('test', 60, 1);

            // フェイルオーバー状態にする
            $primary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Redis connection failed'));

            $secondary->shouldReceive('checkLimit')
                ->once()
                ->andReturn(RateLimitResult::allowed(1, 119, Carbon::now()->addMinutes(1)));

            $metrics->shouldReceive('recordFailure')->once();
            $metrics->shouldReceive('recordLatency')->once();

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $store->checkLimit($key, $rule);

            // 30秒経過 + ヘルスチェック成功
            Carbon::setTestNow(Carbon::now()->addSeconds(30));

            $primary->shouldReceive('getStatus')
                ->once()
                ->andReturn(RateLimitResult::allowed(0, 1, Carbon::now()->addMinutes(1)));

            $metrics->shouldReceive('recordLatency')->once();

            // プライマリ復帰後のリクエスト
            $primary->shouldReceive('checkLimit')
                ->once()
                ->andReturn(RateLimitResult::allowed(1, 59, Carbon::now()->addMinutes(1)));

            $secondary->shouldNotReceive('checkLimit'); // セカンダリは使われない

            $metrics->shouldReceive('recordLatency')->once();

            $store->checkLimit($key, $rule);
        });

        it('ヘルスチェックが失敗した場合はセカンダリストアを継続使用する', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_8');
            $rule = RateLimitRule::create('test', 60, 1);

            // フェイルオーバー状態にする
            $primary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Redis connection failed'));

            $secondary->shouldReceive('checkLimit')
                ->twice() // 初回 + ヘルスチェック失敗後
                ->andReturn(RateLimitResult::allowed(1, 119, Carbon::now()->addMinutes(1)));

            $metrics->shouldReceive('recordFailure')->once();
            $metrics->shouldReceive('recordLatency')->twice();

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $store->checkLimit($key, $rule);

            // 30秒経過 + ヘルスチェック失敗
            Carbon::setTestNow(Carbon::now()->addSeconds(30));

            $primary->shouldReceive('getStatus')
                ->once()
                ->andThrow(new RuntimeException('Redis still down'));

            // セカンダリストア継続使用
            $store->checkLimit($key, $rule);
        });
    });

    describe('制限値の緩和', function () {
        it('セカンダリストア使用時は制限値を2倍に緩和する', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_9');
            $originalRule = RateLimitRule::create('test', 60, 1); // 60リクエスト/分

            $primary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Redis connection failed'));

            // セカンダリストアには120リクエスト/分のルールが渡される
            $secondary->shouldReceive('checkLimit')
                ->once()
                ->with($key, Mockery::on(function ($relaxedRule) {
                    return $relaxedRule->getMaxAttempts() === 120
                        && $relaxedRule->getDecayMinutes() === 1
                        && $relaxedRule->getEndpointType() === 'test';
                }))
                ->andReturn(RateLimitResult::allowed(1, 119, Carbon::now()->addMinutes(1)));

            $metrics->shouldReceive('recordFailure')->once();
            $metrics->shouldReceive('recordLatency')->once();

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $store->checkLimit($key, $originalRule);
        });
    });

    describe('メトリクス記録', function () {
        it('プライマリストア正常時はレイテンシのみ記録する', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_10');
            $rule = RateLimitRule::create('test', 60, 1);

            $primary->shouldReceive('checkLimit')
                ->once()
                ->andReturn(RateLimitResult::allowed(1, 59, Carbon::now()->addMinutes(1)));

            // レイテンシのみ記録（障害記録はなし）
            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::type('float'), 'primary');

            $metrics->shouldNotReceive('recordFailure');

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $store->checkLimit($key, $rule);
        });

        it('フェイルオーバー時は障害記録とレイテンシを記録する', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_11');
            $rule = RateLimitRule::create('test', 60, 1);

            $primary->shouldReceive('checkLimit')
                ->once()
                ->andThrow(new RuntimeException('Redis connection failed'));

            $secondary->shouldReceive('checkLimit')
                ->once()
                ->andReturn(RateLimitResult::allowed(1, 119, Carbon::now()->addMinutes(1)));

            // 障害記録（failedOver=true）
            $metrics->shouldReceive('recordFailure')
                ->once()
                ->with($key, $rule, 'Redis connection failed', true);

            // レイテンシ記録（secondary）
            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::type('float'), 'secondary');

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $store->checkLimit($key, $rule);
        });

        it('レイテンシはミリ秒単位で記録される', function () {
            $primary = Mockery::mock(RateLimitService::class);
            $secondary = Mockery::mock(RateLimitService::class);
            $metrics = Mockery::mock(RateLimitMetrics::class);

            $key = RateLimitKey::create('rate_limit:test:user_12');
            $rule = RateLimitRule::create('test', 60, 1);

            $primary->shouldReceive('checkLimit')
                ->once()
                ->andReturn(RateLimitResult::allowed(1, 59, Carbon::now()->addMinutes(1)));

            // レイテンシは0以上のfloat値
            $metrics->shouldReceive('recordLatency')
                ->once()
                ->with(Mockery::on(function ($latency) {
                    return is_float($latency) && $latency >= 0;
                }), 'primary');

            $store = new FailoverRateLimitStore($primary, $secondary, $metrics);
            $store->checkLimit($key, $rule);
        });
    });

    afterEach(function () {
        Mockery::close();
        Carbon::setTestNow(); // タイムトラベルをリセット
    });
});
