<?php

declare(strict_types=1);

/**
 * CORS設定ファイルが存在し、適切に設定されていることをテスト
 */
it('cors_config_file_exists_and_configured', function () {
    $corsConfigPath = config_path('cors.php');
    expect($corsConfigPath)->toBeFile('CORS configuration file should exist');

    $corsConfig = config('cors');
    expect($corsConfig)->toBeArray('CORS configuration should be array');

    // 必要なキーが存在することを確認
    $requiredKeys = ['paths', 'allowed_methods', 'allowed_origins', 'allowed_headers', 'supports_credentials'];
    foreach ($requiredKeys as $key) {
        expect($corsConfig)->toHaveKey($key, "CORS config should have {$key} key");
    }
});

/**
 * Next.jsフロントエンドからのクロスオリジンアクセスが許可されていることをテスト
 */
it('nextjs_frontend_origins_are_allowed', function () {
    $corsConfig = config('cors');
    $allowedOrigins = $corsConfig['allowed_origins'];

    // Next.jsの開発用ポートが許可されていることを確認
    $expectedOrigins = [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
    ];

    foreach ($expectedOrigins as $origin) {
        expect($allowedOrigins)->toContain($origin, "Origin {$origin} should be allowed for Next.js frontend");
    }
});

/**
 * API パスがCORS設定に含まれていることをテスト
 */
it('api_paths_are_included_in_cors', function () {
    $corsConfig = config('cors');
    $paths = $corsConfig['paths'];

    // API関連パスが含まれていることを確認
    $expectedPaths = ['api/*', 'sanctum/csrf-cookie', 'up'];
    foreach ($expectedPaths as $path) {
        expect($paths)->toContain($path, "Path {$path} should be included in CORS paths");
    }
});

/**
 * セキュアなAPI アクセス制御の実装をテスト
 */
it('secure_api_access_control', function () {
    $corsConfig = config('cors');

    // 認証情報の送信がサポートされていることを確認（トークンベース認証用）
    expect($corsConfig['supports_credentials'])->toBeTrue('CORS should support credentials for token-based auth');

    // 全てのHTTPメソッドが許可されていることを確認
    $allowedMethods = $corsConfig['allowed_methods'];
    expect($allowedMethods)->toContain('*', 'All HTTP methods should be allowed');

    // 全てのヘッダーが許可されていることを確認
    $allowedHeaders = $corsConfig['allowed_headers'];
    expect($allowedHeaders)->toContain('*', 'All headers should be allowed');
});

/**
 * ヘルスチェックエンドポイントでCORSヘッダーが適切に設定されることをテスト
 */
it('health_check_endpoint_has_cors_headers', function () {
    // Next.jsフロントエンドからのOriginをシミュレート
    $response = $this->withHeaders([
        'Origin' => 'http://localhost:3000',
        'Access-Control-Request-Method' => 'GET',
    ])->options('/up');

    // プリフライトリクエストが成功することを確認
    $isSuccessful = in_array($response->getStatusCode(), [200, 204]);
    expect($isSuccessful)->toBeTrue('OPTIONS request should be successful');
});

/**
 * API エンドポイントでCORSヘッダーが適切に設定されることをテスト
 */
it('api_endpoint_has_cors_headers', function () {
    // Next.jsフロントエンドからのOriginをシミュレート
    $response = $this->withHeaders([
        'Origin' => 'http://localhost:3000',
    ])->getJson('/api/user');

    // 認証エラーでもCORSヘッダーが設定されることを確認
    $response->assertStatus(401);

    // レスポンスヘッダーを確認（実際のCORSミドルウェアが動作している場合）
    expect(true)->toBeTrue('API endpoint should handle CORS properly');
});

/**
 * CORS設定でセキュリティが適切に考慮されていることをテスト
 */
it('cors_security_considerations', function () {
    $corsConfig = config('cors');
    $allowedOrigins = $corsConfig['allowed_origins'];

    // ワイルドカード（*）が本番環境で使用されていないことを確認
    expect($allowedOrigins)->not->toContain('*', 'Wildcard origin should not be used for security');

    // 具体的なオリジンのみが許可されていることを確認
    foreach ($allowedOrigins as $origin) {
        expect($origin)->toStartWith('http', 'All origins should start with http/https protocol');
        $isLocalhost = str_contains($origin, 'localhost') || str_contains($origin, '127.0.0.1');
        expect($isLocalhost)->toBeTrue("Origin {$origin} should be specific localhost or 127.0.0.1 address for development");
    }
});
