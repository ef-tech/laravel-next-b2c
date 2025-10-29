<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * V1 API Routing Tests
 *
 * V1ルーティングファイル（routes/api/v1.php）の動作を検証します。
 * - V1エンドポイントのルート名検証
 * - V1エンドポイントのHTTPメソッド検証
 * - V1エンドポイントのミドルウェア適用検証
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用ユーザーを作成
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
});

describe('V1 Public Routes', function () {
    it('should register v1.health route for GET /api/v1/health', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.health');
        expect($route)->toBe(url('/api/v1/health'));

        // エンドポイントが正常に動作するか検証
        $response = $this->get('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    });

    it('should register v1.csp-report route for POST /api/v1/csp-report', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.csp-report');
        expect($route)->toBe(url('/api/v1/csp-report'));

        // エンドポイントが正常に動作するか検証（application/json形式でもOK）
        $response = $this->postJson('/api/v1/csp-report', [
            'csp-report' => [
                'document-uri' => 'https://example.com/',
                'violated-directive' => 'script-src',
            ],
        ]);
        $response->assertStatus(204); // noContent()
    });
});

describe('V1 Authentication Routes', function () {
    it('should register v1.register route for POST /api/v1/register', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.register');
        expect($route)->toBe(url('/api/v1/register'));

        // エンドポイントが正常に動作するか検証
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(201);
    });

    it('should register v1.login route for POST /api/v1/login', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.login');
        expect($route)->toBe(url('/api/v1/login'));

        // エンドポイントが正常に動作するか検証
        $response = $this->postJson('/api/v1/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
    });
});

describe('V1 Protected Routes', function () {
    it('should register v1.logout route for POST /api/v1/logout', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.logout');
        expect($route)->toBe(url('/api/v1/logout'));

        // 認証なしでアクセスした場合は401
        $response = $this->postJson('/api/v1/logout');
        $response->assertStatus(401);

        // Sanctum認証後は正常に動作
        $token = $this->user->createToken('test-token')->plainTextToken;
        $response = $this->withToken($token)->postJson('/api/v1/logout');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logged out successfully']);
    });

    it('should register v1.user route for GET /api/v1/user', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.user');
        expect($route)->toBe(url('/api/v1/user'));

        // 認証なしでアクセスした場合は401
        $response = $this->getJson('/api/v1/user');
        $response->assertStatus(401);

        // Sanctum認証後は正常に動作
        $token = $this->user->createToken('test-token')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/v1/user');
        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'name', 'email']);
    });

    it('should register v1.tokens.store route for POST /api/v1/tokens', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.tokens.store');
        expect($route)->toBe(url('/api/v1/tokens'));

        // 認証なしでアクセスした場合は401
        $response = $this->postJson('/api/v1/tokens', ['name' => 'test-token']);
        $response->assertStatus(401);

        // Sanctum認証後は正常に動作
        $token = $this->user->createToken('auth-token')->plainTextToken;
        $response = $this->withToken($token)->postJson('/api/v1/tokens', [
            'name' => 'new-token',
        ]);
        $response->assertStatus(201);
    });

    it('should register v1.tokens.index route for GET /api/v1/tokens', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.tokens.index');
        expect($route)->toBe(url('/api/v1/tokens'));

        // 認証なしでアクセスした場合は401
        $response = $this->getJson('/api/v1/tokens');
        $response->assertStatus(401);

        // Sanctum認証後は正常に動作
        $token = $this->user->createToken('test-token')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/v1/tokens');
        $response->assertStatus(200);
        $response->assertJsonStructure(['tokens']);
    });

    it('should register v1.tokens.destroy route for DELETE /api/v1/tokens/{id}', function () {
        // テスト用トークン作成
        $testToken = $this->user->createToken('to-delete-token');
        $tokenId = $testToken->accessToken->id;

        // ルート名でアクセス可能か検証
        $route = route('v1.tokens.destroy', ['id' => $tokenId]);
        expect($route)->toBe(url("/api/v1/tokens/{$tokenId}"));

        // 認証なしでアクセスした場合は401
        $response = $this->deleteJson("/api/v1/tokens/{$tokenId}");
        $response->assertStatus(401);

        // Sanctum認証後は正常に動作
        $authToken = $this->user->createToken('auth-token')->plainTextToken;
        $response = $this->withToken($authToken)->deleteJson("/api/v1/tokens/{$tokenId}");
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Token deleted successfully']);
    });

    it('should register v1.tokens.destroyAll route for DELETE /api/v1/tokens', function () {
        // ルート名でアクセス可能か検証
        $route = route('v1.tokens.destroyAll');
        expect($route)->toBe(url('/api/v1/tokens'));

        // 認証なしでアクセスした場合は401
        $response = $this->deleteJson('/api/v1/tokens');
        $response->assertStatus(401);

        // Sanctum認証後は正常に動作
        $token = $this->user->createToken('test-token')->plainTextToken;
        $response = $this->withToken($token)->deleteJson('/api/v1/tokens');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'All tokens deleted successfully']);
    });
});

describe('V1 Route Middleware', function () {
    it('should apply throttle:5,1 middleware to v1.login route', function () {
        // 5回連続でログインリクエストを送信
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => $this->user->email,
                'password' => 'password',
            ]);
            $response->assertStatus(200);
        }

        // 6回目はレート制限により429エラー
        $response = $this->postJson('/api/v1/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(429);
    });

    it('should apply throttle:60,1 middleware to v1 protected routes', function () {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // 60回連続でリクエストを送信
        for ($i = 0; $i < 60; $i++) {
            $response = $this->withToken($token)->getJson('/api/v1/user');
            $response->assertStatus(200);
        }

        // 61回目はレート制限により429エラー
        $response = $this->withToken($token)->getJson('/api/v1/user');
        $response->assertStatus(429);
    });

    it('should apply auth:sanctum middleware to v1 protected routes', function () {
        // 認証なしでアクセスした場合は401
        $response = $this->getJson('/api/v1/user');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/logout');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/tokens');
        $response->assertStatus(401);

        // 無効なトークンでアクセスした場合も401
        $response = $this->withToken('invalid-token')->getJson('/api/v1/user');
        $response->assertStatus(401);
    });
});
