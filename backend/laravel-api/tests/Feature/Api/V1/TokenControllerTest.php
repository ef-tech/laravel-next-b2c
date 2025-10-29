<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

uses(RefreshDatabase::class);

describe('V1 Token API - POST /api/v1/tokens', function () {
    test('新しいトークンを作成できる', function () {
        $user = User::factory()->create();
        $token = $user->createToken('Auth Token')->plainTextToken;

        $response = withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tokens', ['name' => 'New Token']);

        $response->assertStatus(201)
            ->assertHeader('X-API-Version', 'v1')
            ->assertJsonStructure(['token', 'name', 'created_at'])
            ->assertJson(['name' => 'New Token']);

        expect($response->json('token'))->toBeString()->not->toBeEmpty();
    });

    test('トークン名を省略した場合デフォルト名が設定される', function () {
        $user = User::factory()->create();
        $token = $user->createToken('Auth Token')->plainTextToken;

        $response = withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tokens');

        $response->assertStatus(201)
            ->assertJson(['name' => 'API Token']);
    });

    test('未認証の場合401を返す', function () {
        $response = postJson('/api/v1/tokens', ['name' => 'New Token']);

        $response->assertUnauthorized();
    });
});

describe('V1 Token API - GET /api/v1/tokens', function () {
    test('全トークン一覧を取得できる', function () {
        $user = User::factory()->create();
        $token1 = $user->createToken('Token 1')->plainTextToken;
        $user->createToken('Token 2');

        $response = withHeader('Authorization', "Bearer {$token1}")
            ->getJson('/api/v1/tokens');

        $response->assertOk()
            ->assertHeader('X-API-Version', 'v1')
            ->assertJsonStructure([
                'tokens' => [
                    '*' => ['id', 'name', 'created_at', 'last_used_at'],
                ],
            ]);

        expect($response->json('tokens'))->toHaveCount(2);
    });

    test('未認証の場合401を返す', function () {
        $response = getJson('/api/v1/tokens');

        $response->assertUnauthorized();
    });
});

describe('V1 Token API - DELETE /api/v1/tokens/{id}', function () {
    test('特定のトークンを削除できる', function () {
        $user = User::factory()->create();
        $token1 = $user->createToken('Token 1')->plainTextToken;
        $token2 = $user->createToken('Token 2');

        expect($user->tokens()->count())->toBe(2);

        $response = withHeader('Authorization', "Bearer {$token1}")
            ->deleteJson("/api/v1/tokens/{$token2->accessToken->id}");

        $response->assertOk()
            ->assertHeader('X-API-Version', 'v1')
            ->assertJson(['message' => 'Token deleted successfully']);

        expect($user->tokens()->count())->toBe(1);
    });

    test('存在しないトークンIDで404を返す', function () {
        $user = User::factory()->create();
        $token = $user->createToken('Token 1')->plainTextToken;

        $response = withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/tokens/999999');

        $response->assertNotFound()
            ->assertJson(['message' => 'Token not found']);
    });

    test('未認証の場合401を返す', function () {
        $response = deleteJson('/api/v1/tokens/1');

        $response->assertUnauthorized();
    });
});

describe('V1 Token API - DELETE /api/v1/tokens', function () {
    test('現在のトークン以外の全トークンを削除できる', function () {
        $user = User::factory()->create();
        $token1 = $user->createToken('Token 1')->plainTextToken;
        $user->createToken('Token 2');
        $user->createToken('Token 3');

        expect($user->tokens()->count())->toBe(3);

        $response = withHeader('Authorization', "Bearer {$token1}")
            ->deleteJson('/api/v1/tokens');

        $response->assertOk()
            ->assertHeader('X-API-Version', 'v1')
            ->assertJson(['message' => 'All tokens deleted successfully']);

        expect($user->tokens()->count())->toBe(1);
        expect($user->tokens()->first()->name)->toBe('Token 1');
    });

    test('未認証の場合401を返す', function () {
        $response = deleteJson('/api/v1/tokens');

        $response->assertUnauthorized();
    });
});
