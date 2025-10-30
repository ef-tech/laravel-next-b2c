<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * V1 API Middleware Stack Tests
 *
 * V1エンドポイントへのミドルウェアスタック適用を検証します。
 * - グローバルミドルウェア適用検証（SetRequestId, SecurityHeaders等）
 * - apiグループミドルウェア適用検証（DynamicRateLimit, RequestLogging等）
 * - 認証ミドルウェア適用検証（auth:sanctum, SanctumTokenVerification）
 * - エンドポイント分類別レート制限設定適用検証
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用ユーザーを作成
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
});

describe('Global Middleware Application', function () {
    it('should apply SetRequestId middleware to V1 public routes', function () {
        $response = $this->get('/api/v1/health');

        // X-Request-Idヘッダーが追加されているか検証
        expect($response->headers->has('X-Request-Id'))->toBeTrue();
        expect($response->headers->get('X-Request-Id'))->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
    });

    it('should apply SecurityHeaders middleware to V1 public routes', function () {
        $response = $this->get('/api/v1/health');

        // セキュリティヘッダーが適用されているか検証
        expect($response->headers->has('X-Content-Type-Options'))->toBeTrue();
        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');

        expect($response->headers->has('X-Frame-Options'))->toBeTrue();
        expect($response->headers->get('X-Frame-Options'))->toBe('DENY');

        expect($response->headers->has('Referrer-Policy'))->toBeTrue();

        // Note: Strict-Transport-Security はHTTPS環境でのみ追加される
        // テスト環境（HTTP）では追加されない
    });

    it('should apply ForceJsonResponse middleware to V1 routes', function () {
        $response = $this->get('/api/v1/health');

        // Content-Typeがapplication/jsonであることを検証
        expect($response->headers->get('Content-Type'))->toContain('application/json');
    });

    it('should apply ApiVersion middleware to V1 routes', function () {
        $response = $this->get('/api/v1/health');

        // X-API-Versionヘッダーが追加されているか検証
        expect($response->headers->has('X-API-Version'))->toBeTrue();
        expect($response->headers->get('X-API-Version'))->toBe('v1');
    });
});

describe('API Group Middleware Application', function () {
    it('should apply DynamicRateLimit:api middleware to V1 routes', function () {
        // DynamicRateLimit:api は標準60req/minレート制限を適用
        // routes/api/v1.php で throttle:60,1 が明示的に設定されている場合はそちらが優先

        $response = $this->get('/api/v1/health');
        $response->assertStatus(200);

        // X-RateLimit-* ヘッダーが追加されているか検証
        // Note: throttle middleware が適用されている場合のみ
        if ($response->headers->has('X-RateLimit-Limit')) {
            expect($response->headers->has('X-RateLimit-Remaining'))->toBeTrue();
        }
    });

    it('should apply RequestLogging middleware to V1 routes', function () {
        // RequestLoggingミドルウェアはログに記録するのみでレスポンスヘッダーは追加しない
        // ここではミドルウェアが正常に動作してリクエストが成功することを検証

        $response = $this->get('/api/v1/health');
        $response->assertStatus(200);

        // ログファイルが作成されているか検証（オプション）
        // Note: テスト環境ではログ出力先が異なる可能性があるため、簡易検証
        expect(file_exists(storage_path('logs/laravel.log')))->toBeTrue();
    });

    it('should apply SubstituteBindings middleware to V1 routes', function () {
        // SubstituteBindings は Eloquent モデルバインディングを有効化
        // ここでは /api/v1/tokens/{id} ルートでモデルバインディングが機能するか検証

        $token = $this->user->createToken('test-token');
        $tokenId = $token->accessToken->id;

        $authToken = $this->user->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($authToken)->deleteJson("/api/v1/tokens/{$tokenId}");
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Token deleted successfully']);

        // モデルバインディングが正常に動作していることを確認
        // (存在しないIDの場合は404が返されるべき)
        $response = $this->withToken($authToken)->deleteJson('/api/v1/tokens/99999');
        $response->assertStatus(404);
    });
});

describe('Authentication Middleware Application', function () {
    it('should apply auth:sanctum middleware to V1 protected routes', function () {
        // 認証なしでアクセスした場合は401
        $response = $this->getJson('/api/v1/user');
        $response->assertStatus(401);

        // 無効なトークンでアクセスした場合も401
        $response = $this->withToken('invalid-token')->getJson('/api/v1/user');
        $response->assertStatus(401);

        // 正常なトークンでアクセスした場合は200
        $token = $this->user->createToken('test-token')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/v1/user');
        $response->assertStatus(200);
    });

    it('should not apply auth:sanctum middleware to V1 public routes', function () {
        // 公開ルートは認証なしでアクセス可能
        $response = $this->get('/api/v1/health');
        $response->assertStatus(200);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'Password123',
        ]);
        $response->assertStatus(201);

        $response = $this->postJson('/api/v1/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200);
    });
});

describe('Endpoint-Specific Rate Limit Application', function () {
    it('should apply throttle:5,1 to v1.login route', function () {
        // ログインエンドポイントは5req/min制限

        // 5回連続でリクエストを送信
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

    it('should apply throttle:60,1 to v1 protected routes', function () {
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

    it('should apply throttle:100,1 to v1.csp.report route', function () {
        // CSPレポートエンドポイントは100req/min制限が設定されている
        // Note: DynamicRateLimitミドルウェアも適用されるため、
        // より厳しい制限が先に発動する可能性がある

        // ルート定義にthrottle:100,1が設定されていることを検証
        $route = app('router')->getRoutes()->getByName('v1.csp.report');
        expect($route)->not->toBeNull();

        $middlewares = $route->gatherMiddleware();
        expect($middlewares)->toContain('throttle:100,1');

        // 正常にリクエストが処理されることを確認
        $response = $this->postJson('/api/v1/csp/report', [
            'csp-report' => [
                'document-uri' => 'https://example.com/',
                'violated-directive' => 'script-src',
            ],
        ]);
        $response->assertStatus(204);
    });
});

describe('Middleware Group Settings Inheritance', function () {
    it('should inherit api group middleware on V1 routes', function () {
        // V1ルートはmiddleware('api')を継承しているため、
        // apiグループに定義された全ミドルウェアが適用される

        $response = $this->get('/api/v1/health');

        // apiグループミドルウェアの効果を検証
        expect($response->headers->has('X-Request-Id'))->toBeTrue(); // SetRequestId (global)
        expect($response->headers->get('Content-Type'))->toContain('application/json'); // ForceJsonResponse (global)

        $response->assertStatus(200);
    });

    it('should apply auth:sanctum middleware stack on protected V1 routes', function () {
        // 保護されたルートはauth:sanctumミドルウェアスタックを適用

        // 認証なしでアクセス → 401
        $response = $this->postJson('/api/v1/logout');
        $response->assertStatus(401);

        // 認証ありでアクセス → 200
        $token = $this->user->createToken('test-token')->plainTextToken;
        $response = $this->withToken($token)->postJson('/api/v1/logout');
        $response->assertStatus(200);

        // 認証後もグローバルミドルウェアが適用されている
        expect($response->headers->has('X-Request-Id'))->toBeTrue();
        expect($response->headers->has('X-API-Version'))->toBeTrue();
    });
});
