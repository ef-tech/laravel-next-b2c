<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/**
 * PostgreSQLタイムアウト動作検証テスト
 *
 * 注意: このテストはPostgreSQL接続が必要です
 * SQLite環境では自動的にスキップされます
 */
beforeEach(function () {
    // PostgreSQL接続が利用可能かチェック
    if (config('database.default') !== 'pgsql') {
        test()->markTestSkipped('このテストはPostgreSQL接続が必要です');
    }

    if (! extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql拡張が有効ではありません');
    }
});

/**
 * statement_timeout超過テスト（長時間クエリ）
 *
 * 期待動作:
 * - タイムアウト時間を超えるクエリでSQLSTATE 57014エラー
 * - エラーメッセージに "statement timeout" を含む
 */
test('statement_timeout超過で適切なエラーが発生する', function () {
    $timeout = (int) config('database.connections.pgsql.statement_timeout', 60000);
    $sleepSeconds = ceil($timeout / 1000) + 1; // タイムアウト時間 + 1秒

    // タイムアウトを超えるクエリ実行
    expect(fn () => DB::select("SELECT pg_sleep({$sleepSeconds})"))
        ->toThrow(\PDOException::class, 'statement timeout');
})->skip(fn () => config('database.default') !== 'pgsql', 'PostgreSQL接続が必要');

/**
 * statement_timeout未超過テスト（正常範囲内クエリ）
 *
 * 期待動作:
 * - タイムアウト時間内のクエリは正常に実行される
 */
test('statement_timeout未超過のクエリは正常実行される', function () {
    // 1秒スリープ（タイムアウト60秒以内）
    $result = DB::select('SELECT pg_sleep(1) as sleep_result');

    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
})->skip(fn () => config('database.default') !== 'pgsql', 'PostgreSQL接続が必要');

/**
 * idle_in_transaction_session_timeout超過テスト（放置トランザクション）
 *
 * 期待動作:
 * - トランザクション内でタイムアウト時間以上アイドルでエラー
 * - エラーメッセージに "timeout" を含む
 *
 * 注: このテストは実行時間が長いため、通常はスキップされます
 */
test('idle_in_transaction_session_timeout超過で適切なエラーが発生する', function () {
    $timeout = (int) config('database.connections.pgsql.idle_in_transaction_session_timeout', 60000);
    $sleepSeconds = ceil($timeout / 1000) + 1; // タイムアウト時間 + 1秒

    DB::beginTransaction();

    try {
        // トランザクション内でアイドル状態を維持
        DB::select("SELECT pg_sleep({$sleepSeconds})");

        // ここには到達しない（タイムアウトエラーで中断）
        DB::rollBack();

        expect(false)->toBeTrue('タイムアウトエラーが発生すべき');
    } catch (\PDOException $e) {
        DB::rollBack();

        // idle_in_transaction_session_timeoutエラー確認
        expect($e->getMessage())
            ->toContain('timeout');
    }
})->skip('実行時間が長いためスキップ（手動実行推奨）');

/**
 * connect_timeout動作確認テスト
 *
 * 期待動作:
 * - 正常な接続は5秒以内に確立される
 * - 接続確立時間が connect_timeout 設定値以内である
 */
test('connect_timeoutの範囲内で接続が確立される', function () {
    $timeout = (int) config('database.connections.pgsql.connect_timeout', 5);

    $start = microtime(true);

    // 新しい接続を確立
    $pdo = DB::connection('pgsql')->getPdo();

    $elapsed = (microtime(true) - $start);

    expect($pdo)->toBeInstanceOf(\PDO::class);
    expect($elapsed)->toBeLessThan($timeout);
})->skip(fn () => config('database.default') !== 'pgsql', 'PostgreSQL接続が必要');

/**
 * lock_timeout動作確認テスト
 *
 * 期待動作:
 * - lock_timeout=0 でデッドロックを即座に検知
 * - SQLSTATE 40P01 または 55P03 エラー
 *
 * 注: このテストは複雑な並行処理が必要なため、簡易版を実装
 */
test('lock_timeout設定が正しく適用されている', function () {
    $lockTimeout = (int) config('database.connections.pgsql.lock_timeout', 0);

    // lock_timeout設定値の確認
    $result = DB::select('SHOW lock_timeout');

    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);

    // 設定値が適用されていることを確認（0 = 即座検知）
    $value = $result[0]->lock_timeout ?? '';
    expect($value)->toBe($lockTimeout === 0 ? '0' : "{$lockTimeout}ms");
})->skip(fn () => config('database.default') !== 'pgsql', 'PostgreSQL接続が必要');

/**
 * タイムアウト設定値の確認テスト
 *
 * 期待動作:
 * - statement_timeout, idle_in_transaction_session_timeout, lock_timeout が
 *   環境変数の値と一致している
 *
 * 注: PostgreSQLは値を人間が読みやすい形式（例: 60000ms → 1min）に変換して返す場合があります
 */
test('タイムアウト設定値がPostgreSQLセッションに正しく適用されている', function () {
    $expectedStatementTimeout = (int) config('database.connections.pgsql.statement_timeout', 60000);
    $expectedIdleTxTimeout = (int) config('database.connections.pgsql.idle_in_transaction_session_timeout', 60000);
    $expectedLockTimeout = (int) config('database.connections.pgsql.lock_timeout', 0);

    // PostgreSQLは値を人間が読みやすい形式に変換するため、ミリ秒単位で比較
    $convertToMs = function (string $value): int {
        // '0' の場合
        if ($value === '0') {
            return 0;
        }

        // 'Xmin' の場合（例: 1min）
        if (preg_match('/^(\d+)min$/', $value, $matches)) {
            return (int) $matches[1] * 60 * 1000;
        }

        // 'Xs' の場合（例: 5s）
        if (preg_match('/^(\d+)s$/', $value, $matches)) {
            return (int) $matches[1] * 1000;
        }

        // 'Xms' の場合（例: 60000ms）
        if (preg_match('/^(\d+)ms$/', $value, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    };

    // statement_timeout確認
    $result = DB::select('SHOW statement_timeout');
    $actualMs = $convertToMs($result[0]->statement_timeout ?? '0');
    expect($actualMs)->toBe($expectedStatementTimeout);

    // idle_in_transaction_session_timeout確認
    $result = DB::select('SHOW idle_in_transaction_session_timeout');
    $actualMs = $convertToMs($result[0]->idle_in_transaction_session_timeout ?? '0');
    expect($actualMs)->toBe($expectedIdleTxTimeout);

    // lock_timeout確認
    $result = DB::select('SHOW lock_timeout');
    $actualMs = $convertToMs($result[0]->lock_timeout ?? '0');
    expect($actualMs)->toBe($expectedLockTimeout);
})->skip(fn () => config('database.default') !== 'pgsql', 'PostgreSQL接続が必要');
