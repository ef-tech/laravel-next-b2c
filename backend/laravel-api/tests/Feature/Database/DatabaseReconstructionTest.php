<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * データベース完全再構築検証テスト
 *
 * Issue #100: bigint主キーでのデータベース再構築完全検証
 * Requirements: 5.1, 5.2, 5.3
 */
test('database has correct structure after migration', function () {
    // RefreshDatabaseトレイトが既にマイグレーションを実行しているため、
    // ここではテーブル構造が正しいことを検証する

    // ユーザーテーブルが存在することを検証
    expect(Schema::hasTable('users'))->toBeTrue();

    // Seederを実行してユーザーが作成されることを確認
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder']);

    expect(User::count())->toBeGreaterThan(0);
});

test('users table has bigint primary key after full reconstruction', function () {
    // PostgreSQL/SQLite環境でusersテーブルのidカラムがbigint/integer型であることを検証
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasColumn('users', 'id'))->toBeTrue();

    $columnType = Schema::getColumnType('users', 'id');
    $dbDriver = config('database.default');

    // データベースドライバーに応じた型チェック
    if ($dbDriver === 'sqlite') {
        // SQLiteではintegerとして報告される
        expect($columnType)->toBe('integer');
    } else {
        // PostgreSQLではbigintとして報告される
        expect($columnType)->toBe('bigint');
    }
});

test('User::first() returns integer ID after seeding', function () {
    // Seeder実行後、最初のユーザーレコードが整数型IDを持つことを検証
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder']);

    $firstUser = User::first();

    expect($firstUser)->not->toBeNull();
    expect($firstUser->id)->toBeInt();
    expect($firstUser->id)->toBeGreaterThan(0);
});

test('seeded users have sequential auto-incremented IDs', function () {
    // 複数のユーザーが順序的にインクリメントされた整数IDを持つことを検証
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder']);

    // 追加のユーザーを作成
    $user1 = User::factory()->create(['email' => 'sequential1@example.com']);
    $user2 = User::factory()->create(['email' => 'sequential2@example.com']);
    $user3 = User::factory()->create(['email' => 'sequential3@example.com']);

    expect($user1->id)->toBeInt();
    expect($user2->id)->toBeInt();
    expect($user3->id)->toBeInt();

    // IDが順序的に増加していることを検証
    expect($user2->id)->toBeGreaterThan($user1->id);
    expect($user3->id)->toBeGreaterThan($user2->id);
});

test('all database tables have correct schema after reconstruction', function () {
    // 全ての主要テーブルが正しいスキーマを持つことを検証
    $expectedTables = [
        'users',
        'sessions',
        'personal_access_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    foreach ($expectedTables as $table) {
        expect(Schema::hasTable($table))
            ->toBeTrue("Table {$table} should exist");
    }

    // personal_access_tokensのtokenable_idがbigint/integer型であることを検証
    expect(Schema::hasColumn('personal_access_tokens', 'tokenable_id'))->toBeTrue();

    $tokenableIdType = Schema::getColumnType('personal_access_tokens', 'tokenable_id');
    $dbDriver = config('database.default');

    if ($dbDriver === 'sqlite') {
        expect($tokenableIdType)->toBe('integer');
    } else {
        expect($tokenableIdType)->toBe('bigint');
    }

    // sessionsのuser_idがbigint/integer型であることを検証
    expect(Schema::hasColumn('sessions', 'user_id'))->toBeTrue();

    $userIdType = Schema::getColumnType('sessions', 'user_id');

    if ($dbDriver === 'sqlite') {
        expect($userIdType)->toBe('integer');
    } else {
        expect($userIdType)->toBe('bigint');
    }
});
