<?php

namespace Tests\Feature;

use Tests\TestCase;

class LaravelOptimizationTest extends TestCase
{
    public function test_config_cache_optimization(): void
    {
        // config:cacheを実行
        $this->artisan('config:cache')
            ->assertExitCode(0);

        // キャッシュファイルの存在確認
        $this->assertFileExists(app()->bootstrapPath('cache/config.php'));
    }

    public function test_route_cache_optimization(): void
    {
        // route:cacheを実行
        $this->artisan('route:cache')
            ->assertExitCode(0);

        // ルートキャッシュファイルの存在確認
        $this->assertFileExists(app()->bootstrapPath('cache/routes-v7.php'));
    }

    public function test_event_cache_optimization(): void
    {
        // event:cacheを実行
        $this->artisan('event:cache')
            ->assertExitCode(0);

        // イベントキャッシュファイルの存在確認
        $this->assertFileExists(app()->bootstrapPath('cache/events.php'));
    }

    public function test_api_only_optimization(): void
    {
        // API専用アーキテクチャでは、ビューキャッシュは不要
        // resources/viewsディレクトリが存在しないことを確認
        $this->assertDirectoryDoesNotExist(resource_path('views'));

        // ビューキャッシュディレクトリも存在しないか空であることを確認
        $viewCachePath = app()->bootstrapPath('cache/views');
        $this->assertTrue(
            ! is_dir($viewCachePath) || count(glob($viewCachePath.'/*')) === 0,
            'View cache should not exist in API-only architecture'
        );
    }

    public function test_individual_optimization_commands(): void
    {
        // 個別に最適化コマンドを実行
        $this->artisan('config:cache')->assertExitCode(0);
        $this->artisan('route:cache')->assertExitCode(0);
        $this->artisan('event:cache')->assertExitCode(0);

        // 最適化状態の確認
        $this->assertFileExists(app()->bootstrapPath('cache/config.php'));
        $this->assertFileExists(app()->bootstrapPath('cache/routes-v7.php'));
        $this->assertFileExists(app()->bootstrapPath('cache/events.php'));
    }

    public function test_optimization_clears_properly(): void
    {
        // まず最適化を実行
        $this->artisan('config:cache')->assertExitCode(0);
        $this->artisan('route:cache')->assertExitCode(0);

        // クリアコマンドの動作確認
        $this->artisan('optimize:clear')->assertExitCode(0);

        // キャッシュファイルが削除されていることを確認
        $this->assertFileDoesNotExist(app()->bootstrapPath('cache/config.php'));
        $this->assertFileDoesNotExist(app()->bootstrapPath('cache/routes-v7.php'));
    }

    public function test_performance_after_optimization(): void
    {
        // 最適化前の状態をクリア
        $this->artisan('optimize:clear');

        $startTime = microtime(true);
        $response = $this->get('/up');
        $beforeOptimization = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);

        // 最適化実行
        $this->artisan('config:cache')->assertExitCode(0);
        $this->artisan('route:cache')->assertExitCode(0);
        $this->artisan('event:cache')->assertExitCode(0);

        $startTime = microtime(true);
        $response = $this->get('/up');
        $afterOptimization = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);

        // 最適化後の方が速いか同等であることを確認
        $this->assertLessThanOrEqual($beforeOptimization * 1.1, $afterOptimization,
            'Optimization should not significantly slow down the application');

        echo "Before optimization: {$beforeOptimization}ms\n";
        echo "After optimization: {$afterOptimization}ms\n";
    }
}
