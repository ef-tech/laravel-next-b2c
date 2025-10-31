<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('V1 User API - POST /api/v1/users', function () {
    test('ユーザー登録が正常に完了する', function (): void {
        $response = postJson('/api/v1/users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'SecurePassword123',
        ]);

        $response->assertStatus(201)
            ->assertHeader('X-API-Version', 'v1')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'created_at', 'updated_at']]);

        assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    });

    test('重複したメールアドレスで422を返す', function (): void {
        postJson('/api/v1/users', [
            'email' => 'duplicate@example.com',
            'name' => 'First User',
            'password' => 'Password123',
        ]);

        $response = postJson('/api/v1/users', [
            'email' => 'duplicate@example.com',
            'name' => 'Second User',
            'password' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('無効なメールアドレスで422を返す', function (): void {
        $response = postJson('/api/v1/users', [
            'email' => 'invalid-email',
            'name' => 'Test User',
            'password' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('名前が短すぎる場合422を返す', function (): void {
        $response = postJson('/api/v1/users', [
            'email' => 'test@example.com',
            'name' => 'A',
            'password' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('passwordが短すぎる場合422を返す', function (): void {
        $response = postJson('/api/v1/users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => '1234567', // 7文字
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('必須フィールドが不足している場合422を返す', function (): void {
        $response = postJson('/api/v1/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'name', 'password']);
    });
});
