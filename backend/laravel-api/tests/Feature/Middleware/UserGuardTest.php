<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // テスト用ルートを登録
    Route::middleware(['auth:sanctum', 'user.guard'])
        ->get('/api/v1/test/user', function () {
            return response()->json(['success' => true]);
        });

    // User作成
    $this->user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);
    $this->userToken = $this->user->createToken('user-token')->plainTextToken;

    // Admin作成
    $this->admin = Admin::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
        'role' => 'super_admin',
        'is_active' => true,
    ]);
    $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;
});

test('UserGuard → User型の場合にアクセスを許可する', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
        ->getJson('/api/v1/test/user');

    $response->assertStatus(200);
});

test('UserGuard → Admin型トークンの場合に401 Unauthorizedを返す', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
        ->getJson('/api/v1/test/user');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthorized',
        ]);
});

test('UserGuard → 未認証の場合に401 Unauthorizedを返す', function (): void {
    $response = $this->getJson('/api/v1/test/user');

    $response->assertStatus(401);
});

test('UserGuard → tokenable_typeがApp\Models\Userであることを保証する', function (): void {
    // Userトークンで正常アクセス
    $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
        ->getJson('/api/v1/test/user');

    $response->assertStatus(200);

    // トークンのtokenable_typeを確認
    $token = $this->user->tokens()->first();
    expect($token->tokenable_type)->toBe(User::class);
});
