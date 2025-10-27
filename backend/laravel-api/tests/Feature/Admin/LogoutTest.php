<?php

declare(strict_types=1);

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // 開発用管理者アカウント作成
    $this->admin = Admin::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    // トークン発行
    $this->token = $this->admin->createToken('admin-token')->plainTextToken;
});

test('POST /api/v1/admin/logout → 認証済み管理者が200 OKでログアウトできる', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/admin/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'ログアウトしました',
        ]);
});

test('POST /api/v1/admin/logout → personal_access_tokensテーブルからトークンが削除される', function (): void {
    // ログアウト前にトークンが存在することを確認
    expect($this->admin->tokens()->count())->toBe(1);

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/admin/logout');

    // ログアウト後にトークンが削除されることを確認
    expect($this->admin->fresh()->tokens()->count())->toBe(0);
});

test('POST /api/v1/admin/logout → 未認証の場合に401 Unauthorizedを返す', function (): void {
    $response = $this->postJson('/api/v1/admin/logout');

    $response->assertStatus(401);
});

test('POST /api/v1/admin/logout → 無効なトークンの場合に401 Unauthorizedを返す', function (): void {
    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->postJson('/api/v1/admin/logout');

    $response->assertStatus(401);
});

test('POST /api/v1/admin/logout → 他の管理者のトークンは影響を受けない', function (): void {
    // 別の管理者を作成
    $otherAdmin = Admin::factory()->create([
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $otherToken = $otherAdmin->createToken('admin-token')->plainTextToken;

    // 最初の管理者がログアウト
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/admin/logout');

    // 別の管理者のトークンは有効（ログアウトできることで確認）
    $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->postJson('/api/v1/admin/logout');

    $response->assertStatus(200);
});
