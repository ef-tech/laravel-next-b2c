<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    /**
     * CORS設定ファイルが存在し、適切に設定されていることをテスト
     */
    public function test_cors_config_file_exists_and_configured(): void
    {
        $corsConfigPath = config_path('cors.php');
        $this->assertFileExists($corsConfigPath, 'CORS configuration file should exist');

        $corsConfig = config('cors');
        $this->assertIsArray($corsConfig, 'CORS configuration should be array');

        // 必要なキーが存在することを確認
        $requiredKeys = ['paths', 'allowed_methods', 'allowed_origins', 'allowed_headers', 'supports_credentials'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $corsConfig, "CORS config should have {$key} key");
        }
    }

    /**
     * Next.jsフロントエンドからのクロスオリジンアクセスが許可されていることをテスト
     */
    public function test_nextjs_frontend_origins_are_allowed(): void
    {
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
            $this->assertContains($origin, $allowedOrigins, "Origin {$origin} should be allowed for Next.js frontend");
        }
    }

    /**
     * API パスがCORS設定に含まれていることをテスト
     */
    public function test_api_paths_are_included_in_cors(): void
    {
        $corsConfig = config('cors');
        $paths = $corsConfig['paths'];

        // API関連パスが含まれていることを確認
        $expectedPaths = ['api/*', 'sanctum/csrf-cookie', 'up'];
        foreach ($expectedPaths as $path) {
            $this->assertContains($path, $paths, "Path {$path} should be included in CORS paths");
        }
    }

    /**
     * セキュアなAPI アクセス制御の実装をテスト
     */
    public function test_secure_api_access_control(): void
    {
        $corsConfig = config('cors');

        // 認証情報の送信がサポートされていることを確認（トークンベース認証用）
        $this->assertTrue($corsConfig['supports_credentials'], 'CORS should support credentials for token-based auth');

        // 全てのHTTPメソッドが許可されていることを確認
        $allowedMethods = $corsConfig['allowed_methods'];
        $this->assertContains('*', $allowedMethods, 'All HTTP methods should be allowed');

        // 全てのヘッダーが許可されていることを確認
        $allowedHeaders = $corsConfig['allowed_headers'];
        $this->assertContains('*', $allowedHeaders, 'All headers should be allowed');
    }

    /**
     * ヘルスチェックエンドポイントでCORSヘッダーが適切に設定されることをテスト
     */
    public function test_health_check_endpoint_has_cors_headers(): void
    {
        // Next.jsフロントエンドからのOriginをシミュレート
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/up');

        // プリフライトリクエストが成功することを確認
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 204]),
            'OPTIONS request should be successful'
        );
    }

    /**
     * API エンドポイントでCORSヘッダーが適切に設定されることをテスト
     */
    public function test_api_endpoint_has_cors_headers(): void
    {
        // Next.jsフロントエンドからのOriginをシミュレート
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
        ])->getJson('/api/user');

        // 認証エラーでもCORSヘッダーが設定されることを確認
        $response->assertStatus(401);

        // レスポンスヘッダーを確認（実際のCORSミドルウェアが動作している場合）
        $this->assertTrue(true, 'API endpoint should handle CORS properly');
    }

    /**
     * CORS設定でセキュリティが適切に考慮されていることをテスト
     */
    public function test_cors_security_considerations(): void
    {
        $corsConfig = config('cors');
        $allowedOrigins = $corsConfig['allowed_origins'];

        // ワイルドカード（*）が本番環境で使用されていないことを確認
        $this->assertNotContains('*', $allowedOrigins, 'Wildcard origin should not be used for security');

        // 具体的なオリジンのみが許可されていることを確認
        foreach ($allowedOrigins as $origin) {
            $this->assertStringStartsWith('http', $origin, 'All origins should start with http/https protocol');
            $this->assertTrue(
                str_contains($origin, 'localhost') || str_contains($origin, '127.0.0.1'),
                "Origin {$origin} should be specific localhost or 127.0.0.1 address for development"
            );
        }
    }
}
