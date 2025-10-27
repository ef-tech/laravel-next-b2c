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

test('GET /api/v1/admin/dashboard → 認証済み管理者が200 OKでダッシュボード情報を取得できる', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200);
});

test('GET /api/v1/admin/dashboard → 正しいAdmin データ構造を返す（id, email, name, role, isActive）', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'admin' => [
                'id',
                'email',
                'name',
                'role',
                'isActive',
            ],
        ])
        ->assertJson([
            'admin' => [
                'id' => $this->admin->id,
                'email' => 'admin@example.com',
                'role' => 'super_admin',
                'isActive' => true,
            ],
        ]);
});

test('GET /api/v1/admin/dashboard → 未認証の場合に401 Unauthorizedを返す', function (): void {
    $response = $this->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(401);
});

test('GET /api/v1/admin/dashboard → 無効なトークンの場合に401 Unauthorizedを返す', function (): void {
    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(401);
});

test('GET /api/v1/admin/dashboard → 非アクティブ管理者でもダッシュボード情報を取得できる', function (): void {
    // 非アクティブ管理者を作成
    $inactiveAdmin = Admin::factory()->create([
        'email' => 'inactive@example.com',
        'password' => 'password',
        'role' => 'admin',
        'is_active' => false,
    ]);
    $inactiveToken = $inactiveAdmin->createToken('admin-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$inactiveToken}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200)
        ->assertJson([
            'admin' => [
                'id' => $inactiveAdmin->id,
                'email' => 'inactive@example.com',
                'role' => 'admin',
                'isActive' => false,
            ],
        ]);
});
