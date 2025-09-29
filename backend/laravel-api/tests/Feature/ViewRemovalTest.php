<?php

namespace Tests\Feature;

use Tests\TestCase;

class ViewRemovalTest extends TestCase
{
    /**
     * resources/views/ ディレクトリが完全削除されていることをテスト
     */
    public function test_views_directory_is_completely_removed(): void
    {
        $viewsPath = resource_path('views');
        $this->assertDirectoryDoesNotExist($viewsPath, 'Views directory should be completely removed for API-only architecture');
    }

    /**
     * Bladeテンプレート関連機能が除去されていることをテスト
     */
    public function test_blade_template_functionality_is_removed(): void
    {
        // welcome.blade.phpが存在しないことを確認
        $welcomeViewPath = resource_path('views/welcome.blade.php');
        $this->assertFileDoesNotExist($welcomeViewPath, 'Welcome Blade template should not exist');

        // viewsディレクトリ内の全ファイルが削除されていることを確認
        $viewsPath = resource_path('views');
        if (is_dir($viewsPath)) {
            $files = scandir($viewsPath);
            $actualFiles = array_diff($files, ['.', '..']);
            $this->assertEmpty($actualFiles, 'Views directory should be empty or not exist');
        }
    }

    /**
     * Web関連ミドルウェアスタックが削除されていることをテスト
     */
    public function test_web_middleware_stack_is_removed(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');
        $bootstrapContent = file_get_contents($bootstrapPath);

        // セッション関連ミドルウェアが除外されていることを確認
        $this->assertStringContainsString('StartSession::class', $bootstrapContent, 'StartSession middleware should be in remove list');
        $this->assertStringContainsString('EncryptCookies::class', $bootstrapContent, 'EncryptCookies middleware should be in remove list');
        $this->assertStringContainsString('VerifyCsrfToken::class', $bootstrapContent, 'VerifyCsrfToken middleware should be in remove list');

        // middleware->remove() が呼ばれていることを確認
        $this->assertStringContainsString('$middleware->remove([', $bootstrapContent, 'Middleware removal should be configured');
    }

    /**
     * CSRF攻撃対象の完全除去確認
     */
    public function test_csrf_attack_surface_is_completely_removed(): void
    {
        // ヘルスチェックエンドポイントにCSRFトークンが含まれていないことを確認
        $response = $this->get('/up');
        $response->assertStatus(200);

        $content = $response->getContent();

        // CSRFトークン関連要素が含まれていないことを確認
        $this->assertStringNotContainsString('csrf-token', $content, 'CSRF token should not be present');
        $this->assertStringNotContainsString('_token', $content, 'CSRF token field should not be present');
        $this->assertStringNotContainsString('@csrf', $content, 'Blade CSRF directive should not be present');

        // meta要素にCSRFトークンが含まれていないことを確認
        $this->assertStringNotContainsString('<meta name="csrf-token"', $content, 'CSRF meta tag should not be present');
    }

    /**
     * API専用アーキテクチャでのレスポンス確認
     */
    public function test_api_only_architecture_responses(): void
    {
        // ヘルスチェックはHTMLレスポンス（特殊なケース）
        $healthResponse = $this->get('/up');
        $healthResponse->assertStatus(200);
        $this->assertStringContainsString('text/html', $healthResponse->headers->get('content-type'));

        // API エンドポイントはJSONレスポンス
        $apiResponse = $this->getJson('/api/user');
        $apiResponse->assertStatus(401); // 認証エラー
        $this->assertStringContainsString('application/json', $apiResponse->headers->get('content-type'));
    }

    /**
     * ビューレンダリング機能の完全除去確認
     */
    public function test_view_rendering_functionality_is_completely_removed(): void
    {
        // ビューヘルパー関数がもはや使用されないことをテスト
        // routes/web.phpが削除されているため、view()関数を使用するルートが存在しないことを確認

        $routeFiles = [
            base_path('routes/api.php'),
            base_path('routes/console.php'),
        ];

        foreach ($routeFiles as $routeFile) {
            if (file_exists($routeFile)) {
                $content = file_get_contents($routeFile);
                // view()関数が使用されていないことを確認（API専用のため）
                $this->assertStringNotContainsString('return view(', $content,
                    'View function should not be used in API-only routes: '.basename($routeFile));
            }
        }
    }
}
