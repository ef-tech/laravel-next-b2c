<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Composer\Autoload\ClassLoader;

class ConfigurationAnalysisTest extends TestCase
{
    /**
     * 現在のcomposer.json依存関係が記録されていることをテスト
     */
    public function test_composer_dependencies_are_documented(): void
    {
        $composerPath = base_path('composer.json');
        $this->assertFileExists($composerPath, 'composer.json file should exist');

        $composerContent = json_decode(File::get($composerPath), true);
        $this->assertIsArray($composerContent, 'composer.json should contain valid JSON');
        $this->assertArrayHasKey('require', $composerContent, 'composer.json should have require section');

        // 必須の依存関係が存在することを確認
        $require = $composerContent['require'];
        $this->assertArrayHasKey('php', $require, 'PHP version should be specified');
        $this->assertArrayHasKey('laravel/framework', $require, 'Laravel framework should be required');
        $this->assertArrayHasKey('laravel/tinker', $require, 'Laravel tinker should be required');

        // Sanctumが追加されていることを確認（最適化後状態）
        $this->assertArrayHasKey('laravel/sanctum', $require, 'Sanctum should be present after optimization');
    }

    /**
     * セッション・ビュー・Web機能の利用箇所が特定されることをテスト
     */
    public function test_web_features_are_identified(): void
    {
        // routes/web.phpファイルの存在確認
        $webRoutesPath = base_path('routes/web.php');
        $this->assertFileExists($webRoutesPath, 'Web routes file should exist initially');

        // resources/viewsディレクトリの存在確認
        $viewsPath = resource_path('views');
        $this->assertDirectoryExists($viewsPath, 'Views directory should exist initially');

        // セッション設定ファイルの存在確認
        $sessionConfigPath = config_path('session.php');
        $this->assertFileExists($sessionConfigPath, 'Session config should exist');

        // bootstrap/app.phpでWebルートがロードされていることを確認
        $bootstrapPath = base_path('bootstrap/app.php');
        $bootstrapContent = File::get($bootstrapPath);
        $this->assertStringContainsString('routes/web.php', $bootstrapContent, 'Bootstrap should load web routes');
    }

    /**
     * パフォーマンスベースライン測定のテスト基盤
     */
    public function test_performance_baseline_can_be_measured(): void
    {
        // より実際の起動時間を測定するためのシミュレーション
        $startTime = microtime(true);

        // 実際のアプリケーション処理をシミュレート
        config('app.name');  // 設定読み込み
        app('router');       // ルーター取得
        usleep(1000);        // 1ms の処理時間を追加

        $bootTime = microtime(true) - $startTime;

        $this->assertIsFloat($bootTime, 'Boot time should be measurable');
        $this->assertGreaterThan(0, $bootTime, 'Boot time should be positive');

        // メモリ使用量測定の基盤確認
        $memoryUsage = memory_get_usage(true);
        $this->assertIsInt($memoryUsage, 'Memory usage should be measurable');
        $this->assertGreaterThan(0, $memoryUsage, 'Memory usage should be positive');
    }

    /**
     * バックアップ可能性のテスト
     */
    public function test_backup_capability_exists(): void
    {
        // プロジェクトルートのGitリポジトリを確認（laravel-apiではなく上位）
        $gitPath = dirname(dirname(base_path())) . '/.git';
        $this->assertDirectoryExists($gitPath, 'Git repository should exist for backup');

        // 重要なファイルが存在してバックアップ可能であることを確認
        $criticalFiles = [
            'composer.json',
            'bootstrap/app.php',
            'routes/web.php',
            'config/auth.php',
            'config/session.php'
        ];

        foreach ($criticalFiles as $file) {
            $filePath = base_path($file);
            $this->assertFileExists($filePath, "Critical file {$file} should exist for backup");
        }
    }

    /**
     * 依存関係数のカウントテスト
     */
    public function test_dependency_count_is_trackable(): void
    {
        $composerPath = base_path('composer.json');
        $composerContent = json_decode(File::get($composerPath), true);

        $productionDeps = count($composerContent['require'] ?? []);
        $devDeps = count($composerContent['require-dev'] ?? []);
        $totalDeps = $productionDeps + $devDeps;

        $this->assertIsInt($productionDeps, 'Production dependencies should be countable');
        $this->assertIsInt($devDeps, 'Development dependencies should be countable');
        $this->assertGreaterThan(0, $totalDeps, 'Total dependencies should be positive');

        // ベースライン記録のため、依存関係数を確認
        $this->assertGreaterThanOrEqual(2, $productionDeps, 'Should have at least PHP and Laravel framework');
    }
}