<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * マイグレーション主キー型変更テスト
 *
 * Issue #100: UUID主キーからbigint主キーへの移行検証
 * Requirements: 1.1, 1.2, 1.3, 1.5, 1.6, 1.7, 1.8
 */
test('users table has bigint primary key after migration', function () {
    // マイグレーション実行後、usersテーブルのidカラムがbigint型であることを検証
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasColumn('users', 'id'))->toBeTrue();

    // カラム型確認（SQLiteではinteger、PostgreSQLではbigint）
    $columnType = Schema::getColumnType('users', 'id');
    $dbDriver = config('database.default');

    if ($dbDriver === 'sqlite') {
        expect($columnType)->toBe('integer');
    } else {
        // PostgreSQLでは'bigint'または'int8'を返す（ドライバーバージョンによる）
        expect($columnType)->toBeIn(['bigint', 'int8']);
    }
});

test('sessions table has bigint foreign key for user_id', function () {
    // マイグレーション実行後、sessionsテーブルのuser_idがbigint型であることを検証
    expect(Schema::hasTable('sessions'))->toBeTrue();
    expect(Schema::hasColumn('sessions', 'user_id'))->toBeTrue();

    $columnType = Schema::getColumnType('sessions', 'user_id');
    $dbDriver = config('database.default');

    if ($dbDriver === 'sqlite') {
        expect($columnType)->toBe('integer');
    } else {
        // PostgreSQLでは'bigint'または'int8'を返す（ドライバーバージョンによる）
        expect($columnType)->toBeIn(['bigint', 'int8']);
    }
});

test('personal_access_tokens table has bigint morphs for tokenable', function () {
    // マイグレーション実行後、personal_access_tokensテーブルのtokenable_idがbigint型であることを検証
    expect(Schema::hasTable('personal_access_tokens'))->toBeTrue();
    expect(Schema::hasColumn('personal_access_tokens', 'tokenable_id'))->toBeTrue();
    expect(Schema::hasColumn('personal_access_tokens', 'tokenable_type'))->toBeTrue();

    $tokenableIdType = Schema::getColumnType('personal_access_tokens', 'tokenable_id');
    $dbDriver = config('database.default');

    if ($dbDriver === 'sqlite') {
        expect($tokenableIdType)->toBe('integer');
    } else {
        // PostgreSQLでは'bigint'または'int8'を返す（ドライバーバージョンによる）
        expect($tokenableIdType)->toBeIn(['bigint', 'int8']);
    }

    $tokenableTypeType = Schema::getColumnType('personal_access_tokens', 'tokenable_type');
    // tokenable_typeはstring型（SQLiteではvarchar、PostgreSQLではstring）
    expect($tokenableTypeType)->toBeIn(['string', 'varchar']);
});

test('users table id column is auto-incrementing', function () {
    // User作成時にIDが自動インクリメントされることを検証
    $user1 = \App\Models\User::factory()->create();
    $user2 = \App\Models\User::factory()->create();

    expect($user1->id)->toBeInt();
    expect($user2->id)->toBeInt();
    expect($user2->id)->toBeGreaterThan($user1->id);
});
