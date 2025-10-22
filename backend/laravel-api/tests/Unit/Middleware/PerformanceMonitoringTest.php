<?php

declare(strict_types=1);

use App\Http\Middleware\PerformanceMonitoring;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PerformanceMonitoring ミドルウェアのテスト
 *
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5
 */
describe('PerformanceMonitoring', function () {
    it('リクエストを通過させること', function () {
        $middleware = new PerformanceMonitoring;
        $request = Request::create('/api/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('terminateメソッドでパフォーマンスメトリクスを出力すること', function () {
        $middleware = new PerformanceMonitoring;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id');

        Log::shouldReceive('channel')
            ->once()
            ->with('monitoring')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Performance metrics', \Mockery::on(function ($context) {
                return isset($context['request_id'])
                    && $context['request_id'] === 'test-request-id'
                    && isset($context['response_time_ms'])
                    && is_numeric($context['response_time_ms'])
                    && $context['response_time_ms'] >= 0
                    && isset($context['peak_memory_mb'])
                    && is_numeric($context['peak_memory_mb'])
                    && $context['peak_memory_mb'] > 0
                    && isset($context['db_queries_count'])
                    && is_int($context['db_queries_count'])
                    && $context['db_queries_count'] >= 0;
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('データベースクエリ実行回数をカウントすること', function () {
        $middleware = new PerformanceMonitoring;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id');

        // DB::listenをモック
        DB::shouldReceive('listen')->once();

        Log::shouldReceive('channel')
            ->once()
            ->with('monitoring')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Performance metrics', \Mockery::on(function ($context) {
                return isset($context['db_queries_count']);
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('レスポンス時間がマイクロ秒精度で測定されること', function () {
        $middleware = new PerformanceMonitoring;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id');

        Log::shouldReceive('channel')
            ->once()
            ->with('monitoring')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Performance metrics', \Mockery::on(function ($context) {
                // レスポンス時間が存在し、1ms以上であることを確認（usleep(1000)分）
                return isset($context['response_time_ms'])
                    && is_numeric($context['response_time_ms'])
                    && $context['response_time_ms'] >= 1.0;
            }));

        $response = $middleware->handle($request, function ($req) {
            usleep(1000); // 1ms待機

            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('ピークメモリ使用量をMB単位で記録すること', function () {
        $middleware = new PerformanceMonitoring;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id');

        Log::shouldReceive('channel')
            ->once()
            ->with('monitoring')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Performance metrics', \Mockery::on(function ($context) {
                return isset($context['peak_memory_mb'])
                    && is_numeric($context['peak_memory_mb'])
                    && $context['peak_memory_mb'] > 0;
            }));

        $response = $middleware->handle($request, function ($req) {
            // メモリを意図的に消費
            $data = str_repeat('x', 1024 * 100); // 100KB

            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('レスポンス時間が閾値を超過した場合にアラートログを記録すること', function () {
        $middleware = new PerformanceMonitoring;
        $request = Request::create('/api/slow-endpoint', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id-slow');

        // 通常のメトリクスログ
        Log::shouldReceive('channel')
            ->once()
            ->with('monitoring')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Performance metrics', \Mockery::any());

        // アラートログ（閾値200msを超過）
        Log::shouldReceive('channel')
            ->once()
            ->with('monitoring')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Performance alert: Response time exceeded threshold', \Mockery::on(function ($context) {
                return isset($context['request_id'])
                    && $context['request_id'] === 'test-request-id-slow'
                    && isset($context['response_time_ms'])
                    && $context['response_time_ms'] > 200
                    && isset($context['threshold_ms'])
                    && $context['threshold_ms'] === 200;
            }));

        $response = $middleware->handle($request, function ($req) {
            usleep(210000); // 210ms待機（閾値200msを超過）

            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('レスポンス時間が閾値以内の場合にアラートログを記録しないこと', function () {
        $middleware = new PerformanceMonitoring;
        $request = Request::create('/api/fast-endpoint', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id-fast');

        // 通常のメトリクスログのみ
        Log::shouldReceive('channel')
            ->once()
            ->with('monitoring')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Performance metrics', \Mockery::on(function ($context) {
                return $context['response_time_ms'] <= 200;
            }));

        // warningは呼ばれないことを期待
        Log::shouldReceive('warning')->never();

        $response = $middleware->handle($request, function ($req) {
            usleep(50000); // 50ms待機（閾値200ms以内）

            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });

    it('パーセンタイル計算に必要な情報を記録すること', function () {
        $middleware = new PerformanceMonitoring;
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Request-Id', 'test-request-id');

        Log::shouldReceive('channel')
            ->once()
            ->with('monitoring')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Performance metrics', \Mockery::on(function ($context) {
                // パーセンタイル計算に必要な情報が含まれているか確認
                return isset($context['method'])
                    && isset($context['url'])
                    && isset($context['response_time_ms'])
                    && isset($context['timestamp']);
            }));

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    });
});
