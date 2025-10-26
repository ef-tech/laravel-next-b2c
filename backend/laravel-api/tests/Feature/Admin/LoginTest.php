<?php

declare(strict_types=1);

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // AdminSeederと同様のテストデータを作成
    $this->admin = Admin::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    $this->disabledAdmin = Admin::factory()->create([
        'email' => 'disabled@example.com',
        'password' => 'password',
        'role' => 'admin',
        'is_active' => false,
    ]);
});

describe('POST /api/v1/admin/login', function (): void {
    test('正しい認証情報で200 OKを返す', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'admin' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'is_active',
                ],
            ]);

        expect($response->json('admin.email'))->toBe('admin@example.com');
        expect($response->json('admin.role'))->toBe('super_admin');
        expect($response->json('admin.is_active'))->toBeTrue();
    });

    test('無効な認証情報で401 Unauthorizedを返す', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'code',
                'message',
                'errors',
                'trace_id',
            ]);

        expect($response->json('code'))->toBe('AUTH.INVALID_CREDENTIALS');
    });

    test('存在しないメールアドレスで401 Unauthorizedを返す', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'code',
                'message',
                'errors',
                'trace_id',
            ]);

        expect($response->json('code'))->toBe('AUTH.INVALID_CREDENTIALS');
    });

    test('無効化された管理者で403 Forbiddenを返す', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'disabled@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJsonStructure([
                'code',
                'message',
                'errors',
                'trace_id',
            ]);

        expect($response->json('code'))->toBe('AUTH.ACCOUNT_DISABLED');
    });

    test('バリデーションエラー時に422 Unprocessable Entityを返す - email必須', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'code',
                'message',
                'errors' => [
                    'email',
                ],
                'trace_id',
            ]);

        expect($response->json('code'))->toBe('VALIDATION_ERROR');
    });

    test('バリデーションエラー時に422 Unprocessable Entityを返す - email形式', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'code',
                'message',
                'errors' => [
                    'email',
                ],
                'trace_id',
            ]);

        expect($response->json('code'))->toBe('VALIDATION_ERROR');
    });

    test('バリデーションエラー時に422 Unprocessable Entityを返す - password必須', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'code',
                'message',
                'errors' => [
                    'password',
                ],
                'trace_id',
            ]);

        expect($response->json('code'))->toBe('VALIDATION_ERROR');
    });

    test('バリデーションエラー時に422 Unprocessable Entityを返す - password最低8文字', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'code',
                'message',
                'errors' => [
                    'password',
                ],
                'trace_id',
            ]);

        expect($response->json('code'))->toBe('VALIDATION_ERROR');
    });

    test('ログイン成功時にpersonal_access_tokensテーブルにトークンが保存される', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => Admin::class,
            'tokenable_id' => $this->admin->id,
            'name' => 'admin-token',
        ]);
    });

    test('ログイン成功時にtrace_idがレスポンスに含まれない（正常系）', function (): void {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        expect($response->json('trace_id'))->toBeNull();
    });
});
