<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

uses(RefreshDatabase::class);

describe('V1 Auth API - POST /api/v1/login', function () {
    test('正常な認証情報でトークンとユーザー情報を返す', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertHeader('X-API-Version', 'v1')
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
                'token_type',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => 'test@example.com',
                ],
            ]);

        expect($response->json('token'))->toBeString()->not->toBeEmpty();
    });

    test('無効なメールアドレスで401を返す', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = postJson('/api/v1/login', [
            'email' => 'invalid@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    });

    test('無効なパスワードで401を返す', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    });

    test('メールアドレスのバリデーションエラーで422を返す', function () {
        $response = postJson('/api/v1/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('パスワードが短すぎる場合422を返す', function () {
        $response = postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});

describe('V1 Auth API - POST /api/v1/logout', function () {
    test('認証済みユーザーをログアウトしトークンを削除する', function () {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->plainTextToken;

        $response = withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout');

        $response->assertOk()
            ->assertHeader('X-API-Version', 'v1')
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        expect($user->tokens()->count())->toBe(0);
    });

    test('未認証の場合401を返す', function () {
        $response = postJson('/api/v1/logout');

        $response->assertUnauthorized();
    });

    test('無効なトークンで401を返す', function () {
        $response = withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/v1/logout');

        $response->assertUnauthorized();
    });

    test('複数トークンがある場合は現在のトークンのみ削除する', function () {
        $user = User::factory()->create();
        $token1 = $user->createToken('Token 1')->plainTextToken;
        $token2 = $user->createToken('Token 2')->plainTextToken;

        expect($user->tokens()->count())->toBe(2);

        $response = withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/v1/logout');

        $response->assertOk();
        expect($user->tokens()->count())->toBe(1);
        expect($user->tokens()->first()->name)->toBe('Token 2');
    });
});

describe('V1 Auth API - GET /api/v1/user', function () {
    test('認証済みユーザー情報を返す', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $token = $user->createToken('Test Token')->plainTextToken;

        $response = withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/user');

        $response->assertOk()
            ->assertHeader('X-API-Version', 'v1')
            ->assertJson([
                'id' => $user->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        expect($response->json())->not->toHaveKey('password');
        expect($response->json())->not->toHaveKey('remember_token');
    });

    test('未認証の場合401を返す', function () {
        $response = getJson('/api/v1/user');

        $response->assertUnauthorized();
    });

    test('無効なトークンで401を返す', function () {
        $response = withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/user');

        $response->assertUnauthorized();
    });

    test('削除されたトークンで401を返す', function () {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->plainTextToken;

        $user->tokens()->delete();

        $response = withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/user');

        $response->assertUnauthorized();
    });
});
