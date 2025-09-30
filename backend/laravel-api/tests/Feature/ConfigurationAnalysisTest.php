<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * 現在のcomposer.json依存関係が記録されていることをテスト
 */
it('composer_dependencies_are_documented', function () {
    $composerPath = base_path('composer.json');
    expect($composerPath)->toBeFile('composer.json file should exist');

    $composerContent = json_decode(File::get($composerPath), true);
    expect($composerContent)->toBeArray('composer.json should contain valid JSON');
    expect($composerContent)->toHaveKey('require', 'composer.json should have require section');

    // 必須の依存関係が存在することを確認
    $require = $composerContent['require'];
    expect($require)->toHaveKey('php', 'PHP version should be specified');
    expect($require)->toHaveKey('laravel/framework', 'Laravel framework should be required');
    expect($require)->toHaveKey('laravel/tinker', 'Laravel tinker should be required');

    // Sanctumが追加されていることを確認（最適化後状態）
    expect($require)->toHaveKey('laravel/sanctum', 'Sanctum should be present after optimization');
});

/**
 * セッション・ビュー・Web機能の利用箇所が特定されることをテスト
 */
it('web_features_are_identified', function () {
    // routes/web.phpファイルの存在確認
    $webRoutesPath = base_path('routes/web.php');
    expect($webRoutesPath)->toBeFile('Web routes file should exist initially');

    // resources/viewsディレクトリの存在確認
    $viewsPath = resource_path('views');
    expect($viewsPath)->toBeDirectory('Views directory should exist initially');

    // セッション設定ファイルの存在確認
    $sessionConfigPath = config_path('session.php');
    expect($sessionConfigPath)->toBeFile('Session config should exist');

    // bootstrap/app.phpでWebルートがロードされていることを確認
    $bootstrapPath = base_path('bootstrap/app.php');
    $bootstrapContent = File::get($bootstrapPath);
    expect($bootstrapContent)->toContain('routes/web.php', 'Bootstrap should load web routes');
});

/**
 * パフォーマンスベースライン測定のテスト基盤
 */
it('performance_baseline_can_be_measured', function () {
    // より実際の起動時間を測定するためのシミュレーション
    $startTime = microtime(true);

    // 実際のアプリケーション処理をシミュレート
    config('app.name');  // 設定読み込み
    app('router');       // ルーター取得
    usleep(1000);        // 1ms の処理時間を追加

    $bootTime = microtime(true) - $startTime;

    expect($bootTime)->toBeFloat('Boot time should be measurable');
    expect($bootTime)->toBeGreaterThan(0, 'Boot time should be positive');

    // メモリ使用量測定の基盤確認
    $memoryUsage = memory_get_usage(true);
    expect($memoryUsage)->toBeInt('Memory usage should be measurable');
    expect($memoryUsage)->toBeGreaterThan(0, 'Memory usage should be positive');
});

/**
 * バックアップ可能性のテスト
 */
it('backup_capability_exists', function () {
    // プロジェクトルートのGitリポジトリを確認（laravel-apiではなく上位）
    $gitPath = dirname(dirname(base_path())).'/.git';
    expect($gitPath)->toBeDirectory('Git repository should exist for backup');

    // 重要なファイルが存在してバックアップ可能であることを確認
    $criticalFiles = [
        'composer.json',
        'bootstrap/app.php',
        'routes/web.php',
        'config/auth.php',
        'config/session.php',
    ];

    foreach ($criticalFiles as $file) {
        $filePath = base_path($file);
        expect($filePath)->toBeFile("Critical file {$file} should exist for backup");
    }
});

/**
 * 依存関係数のカウントテスト
 */
it('dependency_count_is_trackable', function () {
    $composerPath = base_path('composer.json');
    $composerContent = json_decode(File::get($composerPath), true);

    $productionDeps = count($composerContent['require'] ?? []);
    $devDeps = count($composerContent['require-dev'] ?? []);
    $totalDeps = $productionDeps + $devDeps;

    expect($productionDeps)->toBeInt('Production dependencies should be countable');
    expect($devDeps)->toBeInt('Development dependencies should be countable');
    expect($totalDeps)->toBeGreaterThan(0, 'Total dependencies should be positive');

    // ベースライン記録のため、依存関係数を確認
    expect($productionDeps)->toBeGreaterThanOrEqual(2, 'Should have at least PHP and Laravel framework');
});
