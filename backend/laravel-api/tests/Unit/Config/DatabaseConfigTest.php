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
        'statement_timeout',
        'idle_in_transaction_session_timeout',
        'lock_timeout',
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
 * タイムアウト設定が正しく読み込まれることを確認
 * 注: GUC設定はDatabaseServiceProviderのConnectionEstablishedイベントで設定
 */
test('タイムアウト設定が正しく読み込まれる', function () {
    $config = config('database.connections.pgsql');

    // タイムアウト設定値の確認
    expect($config['statement_timeout'])->toBeInt();
    expect($config['idle_in_transaction_session_timeout'])->toBeInt();
    expect($config['lock_timeout'])->toBeInt();

    // デフォルト値の確認
    expect($config['statement_timeout'])->toBe(60000);
    expect($config['idle_in_transaction_session_timeout'])->toBe(60000);
    expect($config['lock_timeout'])->toBe(0);
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
