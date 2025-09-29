<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * config/auth.phpがSanctum中心の設定に変更されていることをテスト
     */
    public function test_auth_config_uses_sanctum_as_default(): void
    {
        $authConfig = config('auth');

        // デフォルトガードがsanctumに設定されていることを確認
        $this->assertEquals('sanctum', $authConfig['defaults']['guard'], 'Default guard should be sanctum');

        // Sanctumガードが設定されていることを確認
        $this->assertArrayHasKey('sanctum', $authConfig['guards'], 'Sanctum guard should be configured');
        $this->assertEquals('sanctum', $authConfig['guards']['sanctum']['driver'], 'Sanctum guard should use sanctum driver');

        // APIガードもSanctumに設定されていることを確認
        $this->assertArrayHasKey('api', $authConfig['guards'], 'API guard should be configured');
        $this->assertEquals('sanctum', $authConfig['guards']['api']['driver'], 'API guard should use sanctum driver');
    }

    /**
     * UserモデルにHasApiTokensトレイトが追加されていることをテスト
     */
    public function test_user_model_has_api_tokens_trait(): void
    {
        $user = new User;

        // HasApiTokensトレイトのメソッドが使用可能であることを確認
        $this->assertTrue(method_exists($user, 'createToken'), 'User model should have createToken method from HasApiTokens');
        $this->assertTrue(method_exists($user, 'tokens'), 'User model should have tokens relationship from HasApiTokens');
    }

    /**
     * APIトークン認証の基本動作をテスト
     */
    public function test_api_token_authentication_works(): void
    {
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
    }

    /**
     * 認証なしでは401エラーになることをテスト
     */
    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * 無効なトークンでは401エラーになることをテスト
     */
    public function test_invalid_token_returns_401(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-12345',
        ])->getJson('/api/user');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * セッションベース認証設定が削除されていないことを確認（後方互換性）
     */
    public function test_web_guard_still_exists_for_compatibility(): void
    {
        $authConfig = config('auth');

        // webガードは後方互換性のため残されていることを確認
        $this->assertArrayHasKey('web', $authConfig['guards'], 'Web guard should exist for compatibility');
        $this->assertEquals('session', $authConfig['guards']['web']['driver'], 'Web guard should use session driver');
    }

    /**
     * トークンの有効期限設定が適切であることをテスト
     */
    public function test_token_expiration_configuration(): void
    {
        // Sanctum設定を確認
        $sanctumConfig = config('sanctum');

        // 設定ファイルが存在することを確認
        $this->assertIsArray($sanctumConfig, 'Sanctum configuration should be available');

        // デフォルトの有効期限設定を確認（null = 無期限、または適切な数値）
        $expiration = $sanctumConfig['expiration'] ?? null;
        $this->assertTrue(
            is_null($expiration) || is_numeric($expiration),
            'Token expiration should be null (no expiration) or numeric value'
        );
    }
}
