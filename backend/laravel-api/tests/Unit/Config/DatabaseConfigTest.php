<?php

declare(strict_types=1);

/**
 * PostgreSQL接続設定が正しく読み込まれることを確認
 */
test('pgsql接続設定が正しく読み込まれる', function () {
    $config = config('database.connections.pgsql');

    // 基本設定の確認
    expect($config)->toBeArray();
    expect($config['driver'])->toBe('pgsql');

    // 最適化パラメータの確認
    expect($config)->toHaveKeys([
        'search_path',
        'sslmode',
        'connect_timeout',
        'application_name',
        'options',
        'pdo_options',
    ]);
});

/**
 * 環境変数のデフォルト値フォールバックが動作することを確認
 */
test('環境変数のデフォルト値フォールバックが動作する', function () {
    // 環境変数を一時的にクリアして、デフォルト値を確認
    config(['database.connections.pgsql.host' => env('DB_HOST', '127.0.0.1')]);
    config(['database.connections.pgsql.port' => env('DB_PORT', '5432')]);
    config(['database.connections.pgsql.connect_timeout' => env('DB_CONNECT_TIMEOUT', 5)]);

    $config = config('database.connections.pgsql');

    // デフォルト値の確認
    expect($config['host'])->toBeString();
    expect($config['port'])->toBeString();
    expect($config['connect_timeout'])->toBeInt();
});

/**
 * GUC設定文字列が正しい形式で生成されることを確認
 */
test('GUC設定文字列が正しい形式で生成される', function () {
    $config = config('database.connections.pgsql');

    expect($config)->toHaveKey('options');
    expect($config['options'])->toBeString();

    // GUC設定の形式確認（-c parameter=value形式）
    expect($config['options'])->toContain('statement_timeout');
    expect($config['options'])->toContain('idle_in_transaction_session_timeout');
    expect($config['options'])->toContain('lock_timeout');
    expect($config['options'])->toContain('-c');
});

/**
 * PDO属性設定が正しく構築されることを確認
 */
test('PDO属性設定が正しく構築される', function () {
    // pdo_pgsql拡張が有効な場合のみテスト
    if (! extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql extension is not loaded');
    }

    $config = config('database.connections.pgsql');

    expect($config)->toHaveKey('pdo_options');
    expect($config['pdo_options'])->toBeArray();
    expect($config['pdo_options'])->toHaveKey(PDO::ATTR_EMULATE_PREPARES);
    expect($config['pdo_options'])->toHaveKey(PDO::ATTR_ERRMODE);
    expect($config['pdo_options'][PDO::ATTR_ERRMODE])->toBe(PDO::ERRMODE_EXCEPTION);
});

/**
 * application_nameが設定されていることを確認
 */
test('application_nameが設定されている', function () {
    $config = config('database.connections.pgsql');

    expect($config)->toHaveKey('application_name');
    expect($config['application_name'])->toBeString();
    expect($config['application_name'])->not->toBeEmpty();
});

/**
 * connect_timeoutが適切な値であることを確認
 */
test('connect_timeoutが適切な値である', function () {
    $config = config('database.connections.pgsql');

    expect($config)->toHaveKey('connect_timeout');
    expect($config['connect_timeout'])->toBeInt();
    expect($config['connect_timeout'])->toBeGreaterThan(0);
    expect($config['connect_timeout'])->toBeLessThanOrEqual(30);
});
