<?php

declare(strict_types=1);

use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitRule;
use Ddd\Infrastructure\RateLimit\Metrics\LogMetrics;
use Illuminate\Support\Facades\Log;

describe('LogMetrics', function () {
    describe('recordHit()', function () {
        it('レート制限許可時はinfo levelでログ出力する', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('rate_limit.hit', Mockery::on(function ($context) {
                    return $context['key'] === 'rate_limit:test:user_1'
                        && $context['endpoint_type'] === 'test'
                        && $context['max_attempts'] === 60
                        && $context['decay_minutes'] === 1
                        && $context['allowed'] === true
                        && $context['attempts'] === 1;
                }));

            $metrics = new LogMetrics(hashKey: false);
            $key = RateLimitKey::create('rate_limit:test:user_1');
            $rule = RateLimitRule::create('test', 60, 1);

            $metrics->recordHit($key, $rule, true, 1);
        });

        it('レート制限拒否時はwarning levelでログ出力する', function () {
            Log::shouldReceive('warning')
                ->once()
                ->with('rate_limit.hit', Mockery::on(function ($context) {
                    return $context['key'] === 'rate_limit:test:user_2'
                        && $context['endpoint_type'] === 'test'
                        && $context['max_attempts'] === 60
                        && $context['decay_minutes'] === 1
                        && $context['allowed'] === false
                        && $context['attempts'] === 61;
                }));

            $metrics = new LogMetrics(hashKey: false);
            $key = RateLimitKey::create('rate_limit:test:user_2');
            $rule = RateLimitRule::create('test', 60, 1);

            $metrics->recordHit($key, $rule, false, 61);
        });
    });

    describe('recordBlock()', function () {
        it('レート制限ブロック時はerror levelでログ出力する', function () {
            Log::shouldReceive('error')
                ->once()
                ->with('rate_limit.blocked', Mockery::on(function ($context) {
                    return $context['key'] === 'rate_limit:test:user_3'
                        && $context['endpoint_type'] === 'test'
                        && $context['max_attempts'] === 60
                        && $context['decay_minutes'] === 1
                        && $context['attempts'] === 65
                        && $context['retry_after'] === 30;
                }));

            $metrics = new LogMetrics(hashKey: false);
            $key = RateLimitKey::create('rate_limit:test:user_3');
            $rule = RateLimitRule::create('test', 60, 1);

            $metrics->recordBlock($key, $rule, 65, 30);
        });
    });

    describe('recordFailure()', function () {
        it('フェイルオーバー成功時はwarning levelでログ出力する', function () {
            Log::shouldReceive('warning')
                ->once()
                ->with('rate_limit.failure', Mockery::on(function ($context) {
                    return $context['key'] === 'rate_limit:test:user_4'
                        && $context['endpoint_type'] === 'test'
                        && $context['max_attempts'] === 60
                        && $context['decay_minutes'] === 1
                        && $context['error'] === 'Redis connection failed'
                        && $context['failed_over'] === true;
                }));

            $metrics = new LogMetrics(hashKey: false);
            $key = RateLimitKey::create('rate_limit:test:user_4');
            $rule = RateLimitRule::create('test', 60, 1);

            $metrics->recordFailure($key, $rule, 'Redis connection failed', true);
        });

        it('フェイルオーバー失敗時はcritical levelでログ出力する', function () {
            Log::shouldReceive('critical')
                ->once()
                ->with('rate_limit.failure', Mockery::on(function ($context) {
                    return $context['key'] === 'rate_limit:test:user_5'
                        && $context['endpoint_type'] === 'test'
                        && $context['max_attempts'] === 60
                        && $context['decay_minutes'] === 1
                        && $context['error'] === 'Both Redis and Array cache failed'
                        && $context['failed_over'] === false;
                }));

            $metrics = new LogMetrics(hashKey: false);
            $key = RateLimitKey::create('rate_limit:test:user_5');
            $rule = RateLimitRule::create('test', 60, 1);

            $metrics->recordFailure($key, $rule, 'Both Redis and Array cache failed', false);
        });
    });

    describe('recordLatency()', function () {
        it('レイテンシをinfo levelでログ出力する', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('rate_limit.latency', Mockery::on(function ($context) {
                    return $context['latency_ms'] === 1.5
                        && $context['store'] === 'primary';
                }));

            $metrics = new LogMetrics(hashKey: false);
            $metrics->recordLatency(1.5, 'primary');
        });

        it('遅いレイテンシ（10ms以上）はwarning levelでログ出力する', function () {
            Log::shouldReceive('warning')
                ->once()
                ->with('rate_limit.latency', Mockery::on(function ($context) {
                    return $context['latency_ms'] === 15.0
                        && $context['store'] === 'secondary';
                }));

            $metrics = new LogMetrics(hashKey: false);
            $metrics->recordLatency(15.0, 'secondary');
        });
    });

    describe('プライバシー配慮 - ハッシュ化', function () {
        it('hashKey=trueの場合、キーをハッシュ化してログ出力する', function () {
            $key = RateLimitKey::create('rate_limit:test:user_sensitive');
            $expectedHash = $key->getHashedKey();

            Log::shouldReceive('info')
                ->once()
                ->with('rate_limit.hit', Mockery::on(function ($context) use ($expectedHash) {
                    return $context['key'] === $expectedHash
                        && $context['endpoint_type'] === 'test'
                        && $context['allowed'] === true;
                }));

            $metrics = new LogMetrics(hashKey: true);
            $rule = RateLimitRule::create('test', 60, 1);

            $metrics->recordHit($key, $rule, true, 1);
        });

        it('hashKey=falseの場合、生キーをログ出力する（開発環境用）', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('rate_limit.hit', Mockery::on(function ($context) {
                    return $context['key'] === 'rate_limit:test:user_debug'
                        && $context['endpoint_type'] === 'test'
                        && $context['allowed'] === true;
                }));

            $metrics = new LogMetrics(hashKey: false);
            $key = RateLimitKey::create('rate_limit:test:user_debug');
            $rule = RateLimitRule::create('test', 60, 1);

            $metrics->recordHit($key, $rule, true, 1);
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});
