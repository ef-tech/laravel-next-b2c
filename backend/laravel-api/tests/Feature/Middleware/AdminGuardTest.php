<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // ActiveなAdmin作成
    $this->admin = Admin::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
        'role' => 'super_admin',
        'is_active' => true,
    ]);
    $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;

    // InactiveなAdmin作成
    $this->inactiveAdmin = Admin::factory()->create([
        'email' => 'inactive@example.com',
        'password' => 'password',
        'role' => 'admin',
        'is_active' => false,
    ]);
    $this->inactiveAdminToken = $this->inactiveAdmin->createToken('admin-token')->plainTextToken;

    // User作成
    $this->user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);
    $this->userToken = $this->user->createToken('user-token')->plainTextToken;
});

test('AdminGuard → Admin型かつis_active=trueの場合にアクセスを許可する', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200);
});

test('AdminGuard → is_active=falseのAdminの場合に403 Forbiddenを返す', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->inactiveAdminToken}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(403)
        ->assertJsonStructure([
            'code',
            'message',
            'trace_id',
        ])
        ->assertJson([
            'code' => 'AUTH.ACCOUNT_DISABLED',
            'message' => 'アカウントが無効です',
        ]);
});

test('AdminGuard → User型トークンの場合に401 Unauthorizedを返す', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'code',
            'message',
            'trace_id',
        ])
        ->assertJson([
            'code' => 'AUTH.UNAUTHORIZED',
            'message' => '認証が必要です',
        ]);
});

test('AdminGuard → 未認証の場合に401 Unauthorizedを返す', function (): void {
    $response = $this->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(401);
});

test('AdminGuard → tokenable_typeがApp\Models\Adminであることを保証する', function (): void {
    // Adminトークンで正常アクセス
    $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200);

    // トークンのtokenable_typeを確認
    $token = $this->admin->tokens()->first();
    expect($token->tokenable_type)->toBe(Admin::class);
});
