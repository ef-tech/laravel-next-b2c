<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Userモデル動作検証テスト
 *
 * Issue #100: bigint主キーでのEloquent ORM動作確認
 * Requirements: 2.4, 2.5
 */
test('User model generates auto-incrementing integer IDs', function () {
    // User作成時にEloquent ORMが自動的に整数IDを生成することを検証
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // IDが整数型であることを検証
    expect($user->id)->toBeInt();
    expect($user->id)->toBeGreaterThan(0);
});

test('User model returns id as integer type', function () {
    // User作成後、$user->idが整数型として返されることを検証
    $user = User::factory()->create();

    // IDの型が整数であることを厳密に検証
    expect($user->id)->toBeInt();
    expect(is_int($user->id))->toBeTrue();

    // データベースから再取得してもIDが整数型であることを検証
    $retrievedUser = User::find($user->id);
    expect($retrievedUser->id)->toBeInt();
    expect($retrievedUser->id)->toBe($user->id);
});

test('User model has correct incrementing property', function () {
    // UserモデルのincrementingプロパティがLaravelデフォルト値（true）であることを検証
    $user = new User;

    // incrementingがtrueであることを検証（明示的に設定していないため、Laravelデフォルト値）
    expect($user->getIncrementing())->toBeTrue();
});

test('User model has correct key type', function () {
    // UserモデルのkeyTypeプロパティがLaravelデフォルト値（'int'）であることを検証
    $user = new User;

    // keyTypeが'int'であることを検証（明示的に設定していないため、Laravelデフォルト値）
    expect($user->getKeyType())->toBe('int');
});

test('multiple Users have sequential auto-incremented IDs', function () {
    // 複数のUser作成時にIDが順序的に増加することを検証
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    expect($user1->id)->toBeInt();
    expect($user2->id)->toBeInt();
    expect($user3->id)->toBeInt();

    // IDが順序的に増加していることを検証（SQLiteでは1, 2, 3...）
    expect($user2->id)->toBeGreaterThan($user1->id);
    expect($user3->id)->toBeGreaterThan($user2->id);
});

test('User can be retrieved by integer ID', function () {
    // 整数IDでUserを取得できることを検証
    $user = User::factory()->create();
    $userId = $user->id;

    // 整数IDでUserを取得
    $retrievedUser = User::find($userId);

    expect($retrievedUser)->not->toBeNull();
    expect($retrievedUser->id)->toBe($userId);
    expect($retrievedUser->email)->toBe($user->email);
});

test('User::create() generates integer ID without explicit id specification', function () {
    // User::create()でID明示指定なしでも整数IDが生成されることを検証
    $user = User::create([
        'name' => 'Direct Create User',
        'email' => 'direct@example.com',
        'password' => bcrypt('password'),
    ]);

    expect($user->id)->toBeInt();
    expect($user->id)->toBeGreaterThan(0);
});
