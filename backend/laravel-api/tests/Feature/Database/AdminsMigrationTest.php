<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('admins table has required columns', function () {
    expect(Schema::hasTable('admins'))->toBeTrue();

    expect(Schema::hasColumn('admins', 'id'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'name'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'email'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'email_verified_at'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'password'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'role'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'is_active'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'remember_token'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'created_at'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'updated_at'))->toBeTrue();
    expect(Schema::hasColumn('admins', 'deleted_at'))->toBeTrue();
});

test('admins email column has unique constraint', function () {
    $indexes = Schema::getIndexes('admins');

    $emailIndex = collect($indexes)->first(function ($index) {
        return in_array('email', $index['columns']) && $index['unique'];
    });

    expect($emailIndex)->not->toBeNull();
});

test('admins is_active column has index', function () {
    $indexes = Schema::getIndexes('admins');

    $isActiveIndex = collect($indexes)->first(function ($index) {
        return in_array('is_active', $index['columns']);
    });

    expect($isActiveIndex)->not->toBeNull();
});

test('admins role column exists and has correct type', function () {
    $columnType = Schema::getColumnType('admins', 'role');
    // SQLiteでは'varchar'として返される
    expect($columnType)->toBeIn(['string', 'varchar']);
});

test('admins is_active column exists and has correct type', function () {
    $columnType = Schema::getColumnType('admins', 'is_active');
    // SQLiteでは'tinyint'、PostgreSQLでは'bool'、MySQLでは'boolean'として返される
    expect($columnType)->toBeIn(['boolean', 'tinyint', 'bool']);
});
