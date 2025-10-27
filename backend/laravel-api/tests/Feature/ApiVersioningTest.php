<?php

declare(strict_types=1);

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = Admin::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
        'role' => 'super_admin',
        'is_active' => true,
    ]);
});

describe('API v1エンドポイント', function (): void {
    test('POST /api/v1/admin/login → v1プレフィックス付きでログイン成功', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'admin' => ['id', 'name', 'email', 'role', 'is_active'],
                'token',
            ]);
    });

    test('GET /api/v1/admin/dashboard → v1プレフィックス付きでダッシュボードアクセス成功', function (): void {
        $token = $this->admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'admin' => ['id', 'name', 'email', 'role', 'isActive'],
            ]);
    });

    test('POST /api/v1/admin/logout → v1プレフィックス付きでログアウト成功', function (): void {
        $token = $this->admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'ログアウトしました',
            ]);
    });
});

describe('ルート名検証', function (): void {
    test('admin.loginルートがv1.admin.loginとして登録されている', function (): void {
        $route = route('v1.admin.login');

        expect($route)->toBe(config('app.url').'/api/v1/admin/login');
    });

    test('admin.logoutルートがv1.admin.logoutとして登録されている', function (): void {
        $route = route('v1.admin.logout');

        expect($route)->toBe(config('app.url').'/api/v1/admin/logout');
    });

    test('admin.dashboardルートがv1.admin.dashboardとして登録されている', function (): void {
        $route = route('v1.admin.dashboard');

        expect($route)->toBe(config('app.url').'/api/v1/admin/dashboard');
    });
});

describe('APIバージョン分離', function (): void {
    test('v1エンドポイントは独立して動作する', function (): void {
        // v1エンドポイントでログイン
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $token = $response->json('token');

        // 同じトークンでv1ダッシュボードにアクセス
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
    });

    test('v1エンドポイントは正しいContent-Typeを返す', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    });

    test('v1エンドポイントはX-RateLimit-Limitヘッダーを返す', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit');
    });
});
