<?php

declare(strict_types=1);

it('composer_dump_autoload_optimizes_successfully', function () {
    $output = [];
    $returnCode = null;

    exec('cd '.base_path().' && composer dump-autoload --optimize 2>&1', $output, $returnCode);

    expect($returnCode)->toBe(0, 'Composer dump-autoload should succeed: '.implode("\n", $output));

    // オートローダーファイルの存在確認
    expect(base_path('vendor/autoload.php'))->toBeFile('Autoloader should exist');
    expect(base_path('vendor/composer/autoload_classmap.php'))->toBeFile('Classmap should be generated');
});

it('dependency_count_baseline', function () {
    $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

    $totalPackages = count($composerLock['packages'] ?? []);
    $devPackages = count($composerLock['packages-dev'] ?? []);

    // ベースライン記録（実際の値は実行時に確認）
    expect($totalPackages)->toBeGreaterThan(0, 'Should have production packages');
    expect($devPackages)->toBeGreaterThan(0, 'Should have development packages');

    // 依存関係数をコンソールに出力（手動確認用）
    echo "Current dependency baseline:\n";
    echo "Production packages: $totalPackages\n";
    echo "Development packages: $devPackages\n";
    echo 'Total packages: '.($totalPackages + $devPackages)."\n";
});

it('application_performance_baseline', function () {
    $startTime = microtime(true);

    // アプリケーション起動テスト
    $response = $this->get('/up');

    $bootTime = (microtime(true) - $startTime) * 1000; // ms

    $response->assertStatus(200);

    // 起動時間をコンソールに出力（ベースライン記録用）
    echo "Application boot time baseline: {$bootTime}ms\n";

    expect($bootTime)->toBeLessThan(5000, 'Boot time should be under 5 seconds');
});

it('memory_usage_baseline', function () {
    $startMemory = memory_get_usage();

    // 基本的なアプリケーション操作
    $response = $this->get('/up');
    $response->assertStatus(200);

    $endMemory = memory_get_usage();
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB

    echo "Memory usage baseline: {$memoryUsed}MB\n";
    echo 'Peak memory usage: '.(memory_get_peak_usage() / 1024 / 1024)."MB\n";

    expect(memory_get_peak_usage() / 1024 / 1024)->toBeLessThan(128, 'Peak memory should be under 128MB');
});
