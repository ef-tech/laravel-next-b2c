<?php

declare(strict_types=1);

/**
 * bootstrap/app.phpがAPI専用ルーティング構成に変更されていることをテスト
 */
it('bootstrap_uses_api_routing', function () {
    $bootstrapPath = base_path('bootstrap/app.php');
    $bootstrapContent = file_get_contents($bootstrapPath);

    // API ルートの読み込み設定が存在することを確認
    expect($bootstrapContent)->toContain('routes/api.php', 'Bootstrap should load API routes');

    // Web ルートの読み込み設定が除去されていることを確認
    expect($bootstrapContent)->not->toContain('routes/web.php', 'Web routes should be removed from bootstrap');

    // ヘルスチェックエンドポイント設定の確認
    expect($bootstrapContent)->toContain("health: '/up'", 'Health check should be configured');
});

/**
 * Web ルートファイルが削除されていることをテスト
 */
it('web_routes_file_is_removed', function () {
    $webRoutesPath = base_path('routes/web.php');
    expect($webRoutesPath)->not->toBeFile('Web routes file should be removed for API-only architecture');
});

/**
 * API ルートファイルが存在し、動作することをテスト
 */
it('api_routes_file_exists_and_works', function () {
    $apiRoutesPath = base_path('routes/api.php');
    expect($apiRoutesPath)->toBeFile('API routes file should exist');

    // API ルートの内容確認
    $apiContent = file_get_contents($apiRoutesPath);
    expect($apiContent)->toContain('Route::', 'API routes should contain route definitions');
});

/**
 * ヘルスチェックエンドポイント（/up）の動作確認
 */
it('health_check_endpoint_works', function () {
    $response = $this->get('/up');
    $response->assertStatus(200);

    // ヘルスチェックレスポンスの内容確認
    $content = $response->getContent();
    expect($content)->toContain('Application up', 'Health check should return success message');
});

/**
 * API ルートのみの動作確認（認証エラーは正常）
 */
it('api_routes_only_operation', function () {
    // 認証が必要なAPIエンドポイントは401を返すことを確認
    $response = $this->getJson('/api/user');
    $response->assertStatus(401); // 認証エラーは正常な動作

    // JSONレスポンスであることを確認
    $response->assertHeader('content-type', 'application/json');
});

/**
 * Webルートが完全に無効化されていることを確認
 */
it('web_routes_are_completely_disabled', function () {
    // 元々存在したwelcomeページにアクセスできないことを確認
    $response = $this->get('/');
    $response->assertStatus(404); // Web routes削除により404
});
