<?php

declare(strict_types=1);

use function Pest\Laravel\get;

describe('API V1 Routing', function () {
    test('V1ルートが/api/v1プレフィックスで登録されている', function () {
        // V1ヘルスチェックエンドポイントが存在することを確認
        $response = get('/api/v1/health');

        // ルートが見つかることを確認（404ではなく、実装されていない場合は500やその他のエラー）
        expect($response->status())->not->toBe(404);
    });

    test('V1ルートにX-API-Versionヘッダーが付与される', function () {
        $response = get('/api/v1/health');

        expect($response->headers->get('X-API-Version'))->toBe('v1');
    });

    test('V1ルート名にバージョンプレフィックスが付与される', function () {
        // ルート名が存在することを確認
        $healthRoute = route('v1.health');

        expect($healthRoute)->toContain('/api/v1/health');
    });

    test('V1ルートがAPIミドルウェアグループを継承している', function () {
        $response = get('/api/v1/health');

        // SetRequestIdミドルウェアによりリクエストIDが付与されていることを確認
        expect($response->headers->has('X-Request-Id'))->toBeTrue();
    });
});
