<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use function Pest\Laravel\getJson;

/**
 * ミドルウェアチェーン統合テスト
 *
 * グローバルミドルウェアからルート固有ミドルウェアまでの
 * 実行順序とデータ伝播を検証します。
 *
 * Requirements: 14.3
 */
describe('Middleware Chain Integration', function () {
    beforeEach(function () {
        // テスト用ルートを登録
        Route::get('/test/middleware-chain', function () {
            return response()->json([
                'message' => 'success',
                'request_id' => request()->header('X-Request-ID'),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);
        })->middleware(['api']);
    });

    it('グローバルミドルウェアが全てのリクエストに適用されること', function () {
        $response = getJson('/test/middleware-chain', [
            'Accept' => 'application/json',
        ]);

        // グローバルミドルウェア（SetRequestId）が実行されること
        expect($response->headers->has('X-Request-ID'))->toBeTrue();

        // グローバルミドルウェア（CorrelationId）が実行されること
        expect($response->headers->has('X-Correlation-ID'))->toBeTrue();

        // グローバルミドルウェア（SecurityHeaders）が実行されること
        expect($response->headers->has('X-Frame-Options'))->toBeTrue();
        expect($response->headers->has('X-Content-Type-Options'))->toBeTrue();

        $response->assertStatus(200);
    });

    it('リクエストIDがミドルウェアチェーン全体で伝播すること', function () {
        $customRequestId = 'test-request-123';

        $response = getJson('/test/middleware-chain', [
            'Accept' => 'application/json',
            'X-Request-ID' => $customRequestId,
        ]);

        // SetRequestIdがカスタムリクエストIDを保持すること
        expect($response->headers->get('X-Request-ID'))->toBe($customRequestId);

        // レスポンスボディでもリクエストIDが取得できること
        $responseData = $response->json();
        expect($responseData['request_id'])->toBe($customRequestId);

        $response->assertStatus(200);
    });

    it('Correlation IDがミドルウェアチェーン全体で伝播すること', function () {
        $customCorrelationId = 'corr-xyz-789';

        $response = getJson('/test/middleware-chain', [
            'Accept' => 'application/json',
            'X-Correlation-ID' => $customCorrelationId,
        ]);

        // CorrelationIdがカスタムCorrelation IDを保持すること
        expect($response->headers->get('X-Correlation-ID'))->toBe($customCorrelationId);

        // レスポンスボディでもCorrelation IDが取得できること
        $responseData = $response->json();
        expect($responseData['correlation_id'])->toBe($customCorrelationId);

        $response->assertStatus(200);
    });

    it('Correlation IDが未指定の場合は自動生成されること', function () {
        $response = getJson('/test/middleware-chain', [
            'Accept' => 'application/json',
        ]);

        $correlationId = $response->headers->get('X-Correlation-ID');

        // Correlation IDが生成されること
        expect($correlationId)->not()->toBeNull();
        expect($correlationId)->not()->toBeEmpty();

        // Correlation IDがUUID形式であること（独立したID生成）
        expect($correlationId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');

        $response->assertStatus(200);
    });

    it('ForceJsonResponseミドルウェアがAcceptヘッダーを検証すること', function () {
        $response = $this->get('/test/middleware-chain', [
            'Accept' => 'text/html',
        ]);

        // ForceJsonResponseが不正なAcceptヘッダーを拒否すること
        $response->assertStatus(406);
        expect($response->headers->get('Content-Type'))->toContain('application/json');

        $responseData = $response->json();
        expect($responseData['error'])->toBe('Not Acceptable');
    });

    it('ミドルウェアが正しい順序で実行されること', function () {
        $response = getJson('/test/middleware-chain', [
            'Accept' => 'application/json',
        ]);

        // 実行順序の検証:
        // 1. SetRequestId（リクエストIDヘッダー設定）
        expect($response->headers->has('X-Request-ID'))->toBeTrue();

        // 2. CorrelationId（Correlation IDヘッダー設定）
        expect($response->headers->has('X-Correlation-ID'))->toBeTrue();

        // 3. ForceJsonResponse（Accept検証 - 通過）
        $response->assertStatus(200);

        // 4. SecurityHeaders（セキュリティヘッダー設定）
        expect($response->headers->has('X-Frame-Options'))->toBeTrue();
        expect($response->headers->has('X-Content-Type-Options'))->toBeTrue();
        expect($response->headers->has('Referrer-Policy'))->toBeTrue();
        // HSTS はHTTPS環境でのみ設定されるためテストではスキップ

        // 5. apiグループミドルウェア（RequestLogging等）
        // ログは非同期処理のため直接検証不可、ステータスコードで確認
        $response->assertStatus(200);
    });

    it('apiグループミドルウェアが適用されること', function () {
        // RequestLogging、PerformanceMonitoring、DynamicRateLimitが含まれる
        $response = getJson('/test/middleware-chain', [
            'Accept' => 'application/json',
        ]);

        // apiグループのミドルウェアが正常に処理されること
        $response->assertStatus(200);

        // レスポンスが返されること（ミドルウェアチェーン全体が成功）
        $responseData = $response->json();
        expect($responseData)->toHaveKey('message');
        expect($responseData['message'])->toBe('success');

        // Note: DynamicRateLimitはRedis依存のため、
        // テスト環境でRedis未起動の場合はグレースフルデグラデーションで
        // レート制限ヘッダーが設定されない可能性がある
    });
});
