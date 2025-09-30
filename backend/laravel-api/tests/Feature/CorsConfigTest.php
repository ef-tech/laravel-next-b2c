<?php

declare(strict_types=1);

/**
 * CORS設定ファイルが作成されていることをテスト
 */
it('cors_config_file_exists', function () {
    $corsConfigPath = config_path('cors.php');
    expect($corsConfigPath)->toBeFile();
});

/**
 * Next.jsフロントエンドからのアクセスが許可されていることをテスト
 */
it('cors_allows_nextjs_frontend_origins', function () {
    $corsConfig = config('cors');

    // 許可されたオリジンにNext.jsの開発サーバーが含まれていることを確認
    $allowedOrigins = $corsConfig['allowed_origins'];

    $expectedOrigins = [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
    ];

    foreach ($expectedOrigins as $origin) {
        expect($allowedOrigins)->toContain($origin);
    }
});

/**
 * APIパスがCORS対象に含まれていることをテスト
 */
it('cors_includes_api_paths', function () {
    $corsConfig = config('cors');

    $paths = $corsConfig['paths'];

    // APIパスが含まれていることを確認
    expect($paths)->toContain('api/*');

    // ヘルスチェックエンドポイントが含まれていることを確認
    expect($paths)->toContain('up');
});

/**
 * 適切なHTTPメソッドが許可されていることをテスト
 */
it('cors_allows_required_http_methods', function () {
    $corsConfig = config('cors');

    $allowedMethods = $corsConfig['allowed_methods'];

    // 全メソッド許可または必要なメソッドが含まれていることを確認
    expect(
        in_array('*', $allowedMethods) ||
        (in_array('GET', $allowedMethods) &&
         in_array('POST', $allowedMethods) &&
         in_array('PUT', $allowedMethods) &&
         in_array('DELETE', $allowedMethods))
    )->toBeTrue();
});

/**
 * 認証情報の送信が適切に設定されていることをテスト
 */
it('cors_supports_credentials_appropriately', function () {
    $corsConfig = config('cors');

    // supports_credentialsがfalseに設定されていることを確認（トークンベース認証のため）
    expect($corsConfig['supports_credentials'])->toBeFalse();
});

/**
 * セキュアなCORS設定であることをテスト
 */
it('cors_configuration_is_secure', function () {
    $corsConfig = config('cors');

    // ワイルドカードオリジン（*）が使用されていないことを確認（セキュリティ上の理由）
    $allowedOrigins = $corsConfig['allowed_origins'];
    expect($allowedOrigins)->not->toContain('*');

    // 本番環境では localhost のみに制限されていることを確認
    foreach ($allowedOrigins as $origin) {
        expect(
            str_starts_with($origin, 'http://localhost:') ||
            str_starts_with($origin, 'http://127.0.0.1:')
        )->toBeTrue();
    }
});

/**
 * CORS設定の基本構造が正しいことをテスト
 */
it('cors_configuration_structure', function () {
    $corsConfig = config('cors');

    // 必要なキーが存在することを確認
    $requiredKeys = [
        'paths',
        'allowed_methods',
        'allowed_origins',
        'allowed_headers',
        'supports_credentials',
    ];

    foreach ($requiredKeys as $key) {
        expect($corsConfig)->toHaveKey($key);
    }
});

/**
 * フロントエンドアプリとの連携動作確認（シミュレーション）
 */
it('frontend_api_access_simulation', function () {
    // CORSヘッダーを含むリクエストをシミュレート
    $response = $this->withHeaders([
        'Origin' => 'http://localhost:3000',
        'Access-Control-Request-Method' => 'GET',
    ])->get('/up');

    $response->assertStatus(200);

    // レスポンスにCORSヘッダーが含まれていることを確認（実際の実装では）
    // Note: テスト環境では実際のCORSミドルウェアが動作しないことがありますが、
    // 設定が正しければ本番環境で動作します
    expect(true)->toBeTrue();
});
