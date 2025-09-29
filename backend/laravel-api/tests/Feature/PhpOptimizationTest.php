<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhpOptimizationTest extends TestCase
{
    public function test_opcache_is_enabled(): void
    {
        $this->assertTrue(extension_loaded('Zend OPcache'), 'OPcache extension should be loaded');
        $this->assertTrue(function_exists('opcache_get_status'), 'OPcache functions should be available');
    }

    public function test_opcache_configuration_optimization(): void
    {
        if (! extension_loaded('Zend OPcache')) {
            $this->markTestSkipped('OPcache extension not loaded');
        }

        // メモリ設定の確認
        $memoryConsumption = ini_get('opcache.memory_consumption');
        $this->assertGreaterThanOrEqual(128, (int) $memoryConsumption,
            'OPcache memory consumption should be at least 128MB');

        // 最大ファイル数の確認
        $maxFiles = ini_get('opcache.max_accelerated_files');
        $this->assertGreaterThanOrEqual(10000, (int) $maxFiles,
            'OPcache should handle at least 10,000 files');

        // JITバッファサイズの確認
        $jitBuffer = ini_get('opcache.jit_buffer_size');
        if ($jitBuffer) {
            $jitSize = $this->parseSize($jitBuffer);
            $this->assertGreaterThanOrEqual(64 * 1024 * 1024, $jitSize,
                'JIT buffer should be at least 64MB');
        }
    }

    public function test_php_performance_settings(): void
    {
        // メモリ制限の確認（-1は無制限を意味し、本番環境では適切）
        $memoryLimit = ini_get('memory_limit');
        $memoryBytes = $this->parseSize($memoryLimit);

        // -1 (無制限) または 256MB以上であることを確認
        $this->assertTrue(
            $memoryBytes === -1 || $memoryBytes >= 256 * 1024 * 1024,
            'PHP memory limit should be unlimited (-1) or at least 256MB'
        );

        // リアルパス キャッシュの確認
        $realpathCacheSize = ini_get('realpath_cache_size');
        if ($realpathCacheSize) {
            $this->assertNotEquals('16k', $realpathCacheSize,
                'Realpath cache should be larger than default 16k');
        }

        echo "PHP memory limit: {$memoryLimit}\n";
        echo "Realpath cache size: {$realpathCacheSize}\n";
    }

    public function test_opcache_statistics(): void
    {
        if (! extension_loaded('Zend OPcache') || ! function_exists('opcache_get_status')) {
            $this->markTestSkipped('OPcache not available');
        }

        // いくつかのPHPファイルをロードしてOPcacheを動作させる
        require_once app_path('Models/User.php');

        $status = opcache_get_status(false);

        if ($status === false) {
            $this->markTestSkipped('OPcache status not available');
        }

        $this->assertIsArray($status, 'OPcache status should be available');
        $this->assertTrue($status['opcache_enabled'] ?? false, 'OPcache should be enabled');

        // メモリ使用率をログ出力
        $memory = $status['memory_usage'] ?? [];
        if (! empty($memory)) {
            $usedMemory = $memory['used_memory'] ?? 0;
            $totalMemory = $usedMemory + ($memory['free_memory'] ?? 0);
            $usagePercent = $totalMemory > 0 ? ($usedMemory / $totalMemory) * 100 : 0;

            echo "OPcache memory usage: {$usagePercent}% ({$usedMemory} bytes used)\n";
        }
    }

    public function test_performance_with_opcache(): void
    {
        $iterations = 1000;
        $startTime = microtime(true);

        // 繰り返し処理でPHPコードの実行時間を測定
        for ($i = 0; $i < $iterations; $i++) {
            config('app.name'); // 設定アクセス
            app('hash'); // サービス解決
        }

        $executionTime = (microtime(true) - $startTime) * 1000; // ms
        $timePerIteration = $executionTime / $iterations;

        echo "PHP execution time: {$executionTime}ms for {$iterations} iterations\n";
        echo "Time per iteration: {$timePerIteration}ms\n";

        // OPcacheの恩恵で、1反復あたり0.15ms未満を期待
        $this->assertLessThan(0.15, $timePerIteration,
            'Each iteration should be fast with OPcache optimization');
    }

    private function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;

        switch ($last) {
            case 'g':
                $size *= 1024;
                // fall through
            case 'm':
                $size *= 1024;
                // fall through
            case 'k':
                $size *= 1024;
        }

        return $size;
    }
}
