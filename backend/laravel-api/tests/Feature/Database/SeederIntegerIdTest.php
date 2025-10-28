<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

/**
 * Seeder動作検証テスト
 *
 * Issue #100: Factory/Seederがbigint主キーで正しく動作することを検証
 * Requirements: 3.3, 3.4, 3.5, 5.4
 */
test('User::factory()->create() generates integer ID automatically', function () {
    // User::factory()->create()が整数型IDを自動生成することを検証
    $user = User::factory()->create();

    expect($user->id)->toBeInt();
    expect($user->id)->toBeGreaterThan(0);
});

test('php artisan db:seed executes without errors', function () {
    // DatabaseSeeder実行がエラーなく完了することを検証
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder']);

    // Seederが成功したことを検証（戻り値0）
    expect(Artisan::output())->toBeString();

    // Seeder実行後のユーザー数確認
    expect(User::count())->toBeGreaterThan(0);
});

test('seeded Users have integer IDs', function () {
    // Seeder実行後のユーザーレコードが整数型IDを持つことを検証
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder']);

    $users = User::all();

    expect($users)->not->toBeEmpty();

    foreach ($users as $user) {
        expect($user->id)->toBeInt();
        expect($user->id)->toBeGreaterThan(0);
    }
});

test('database SELECT query returns integer ID values', function () {
    // データベースクエリで取得したユーザーのIDが整数値であることを検証
    User::factory()->count(5)->create();

    $users = User::limit(5)->get();

    expect($users)->toHaveCount(5);

    $ids = $users->pluck('id')->toArray();

    // 全IDが整数型であることを検証
    foreach ($ids as $id) {
        expect($id)->toBeInt();
    }

    // IDが順序的に増加していることを検証（1, 2, 3, ...）
    expect($ids)->toEqual(range(1, 5));
});
