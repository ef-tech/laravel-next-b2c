<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ユーザー登録エンドポイント検証テスト
 *
 * Issue #100: bigint主キーでのAPI応答検証
 * Requirements: 8.1
 */
test('POST /api/users returns integer user ID in JSON response', function () {
    // ユーザー登録リクエストを送信
    $response = $this->postJson('/api/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated();

    // レスポンスJSONを取得
    $data = $response->json();

    // IDが整数型であることを検証
    expect($data)->toHaveKey('id');
    expect($data['id'])->toBeInt();

    // データベースにユーザーが作成されたことを確認
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->id)->toBeInt();
    expect($user->id)->toBeGreaterThan(0);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('registered users have sequential integer IDs in database', function () {
    // 複数のユーザーを登録
    $response1 = $this->postJson('/api/users', [
        'name' => 'User 1',
        'email' => 'user1@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response2 = $this->postJson('/api/users', [
        'name' => 'User 2',
        'email' => 'user2@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response3 = $this->postJson('/api/users', [
        'name' => 'User 3',
        'email' => 'user3@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response1->assertCreated();
    $response2->assertCreated();
    $response3->assertCreated();

    // データベースから実際のユーザーを取得
    $user1 = User::where('email', 'user1@example.com')->first();
    $user2 = User::where('email', 'user2@example.com')->first();
    $user3 = User::where('email', 'user3@example.com')->first();

    // 全てのIDが整数型であることを検証
    expect($user1->id)->toBeInt();
    expect($user2->id)->toBeInt();
    expect($user3->id)->toBeInt();

    // IDが順序的に増加していることを検証
    expect($user2->id)->toBeGreaterThan($user1->id);
    expect($user3->id)->toBeGreaterThan($user2->id);
});

test('user registration validates required fields', function () {
    // 必須フィールドなしでリクエスト
    $response = $this->postJson('/api/users', []);

    $response->assertUnprocessable();
    // nameとemailは必須（passwordはオプショナルの可能性）
    $response->assertJsonValidationErrors(['name', 'email']);
});

test('user registration rejects duplicate email', function () {
    // 最初のユーザーを登録
    User::factory()->create(['email' => 'existing@example.com']);

    // 同じメールアドレスで登録試行
    $response = $this->postJson('/api/users', [
        'name' => 'New User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // DDD層がEmailAlreadyExistsExceptionをスローするため422
    $response->assertUnprocessable();

    $data = $response->json();
    expect($data)->toHaveKey('message');
    expect($data['message'])->toContain('existing@example.com');
});
