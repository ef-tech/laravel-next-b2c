<?php

namespace Tests\Feature;

use Tests\TestCase;

class AutoloaderOptimizationTest extends TestCase
{
    public function test_composer_dump_autoload_optimizes_successfully(): void
    {
        $output = [];
        $returnCode = null;

        exec('cd '.base_path().' && composer dump-autoload --optimize 2>&1', $output, $returnCode);

        $this->assertEquals(0, $returnCode, 'Composer dump-autoload should succeed: '.implode("\n", $output));

        // オートローダーファイルの存在確認
        $this->assertFileExists(base_path('vendor/autoload.php'), 'Autoloader should exist');
        $this->assertFileExists(base_path('vendor/composer/autoload_classmap.php'), 'Classmap should be generated');
    }

    public function test_dependency_count_baseline(): void
    {
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

        $totalPackages = count($composerLock['packages'] ?? []);
        $devPackages = count($composerLock['packages-dev'] ?? []);

        // ベースライン記録（実際の値は実行時に確認）
        $this->assertGreaterThan(0, $totalPackages, 'Should have production packages');
        $this->assertGreaterThan(0, $devPackages, 'Should have development packages');

        // 依存関係数をコンソールに出力（手動確認用）
        echo "Current dependency baseline:\n";
        echo "Production packages: $totalPackages\n";
        echo "Development packages: $devPackages\n";
        echo 'Total packages: '.($totalPackages + $devPackages)."\n";
    }

    public function test_application_performance_baseline(): void
    {
        $startTime = microtime(true);

        // アプリケーション起動テスト
        $response = $this->get('/up');

        $bootTime = (microtime(true) - $startTime) * 1000; // ms

        $response->assertStatus(200);

        // 起動時間をコンソールに出力（ベースライン記録用）
        echo "Application boot time baseline: {$bootTime}ms\n";

        $this->assertLessThan(5000, $bootTime, 'Boot time should be under 5 seconds');
    }

    public function test_memory_usage_baseline(): void
    {
        $startMemory = memory_get_usage();

        // 基本的なアプリケーション操作
        $response = $this->get('/up');
        $response->assertStatus(200);

        $endMemory = memory_get_usage();
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB

        echo "Memory usage baseline: {$memoryUsed}MB\n";
        echo 'Peak memory usage: '.(memory_get_peak_usage() / 1024 / 1024)."MB\n";

        $this->assertLessThan(128, memory_get_peak_usage() / 1024 / 1024, 'Peak memory should be under 128MB');
    }
}
