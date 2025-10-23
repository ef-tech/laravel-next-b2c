<?php

declare(strict_types=1);

use App\Http\Middleware\DynamicRateLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

describe('DynamicRateLimit Middleware - Feature Tests', function () {
    beforeEach(function () {
        // テスト用キャッシュストアをクリア
        Cache::store('array')->flush();

        // 設定をセットアップ
        config()->set('ratelimit', [
            'endpoint_types' => [
                'public_unauthenticated' => [
                    'max_attempts' => 60,
                    'decay_minutes' => 1,
                ],
                'protected_unauthenticated' => [
                    'max_attempts' => 5,
                    'decay_minutes' => 10,
                ],
                'public_authenticated' => [
                    'max_attempts' => 120,
                    'decay_minutes' => 1,
                ],
                'protected_authenticated' => [
                    'max_attempts' => 30,
                    'decay_minutes' => 1,
                ],
            ],
            'default' => [
                'max_attempts' => 30,
                'decay_minutes' => 1,
            ],
            'protected_routes' => [
                'login',
                'register',
                'password.*',
                'admin.*',
                'payment.*',
            ],
            'cache' => [
                'store' => 'array',
            ],
        ]);

        // テスト用ルート設定
        Route::middleware(['api', DynamicRateLimit::class])->group(function () {
            // 公開・未認証エンドポイント
            Route::get('/test/public', function () {
                return response()->json(['message' => 'public endpoint']);
            })->name('test.public');

            // 保護・未認証エンドポイント（ログイン）
            Route::post('/test/login', function () {
                return response()->json(['message' => 'login endpoint']);
            })->name('login'); // protected_routes設定の'login'パターンにマッチ

            // 公開・認証済みエンドポイント
            Route::get('/test/authenticated', function () {
                return response()->json(['message' => 'authenticated endpoint']);
            })->middleware('auth:sanctum')->name('test.authenticated');

            // 保護・認証済みエンドポイント
            Route::get('/test/protected', function () {
                return response()->json(['message' => 'protected endpoint']);
            })->middleware('auth:sanctum')->name('admin.test'); // protected_routes設定の'admin.*'パターンにマッチ
        });
    });

    describe('公開・未認証エンドポイント（60 req/min）', function () {
        it('制限値以内のリクエストは許可される', function () {
            for ($i = 0; $i < 5; $i++) {
                $response = $this->getJson('/test/public');
                $response->assertOk();
                $response->assertHeader('X-RateLimit-Limit');
                $response->assertHeader('X-RateLimit-Remaining');
                $response->assertHeader('X-RateLimit-Reset');
            }
        });

        it('制限値を超えたリクエストは429を返す', function () {
            // キャッシュに制限値+1の値を設定
            $key = 'rate_limit:public_unauthenticated:ip_'.$this->app['request']->ip();
            Cache::store('array')->put($key, 61, 60);

            $response = $this->getJson('/test/public');
            $response->assertStatus(429);
            $response->assertHeader('Retry-After');
            $response->assertJsonStructure(['message', 'retry_after']);
        });

        it('HTTPヘッダーが正しく設定される', function () {
            $response = $this->getJson('/test/public');
            $response->assertOk();
            $response->assertHeader('X-RateLimit-Limit', '60');
            $response->assertHeader('X-RateLimit-Policy', 'public_unauthenticated');
            $response->assertHeader('X-RateLimit-Key');
        });
    });

    describe('保護・未認証エンドポイント（5 req/10min）', function () {
        it('ログインエンドポイントは厳格なレート制限が適用される', function () {
            for ($i = 0; $i < 5; $i++) {
                $response = $this->postJson('/test/login', [
                    'email' => 'test@example.com',
                    'password' => 'password',
                ]);
                // 実際のログイン処理は実装していないため、エラーレスポンス期待
                // ただし、レート制限ヘッダーは設定されている
                $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
            }
        });

        it('制限値5を超えると429を返す', function () {
            $emailHash = hash('sha256', 'test@example.com');
            $ip = $this->app['request']->ip();
            $key = "rate_limit:protected_unauthenticated:ip_{$ip}_email_{$emailHash}";
            Cache::store('array')->put($key, 6, 600);

            $response = $this->postJson('/test/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            $response->assertStatus(429);
            $response->assertHeader('Retry-After');
        });
    });

    describe('公開・認証済みエンドポイント（120 req/min）', function () {
        it('認証済みユーザーは高い制限値が適用される', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/test/authenticated');

            $response->assertOk();
            $response->assertHeader('X-RateLimit-Limit', '120');
            $response->assertHeader('X-RateLimit-Policy', 'public_authenticated');
        });
    });

    describe('保護・認証済みエンドポイント（30 req/min）', function () {
        it('保護エンドポイントは厳格な制限が適用される', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/test/protected');

            $response->assertOk();
            $response->assertHeader('X-RateLimit-Limit', '30');
            $response->assertHeader('X-RateLimit-Policy', 'protected_authenticated');
        });
    });

    describe('HTTPレスポンスヘッダー検証', function () {
        it('全てのレート制限ヘッダーが設定される', function () {
            $response = $this->getJson('/test/public');
            $response->assertOk();

            // 標準ヘッダー
            $response->assertHeader('X-RateLimit-Limit');
            $response->assertHeader('X-RateLimit-Remaining');
            $response->assertHeader('X-RateLimit-Reset');

            // 拡張ヘッダー
            $response->assertHeader('X-RateLimit-Policy');
            $response->assertHeader('X-RateLimit-Key');
        });

        it('429レスポンス時はRetry-Afterヘッダーが設定される', function () {
            $key = 'rate_limit:public_unauthenticated:ip_'.$this->app['request']->ip();
            Cache::store('array')->put($key, 61, 60);

            $response = $this->getJson('/test/public');
            $response->assertStatus(429);
            $response->assertHeader('Retry-After');

            // JSONボディ検証
            $response->assertJsonStructure([
                'message',
                'retry_after',
            ]);

            $json = $response->json();
            expect($json['message'])->toBe('Too Many Requests');
            expect($json['retry_after'])->toBeInt();
        });
    });

    afterEach(function () {
        Cache::store('array')->flush();
    });
});
