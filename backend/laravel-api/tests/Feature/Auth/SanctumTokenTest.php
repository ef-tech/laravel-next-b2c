<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

/**
 * Laravel Sanctumトークン発行検証テスト
 *
 * Issue #100: bigint主キーでのSanctumトークン発行検証
 * Requirements: 5.5
 */
test('Sanctum token is issued with bigint tokenable_id', function () {
    // ユーザーを作成
    $user = User::factory()->create([
        'email' => 'sanctum@example.com',
        'password' => bcrypt('password'),
    ]);

    // Sanctumトークンを発行
    $token = $user->createToken('test-token');

    expect($token)->not->toBeNull();
    expect($token->plainTextToken)->toBeString();
    expect($token->accessToken)->toBeInstanceOf(PersonalAccessToken::class);

    // personal_access_tokensテーブルからトークン情報を取得
    $personalAccessToken = PersonalAccessToken::where('tokenable_id', $user->id)
        ->where('tokenable_type', User::class)
        ->first();

    expect($personalAccessToken)->not->toBeNull();
    expect($personalAccessToken->tokenable_id)->toBeInt();
    expect($personalAccessToken->tokenable_id)->toBe($user->id);
    expect($personalAccessToken->tokenable_type)->toBe(User::class);
});

test('tokenable_id in personal_access_tokens is integer type', function () {
    // ユーザーを作成してトークン発行
    $user = User::factory()->create();
    $user->createToken('verification-token');

    // データベースから直接レコードを取得
    $tokenRecord = DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->first();

    expect($tokenRecord)->not->toBeNull();

    // tokenable_idが整数型であることを検証
    expect($tokenRecord->tokenable_id)->toBeInt();
    expect($tokenRecord->tokenable_id)->toBe($user->id);
});

test('multiple tokens can be issued for same user with bigint ID', function () {
    // ユーザーを作成
    $user = User::factory()->create();

    // 複数のトークンを発行
    $token1 = $user->createToken('token-1');
    $token2 = $user->createToken('token-2');
    $token3 = $user->createToken('token-3');

    expect($token1->plainTextToken)->toBeString();
    expect($token2->plainTextToken)->toBeString();
    expect($token3->plainTextToken)->toBeString();

    // 全てのトークンが同じユーザーIDに紐づいていることを検証
    $tokens = PersonalAccessToken::where('tokenable_id', $user->id)->get();

    expect($tokens)->toHaveCount(3);

    foreach ($tokens as $token) {
        expect($token->tokenable_id)->toBeInt();
        expect($token->tokenable_id)->toBe($user->id);
        expect($token->tokenable_type)->toBe(User::class);
    }
});

test('token is associated with user via bigint foreign key', function () {
    // ユーザーを作成してトークン発行
    $user = User::factory()->create([
        'email' => 'auth@example.com',
        'password' => bcrypt('password'),
    ]);

    $token = $user->createToken('auth-token');

    // トークンからユーザーを取得できることを検証
    $personalAccessToken = PersonalAccessToken::find($token->accessToken->id);

    expect($personalAccessToken->tokenable_id)->toBeInt();
    expect($personalAccessToken->tokenable_id)->toBe($user->id);

    // リレーションを通じてユーザーを取得
    $tokenableUser = $personalAccessToken->tokenable;

    expect($tokenableUser)->toBeInstanceOf(User::class);
    expect($tokenableUser->id)->toBe($user->id);
    expect($tokenableUser->email)->toBe($user->email);
});

test('token revocation works correctly with bigint tokenable_id', function () {
    // ユーザーを作成してトークン発行
    $user = User::factory()->create();
    $token = $user->createToken('revoke-test');

    // トークンが存在することを確認
    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);

    // トークンを削除
    $user->tokens()->delete();

    // トークンが削除されたことを確認
    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(0);
});
