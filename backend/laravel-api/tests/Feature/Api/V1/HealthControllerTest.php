<?php

declare(strict_types=1);

use function Pest\Laravel\get;

describe('V1 Health Check API', function () {
    test('ヘルスチェックエンドポイントが正常なレスポンスを返す', function () {
        $response = get('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok'])
            ->assertHeader('X-API-Version', 'v1');
    });

    test('ヘルスチェックレスポンスにtimestampが含まれる', function () {
        $response = get('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'timestamp']);
    });

    test('ヘルスチェックレスポンスにCache-Controlヘッダーが含まれる', function () {
        $response = get('/api/v1/health');

        $response->assertHeader('Cache-Control', 'no-store, private');
    });

    test('ヘルスチェックはレート制限なしでアクセスできる', function () {
        // 連続して10回アクセスしても429エラーにならないことを確認
        for ($i = 0; $i < 10; $i++) {
            $response = get('/api/v1/health');
            expect($response->status())->toBe(200);
        }
    });

    test('ヘルスチェックは認証なしでアクセスできる', function () {
        // 認証トークンなしでアクセス可能
        $response = get('/api/v1/health');

        $response->assertStatus(200);
    });
});
