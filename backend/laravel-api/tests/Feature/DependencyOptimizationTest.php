<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DependencyOptimizationTest extends TestCase
{
    /**
     * Laravel Sanctum パッケージが追加されていることをテスト
     */
    public function test_laravel_sanctum_is_added(): void
    {
        $composerPath = base_path('composer.json');
        $composerContent = json_decode(File::get($composerPath), true);

        $this->assertArrayHasKey('laravel/sanctum', $composerContent['require'], 'Laravel Sanctum should be added to dependencies');
        $this->assertEquals('^4.0', $composerContent['require']['laravel/sanctum'], 'Sanctum version should be 4.0');
    }

    /**
     * 必須依存関係が正しく保持されていることをテスト
     */
    public function test_required_dependencies_are_maintained(): void
    {
        $composerPath = base_path('composer.json');
        $composerContent = json_decode(File::get($composerPath), true);

        $requiredPackages = [
            'php' => '^8.4',
            'laravel/framework' => '^12.0',
            'laravel/sanctum' => '^4.0',
            'laravel/tinker' => '^2.10.1',
        ];

        foreach ($requiredPackages as $package => $version) {
            $this->assertArrayHasKey($package, $composerContent['require'], "Package {$package} should be present");
            $this->assertEquals($version, $composerContent['require'][$package], "Package {$package} should have correct version {$version}");
        }
    }

    /**
     * 不要なビュー・セッション関連パッケージが除去されていることをテスト
     */
    public function test_view_session_packages_are_removed(): void
    {
        $composerPath = base_path('composer.json');
        $composerContent = json_decode(File::get($composerPath), true);

        // 現在の構成では既に最小限のため、これらのパッケージは存在しない
        $unwantedPackages = [
            'laravel/ui',           // Laravel UI components
            'laravel/breeze',       // Authentication scaffolding
            'laravel/jetstream',    // Application scaffolding
            'inertiajs/inertia-laravel', // Inertia.js
            'tightenco/ziggy',      // Route helper
        ];

        foreach ($unwantedPackages as $package) {
            $this->assertArrayNotHasKey($package, $composerContent['require'], "Unwanted package {$package} should not be present");
            $this->assertArrayNotHasKey($package, $composerContent['require-dev'] ?? [], "Unwanted package {$package} should not be in dev dependencies");
        }
    }

    /**
     * Sanctumの基本設定が正しく行われていることをテスト
     */
    public function test_sanctum_basic_configuration(): void
    {
        // Sanctum設定ファイルの存在確認
        $sanctumConfigPath = config_path('sanctum.php');

        if (! File::exists($sanctumConfigPath)) {
            // 設定ファイルが存在しない場合は、まだ公開されていない
            $this->markTestIncomplete('Sanctum config not published yet');
        }

        $this->assertFileExists($sanctumConfigPath, 'Sanctum config should be published');

        // Sanctumマイグレーションの確認
        $migrationFiles = File::glob(database_path('migrations/*_create_personal_access_tokens_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Sanctum migration should exist');
    }

    /**
     * 開発依存関係が適切に保持されていることをテスト
     */
    public function test_development_dependencies_are_maintained(): void
    {
        $composerPath = base_path('composer.json');
        $composerContent = json_decode(File::get($composerPath), true);

        $requiredDevPackages = [
            'fakerphp/faker',
            'laravel/pint',
            'phpunit/phpunit',
            'mockery/mockery',
            'nunomaduro/collision',
        ];

        foreach ($requiredDevPackages as $package) {
            $this->assertArrayHasKey($package, $composerContent['require-dev'], "Dev package {$package} should be maintained");
        }

        // 開発専用で削除可能なパッケージ（プロジェクト方針により）
        // 現在の構成では全て必要なパッケージが含まれている
        $this->assertIsArray($composerContent['require-dev'], 'Development dependencies should be array');
    }

    /**
     * composer.jsonの構造が正しく保持されていることをテスト
     */
    public function test_composer_json_structure_is_maintained(): void
    {
        $composerPath = base_path('composer.json');
        $composerContent = json_decode(File::get($composerPath), true);

        // 必須セクションの存在確認
        $requiredSections = [
            'name',
            'type',
            'description',
            'license',
            'require',
            'require-dev',
            'autoload',
            'autoload-dev',
            'scripts',
            'config',
        ];

        foreach ($requiredSections as $section) {
            $this->assertArrayHasKey($section, $composerContent, "Composer.json should contain {$section} section");
        }

        // プロジェクト情報の確認
        $this->assertEquals('laravel/laravel', $composerContent['name'], 'Project name should be maintained');
        $this->assertEquals('project', $composerContent['type'], 'Project type should be maintained');
        $this->assertEquals('MIT', $composerContent['license'], 'License should be maintained');
    }
}
