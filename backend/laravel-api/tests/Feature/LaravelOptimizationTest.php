<?php

declare(strict_types=1);

it('config_cache_optimization', function () {
    // config:cacheを実行
    $this->artisan('config:cache')
        ->assertExitCode(0);

    // キャッシュファイルの存在確認
    expect(app()->bootstrapPath('cache/config.php'))->toBeFile();
});

it('route_cache_optimization', function () {
    // route:cacheを実行
    $this->artisan('route:cache')
        ->assertExitCode(0);

    // ルートキャッシュファイルの存在確認
    expect(app()->bootstrapPath('cache/routes-v7.php'))->toBeFile();
});

it('event_cache_optimization', function () {
    // event:cacheを実行
    $this->artisan('event:cache')
        ->assertExitCode(0);

    // イベントキャッシュファイルの存在確認
    expect(app()->bootstrapPath('cache/events.php'))->toBeFile();
});

it('api_only_optimization', function () {
    // API専用アーキテクチャでは、ビューキャッシュは不要
    // resources/viewsディレクトリが存在しないことを確認
    expect(resource_path('views'))->not->toBeDirectory();

    // ビューキャッシュディレクトリも存在しないか空であることを確認
    $viewCachePath = app()->bootstrapPath('cache/views');
    $viewCacheEmpty = ! is_dir($viewCachePath) || count(glob($viewCachePath.'/*')) === 0;
    expect($viewCacheEmpty)->toBeTrue('View cache should not exist in API-only architecture');
});

it('individual_optimization_commands', function () {
    // 個別に最適化コマンドを実行
    $this->artisan('config:cache')->assertExitCode(0);
    $this->artisan('route:cache')->assertExitCode(0);
    $this->artisan('event:cache')->assertExitCode(0);

    // 最適化状態の確認
    expect(app()->bootstrapPath('cache/config.php'))->toBeFile();
    expect(app()->bootstrapPath('cache/routes-v7.php'))->toBeFile();
    expect(app()->bootstrapPath('cache/events.php'))->toBeFile();
});

it('optimization_clears_properly', function () {
    // まず最適化を実行
    $this->artisan('config:cache')->assertExitCode(0);
    $this->artisan('route:cache')->assertExitCode(0);

    // クリアコマンドの動作確認
    $this->artisan('optimize:clear')->assertExitCode(0);

    // キャッシュファイルが削除されていることを確認
    expect(app()->bootstrapPath('cache/config.php'))->not->toBeFile();
    expect(app()->bootstrapPath('cache/routes-v7.php'))->not->toBeFile();
});

it('performance_after_optimization', function () {
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
    expect($afterOptimization)->toBeLessThanOrEqual($beforeOptimization * 1.1, 'Optimization should not significantly slow down the application');

    echo "Before optimization: {$beforeOptimization}ms\n";
    echo "After optimization: {$afterOptimization}ms\n";
});
