<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * config/auth.phpがSanctum中心の設定に変更されていることをテスト
 */
it('auth_config_uses_sanctum_as_default', function () {
    $authConfig = config('auth');

    // デフォルトガードがsanctumに設定されていることを確認
    expect($authConfig['defaults']['guard'])->toBe('sanctum', 'Default guard should be sanctum');

    // Sanctumガードが設定されていることを確認
    expect($authConfig['guards'])->toHaveKey('sanctum', 'Sanctum guard should be configured');
    expect($authConfig['guards']['sanctum']['driver'])->toBe('sanctum', 'Sanctum guard should use sanctum driver');

    // APIガードもSanctumに設定されていることを確認
    expect($authConfig['guards'])->toHaveKey('api', 'API guard should be configured');
    expect($authConfig['guards']['api']['driver'])->toBe('sanctum', 'API guard should use sanctum driver');
});

/**
 * UserモデルにHasApiTokensトレイトが追加されていることをテスト
 */
it('user_model_has_api_tokens_trait', function () {
    $user = new User;

    // HasApiTokensトレイトのメソッドが使用可能であることを確認
    expect($user)->toHaveMethod('createToken', 'User model should have createToken method from HasApiTokens');
    expect($user)->toHaveMethod('tokens', 'User model should have tokens relationship from HasApiTokens');
});

/**
 * APIトークン認証の基本動作をテスト
 */
it('api_token_authentication_works', function () {
    // テストユーザーを作成
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // APIトークンを作成
    $token = $user->createToken('test-token');

    // トークンを使ってAPIエンドポイントにアクセス
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson('/api/user');

    $response->assertStatus(200);
    $response->assertJson([
        'id' => $user->id,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});

/**
 * 認証なしでは401エラーになることをテスト
 */
it('api_requires_authentication', function () {
    $response = $this->getJson('/api/user');
    $response->assertStatus(401);
    $response->assertJson(['message' => 'Unauthenticated.']);
});

/**
 * 無効なトークンでは401エラーになることをテスト
 */
it('invalid_token_returns_401', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-token-12345',
    ])->getJson('/api/user');

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Unauthenticated.']);
});

/**
 * セッションベース認証設定が削除されていないことを確認（後方互換性）
 */
it('web_guard_still_exists_for_compatibility', function () {
    $authConfig = config('auth');

    // webガードは後方互換性のため残されていることを確認
    expect($authConfig['guards'])->toHaveKey('web', 'Web guard should exist for compatibility');
    expect($authConfig['guards']['web']['driver'])->toBe('session', 'Web guard should use session driver');
});

/**
 * トークンの有効期限設定が適切であることをテスト
 */
it('token_expiration_configuration', function () {
    // Sanctum設定を確認
    $sanctumConfig = config('sanctum');

    // 設定ファイルが存在することを確認
    expect($sanctumConfig)->toBeArray('Sanctum configuration should be available');

    // デフォルトの有効期限設定を確認（null = 無期限、または適切な数値）
    $expiration = $sanctumConfig['expiration'] ?? null;
    expect(is_null($expiration) || is_numeric($expiration))->toBeTrue('Token expiration should be null (no expiration) or numeric value');
});
