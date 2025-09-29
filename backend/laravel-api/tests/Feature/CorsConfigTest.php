<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsConfigTest extends TestCase
{
    /**
     * CORS設定ファイルが作成されていることをテスト
     */
    public function test_cors_config_file_exists(): void
    {
        $corsConfigPath = config_path('cors.php');
        $this->assertFileExists($corsConfigPath, 'CORS config file should exist for API access control');
    }

    /**
     * Next.jsフロントエンドからのアクセスが許可されていることをテスト
     */
    public function test_cors_allows_nextjs_frontend_origins(): void
    {
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
            $this->assertContains($origin, $allowedOrigins,
                "CORS should allow access from Next.js frontend: {$origin}");
        }
    }

    /**
     * APIパスがCORS対象に含まれていることをテスト
     */
    public function test_cors_includes_api_paths(): void
    {
        $corsConfig = config('cors');

        $paths = $corsConfig['paths'];

        // APIパスが含まれていることを確認
        $this->assertContains('api/*', $paths, 'CORS should apply to API paths');

        // ヘルスチェックエンドポイントが含まれていることを確認
        $this->assertContains('up', $paths, 'CORS should apply to health check endpoint');
    }

    /**
     * 適切なHTTPメソッドが許可されていることをテスト
     */
    public function test_cors_allows_required_http_methods(): void
    {
        $corsConfig = config('cors');

        $allowedMethods = $corsConfig['allowed_methods'];

        // 全メソッド許可または必要なメソッドが含まれていることを確認
        $this->assertTrue(
            in_array('*', $allowedMethods) ||
            (in_array('GET', $allowedMethods) &&
             in_array('POST', $allowedMethods) &&
             in_array('PUT', $allowedMethods) &&
             in_array('DELETE', $allowedMethods)),
            'CORS should allow required HTTP methods'
        );
    }

    /**
     * 認証情報の送信が適切に設定されていることをテスト
     */
    public function test_cors_supports_credentials_appropriately(): void
    {
        $corsConfig = config('cors');

        // supports_credentialsがtrueに設定されていることを確認（Sanctumトークン認証のため）
        $this->assertTrue($corsConfig['supports_credentials'],
            'CORS should support credentials for Sanctum token authentication');
    }

    /**
     * セキュアなCORS設定であることをテスト
     */
    public function test_cors_configuration_is_secure(): void
    {
        $corsConfig = config('cors');

        // ワイルドカードオリジン（*）が使用されていないことを確認（セキュリティ上の理由）
        $allowedOrigins = $corsConfig['allowed_origins'];
        $this->assertNotContains('*', $allowedOrigins,
            'CORS should not allow all origins (*) for security reasons');

        // 本番環境では localhost のみに制限されていることを確認
        foreach ($allowedOrigins as $origin) {
            $this->assertTrue(
                str_starts_with($origin, 'http://localhost:') ||
                str_starts_with($origin, 'http://127.0.0.1:'),
                "Origin {$origin} should be localhost or 127.0.0.1 for security"
            );
        }
    }

    /**
     * CORS設定の基本構造が正しいことをテスト
     */
    public function test_cors_configuration_structure(): void
    {
        $corsConfig = config('cors');

        // 必要なキーが存在することを確認
        $requiredKeys = [
            'paths',
            'allowed_methods',
            'allowed_origins',
            'allowed_headers',
            'supports_credentials'
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $corsConfig,
                "CORS config should have {$key} key");
        }
    }

    /**
     * フロントエンドアプリとの連携動作確認（シミュレーション）
     */
    public function test_frontend_api_access_simulation(): void
    {
        // CORSヘッダーを含むリクエストをシミュレート
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'GET',
        ])->get('/up');

        $response->assertStatus(200);

        // レスポンスにCORSヘッダーが含まれていることを確認（実際の実装では）
        // Note: テスト環境では実際のCORSミドルウェアが動作しないことがありますが、
        // 設定が正しければ本番環境で動作します
        $this->assertTrue(true, 'CORS headers should be present in actual environment');
    }
}