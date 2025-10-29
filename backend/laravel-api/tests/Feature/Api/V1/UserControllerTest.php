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
        ]);

        $response->assertStatus(201)
            ->assertHeader('X-API-Version', 'v1')
            ->assertJsonStructure(['id']);

        assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    });

    test('重複したメールアドレスで422を返す', function (): void {
        postJson('/api/v1/users', [
            'email' => 'duplicate@example.com',
            'name' => 'First User',
        ]);

        $response = postJson('/api/v1/users', [
            'email' => 'duplicate@example.com',
            'name' => 'Second User',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'email_already_exists',
            ]);
    });

    test('無効なメールアドレスで422を返す', function (): void {
        $response = postJson('/api/v1/users', [
            'email' => 'invalid-email',
            'name' => 'Test User',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('名前が短すぎる場合422を返す', function (): void {
        $response = postJson('/api/v1/users', [
            'email' => 'test@example.com',
            'name' => 'A',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    test('必須フィールドが不足している場合422を返す', function (): void {
        $response = postJson('/api/v1/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'name']);
    });
});
