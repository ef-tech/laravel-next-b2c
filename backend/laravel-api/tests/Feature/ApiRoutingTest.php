<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRoutingTest extends TestCase
{
    /**
     * bootstrap/app.phpがAPI専用ルーティング構成に変更されていることをテスト
     */
    public function test_bootstrap_uses_api_routing(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');
        $bootstrapContent = file_get_contents($bootstrapPath);

        // API ルートの読み込み設定が存在することを確認
        $this->assertStringContainsString('routes/api.php', $bootstrapContent, 'Bootstrap should load API routes');

        // Web ルートの読み込み設定が除去されていることを確認
        $this->assertStringNotContainsString('routes/web.php', $bootstrapContent, 'Web routes should be removed from bootstrap');

        // ヘルスチェックエンドポイント設定の確認
        $this->assertStringContainsString("health: '/up'", $bootstrapContent, 'Health check should be configured');
    }

    /**
     * Web ルートファイルが削除されていることをテスト
     */
    public function test_web_routes_file_is_removed(): void
    {
        $webRoutesPath = base_path('routes/web.php');
        $this->assertFileDoesNotExist($webRoutesPath, 'Web routes file should be removed for API-only architecture');
    }

    /**
     * API ルートファイルが存在し、動作することをテスト
     */
    public function test_api_routes_file_exists_and_works(): void
    {
        $apiRoutesPath = base_path('routes/api.php');
        $this->assertFileExists($apiRoutesPath, 'API routes file should exist');

        // API ルートの内容確認
        $apiContent = file_get_contents($apiRoutesPath);
        $this->assertStringContainsString('Route::', $apiContent, 'API routes should contain route definitions');
    }

    /**
     * ヘルスチェックエンドポイント（/up）の動作確認
     */
    public function test_health_check_endpoint_works(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(200);

        // ヘルスチェックレスポンスの内容確認
        $content = $response->getContent();
        $this->assertStringContainsString('Application up', $content, 'Health check should return success message');
    }

    /**
     * API ルートのみの動作確認（認証エラーは正常）
     */
    public function test_api_routes_only_operation(): void
    {
        // 認証が必要なAPIエンドポイントは401を返すことを確認
        $response = $this->getJson('/api/user');
        $response->assertStatus(401); // 認証エラーは正常な動作

        // JSONレスポンスであることを確認
        $response->assertHeader('content-type', 'application/json');
    }

    /**
     * Webルートが完全に無効化されていることを確認
     */
    public function test_web_routes_are_completely_disabled(): void
    {
        // 元々存在したwelcomeページにアクセスできないことを確認
        $response = $this->get('/');
        $response->assertStatus(404); // Web routes削除により404
    }
}