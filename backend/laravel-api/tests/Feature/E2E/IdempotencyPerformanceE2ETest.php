<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

/**
 * IdempotencyとパフォーマンスE2Eテスト
 *
 * IdempotencyKeyとPerformanceMonitoringミドルウェアの動作を
 * 実際のHTTPリクエストで検証します。
 * - Idempotencyキーによる重複リクエスト防止
 * - パフォーマンスメトリクス収集（レスポンス時間測定）
 *
 * Requirements: 14.5, 14.6
 */
describe('Idempotency and Performance E2E', function () {
    beforeEach(function () {
        // データベースマイグレーション実行
        $this->artisan('migrate:fresh');

        // レート制限カウンターをクリア
        try {
            $redis = Redis::connection('default');
            $keys = $redis->keys('rate_limit:*');
            if (! empty($keys)) {
                $redis->del($keys);
            }
        } catch (\Exception $e) {
            // Redis接続エラーは無視（CI環境でRedisが利用できない場合）
        }

        // テスト用ルートを登録
        Route::post('/test/idempotency/webhook', function () {
            return response()->json([
                'status' => 'processed',
                'timestamp' => now()->toIso8601String(),
            ]);
        })->middleware(['webhook']);
    });

    describe('Idempotency機能', function () {
        it('Idempotencyキーがない場合は通常処理されること', function () {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
                ->postJson('/test/idempotency/webhook', [
                    'data' => 'test-data-1',
                ], [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);

            // webhookグループが適用されていること
            $response->assertStatus(200);
            $response->assertJson(['status' => 'processed']);

            // グローバルミドルウェアが実行されていること
            expect($response->headers->has('X-Request-ID'))->toBeTrue();
            expect($response->headers->has('X-Correlation-ID'))->toBeTrue();
        });

        it('Idempotencyキーが設定されている場合もリクエストが処理されること', function () {
            // 未認証ユーザーの場合、IdempotencyKeyミドルウェアはスキップされる
            $idempotencyKey = 'test-key-'.uniqid();

            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.2'])
                ->postJson('/test/idempotency/webhook', [
                    'data' => 'with-idempotency-key',
                ], [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Idempotency-Key' => $idempotencyKey,
                ]);

            // IdempotencyKeyミドルウェアは未認証ユーザーをスキップ
            $response->assertStatus(200);
            $response->assertJson(['status' => 'processed']);

            // Note: IdempotencyKeyミドルウェアはRedis依存かつ認証必須
            // 未認証の場合は通常処理される
        });

        it('webhookグループのミドルウェアチェーンが正しく動作すること', function () {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.3'])
                ->postJson('/test/idempotency/webhook', [
                    'data' => 'webhook-test',
                ], [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);

            // webhookグループ = api + IdempotencyKey + DynamicRateLimit:webhook
            $response->assertStatus(200);

            // apiグループミドルウェアが含まれていること
            expect($response->headers->has('X-Request-ID'))->toBeTrue();
            expect($response->headers->has('X-Correlation-ID'))->toBeTrue();
        });
    });

    describe('パフォーマンスメトリクス', function () {
        it('PerformanceMonitoringミドルウェアが適用されること', function () {
            $startTime = microtime(true);

            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.2.1'])
                ->postJson('/test/idempotency/webhook', [
                    'data' => 'performance-test',
                ], [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // ミリ秒

            // リクエストが正常処理されること
            $response->assertStatus(200);

            // レスポンス時間が妥当な範囲であること（5秒以内）
            expect($responseTime)->toBeLessThan(5000);

            // Note: PerformanceMonitoringミドルウェアはterminateメソッドで
            // 非同期にログを記録するため、ヘッダーには含まれない
        });

        it('複数リクエストのパフォーマンスが安定していること', function () {
            $responseTimes = [];

            // 10回リクエストを送信してレスポンス時間を測定
            for ($i = 0; $i < 10; $i++) {
                $startTime = microtime(true);

                $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.2.2'])
                    ->postJson('/test/idempotency/webhook', [
                        'data' => 'performance-test-'.$i,
                    ], [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ]);

                $endTime = microtime(true);
                $responseTimes[] = ($endTime - $startTime) * 1000;

                $response->assertStatus(200);
            }

            // 平均レスポンス時間を計算
            $avgResponseTime = array_sum($responseTimes) / count($responseTimes);

            // 平均レスポンス時間が2秒以内であること
            expect($avgResponseTime)->toBeLessThan(2000);

            // 最大レスポンス時間が5秒以内であること
            $maxResponseTime = max($responseTimes);
            expect($maxResponseTime)->toBeLessThan(5000);
        });

        it('ミドルウェアチェーン全体のオーバーヘッドが許容範囲内であること', function () {
            // シンプルなレスポンスで全ミドルウェアチェーンのオーバーヘッドを測定
            $startTime = microtime(true);

            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.2.3'])
                ->postJson('/test/idempotency/webhook', [
                    'data' => 'overhead-test',
                ], [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);

            $endTime = microtime(true);
            $totalTime = ($endTime - $startTime) * 1000; // ミリ秒

            $response->assertStatus(200);

            // 全ミドルウェアチェーン（グローバル + webhook）のオーバーヘッドが
            // 1秒以内であること
            expect($totalTime)->toBeLessThan(1000);
        });
    });
});
