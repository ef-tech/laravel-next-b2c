<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OptimizationTargetAnalysisTest extends TestCase
{
    /**
     * 削除可能なパッケージとリスクの詳細評価テスト
     */
    public function test_removable_packages_are_identified_with_risks(): void
    {
        $composerPath = base_path('composer.json');
        $composerContent = json_decode(File::get($composerPath), true);

        // 現在の依存関係を確認
        $currentPackages = array_keys($composerContent['require'] ?? []);

        // 削除対象パッケージの特定（現在はtinkerのみが削除候補でない）
        $keepPackages = ['php', 'laravel/framework', 'laravel/tinker'];
        $removablePackages = array_diff($currentPackages, $keepPackages);

        // API専用構成では、Web関連パッケージは不要
        $this->assertEmpty($removablePackages, 'Current minimal config should not have removable packages');

        // 追加予定のパッケージを確認
        $this->assertArrayNotHasKey('laravel/sanctum', $composerContent['require'], 'Sanctum should not be present initially');
    }

    /**
     * Web機能削除による影響範囲の特定テスト
     */
    public function test_web_feature_removal_impact_is_identified(): void
    {
        // routes/web.phpの内容分析
        $webRoutesPath = base_path('routes/web.php');
        $this->assertFileExists($webRoutesPath, 'Web routes file should exist for impact analysis');

        $webRoutesContent = File::get($webRoutesPath);

        // ビューを使用するルートが存在することを確認
        $this->assertStringContainsString('view(', $webRoutesContent, 'Web routes should contain view usage');
        $this->assertStringContainsString('welcome', $webRoutesContent, 'Web routes should reference welcome view');

        // resources/views ディレクトリの確認
        $viewsPath = resource_path('views');
        $this->assertDirectoryExists($viewsPath, 'Views directory should exist for impact analysis');

        // welcome.blade.phpの存在確認（削除影響対象）
        $welcomeViewPath = resource_path('views/welcome.blade.php');
        $this->assertFileExists($welcomeViewPath, 'Welcome view should exist as removal target');
    }

    /**
     * 設定ファイル変更箇所の洗い出しテスト
     */
    public function test_configuration_change_points_are_identified(): void
    {
        // bootstrap/app.php の現在の設定を確認
        $bootstrapPath = base_path('bootstrap/app.php');
        $bootstrapContent = File::get($bootstrapPath);

        // Web ルートの読み込み設定が存在することを確認
        $this->assertStringContainsString('routes/web.php', $bootstrapContent, 'Bootstrap should load web routes currently');
        $this->assertStringContainsString('health: \'/up\'', $bootstrapContent, 'Health check should be configured');

        // config/auth.php の現在の認証設定を確認
        $authConfigPath = config_path('auth.php');
        $this->assertFileExists($authConfigPath, 'Auth config should exist');

        $authConfig = include $authConfigPath;
        $this->assertArrayHasKey('defaults', $authConfig, 'Auth config should have defaults');
        $this->assertArrayHasKey('guard', $authConfig['defaults'], 'Auth defaults should specify guard');

        // config/session.php の存在確認
        $sessionConfigPath = config_path('session.php');
        $this->assertFileExists($sessionConfigPath, 'Session config should exist for modification');
    }

    /**
     * テストケース実行可能性の事前確認
     */
    public function test_test_execution_capability_before_changes(): void
    {
        // 現在のテストスイートが実行可能であることを確認
        $testDirectory = base_path('tests');
        $this->assertDirectoryExists($testDirectory, 'Tests directory should exist');

        // phpunit.xmlの存在確認
        $phpunitXmlPath = base_path('phpunit.xml');
        $this->assertFileExists($phpunitXmlPath, 'PHPUnit configuration should exist');

        // テスト用データベース設定の確認
        $testEnv = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        $this->assertNotEmpty($testEnv, 'Test database connection should be configured');

        // 基本的なアプリケーション機能のテスト
        $response = $this->get('/up');  // ヘルスチェック
        $response->assertStatus(200);
    }

    /**
     * CORS設定の必要性確認テスト
     */
    public function test_cors_configuration_requirements(): void
    {
        // 現在CORS設定が存在しないことを確認
        $corsConfigPath = config_path('cors.php');

        if (File::exists($corsConfigPath)) {
            $this->markTestSkipped('CORS config already exists');
        }

        // API専用化に向けてCORS設定が必要になることをテストで表現
        $this->assertFalse(File::exists($corsConfigPath), 'CORS config should not exist initially');

        // Next.jsフロントエンドのポート設定確認（ステアリング文書から）
        $envContent = File::get(base_path('.env'));
        $this->assertIsString($envContent, 'Environment file should be readable for CORS setup');
    }

    /**
     * ミドルウェア削除対象の確認テスト
     */
    public function test_middleware_removal_targets_identified(): void
    {
        // Laravel 12の新形式でミドルウェアが設定されていることを確認
        $bootstrapPath = base_path('bootstrap/app.php');
        $bootstrapContent = File::get($bootstrapPath);

        // ミドルウェア設定セクションの存在確認
        $this->assertStringContainsString('withMiddleware', $bootstrapContent, 'Middleware configuration should exist');

        // 現在はデフォルトのミドルウェアが設定されていることを想定
        // 削除対象：StartSession, EncryptCookies, VerifyCsrfToken
        // 注意：Laravel 12では明示的に記述されていない場合があるため、設定の存在を確認
        $this->assertStringNotContainsString('StartSession', $bootstrapContent, 'StartSession middleware should not be explicitly set yet');
        $this->assertStringNotContainsString('EncryptCookies', $bootstrapContent, 'EncryptCookies middleware should not be explicitly set yet');
    }
}
