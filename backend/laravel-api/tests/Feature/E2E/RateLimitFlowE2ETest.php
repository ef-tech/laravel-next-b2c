<?php

declare(strict_types=1);

use App\Http\Middleware\DynamicRateLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * Rate Limit Flow E2E Tests
 *
 * 完全なレート制限フローをEnd-to-Endで検証します。
 * - 実際のHTTPリクエストでレート制限チェックを実行
 * - 制限値到達時の429レスポンス検証
 * - HTTPヘッダーの正確性検証
 * - エンドポイント分類別のレート制限検証
 *
 * Requirements: 10.6, 10.8
 */
describe('Rate Limit Flow E2E Tests', function () {
    beforeEach(function () {
        // 設定をセットアップ
        config()->set('ratelimit', [
            'endpoint_types' => [
                'public_unauthenticated' => [
                    'max_attempts' => 5, // テスト用に低い値を設定
                    'decay_minutes' => 1,
                ],
                'protected_unauthenticated' => [
                    'max_attempts' => 3,
                    'decay_minutes' => 10,
                ],
                'public_authenticated' => [
                    'max_attempts' => 10,
                    'decay_minutes' => 1,
                ],
                'protected_authenticated' => [
                    'max_attempts' => 5,
                    'decay_minutes' => 1,
                ],
            ],
            'default' => [
                'max_attempts' => 5,
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

        // テスト用ルート設定（DynamicRateLimitミドルウェアのみを使用）
        Route::middleware([DynamicRateLimit::class])->group(function () {
            // 公開・未認証エンドポイント
            Route::get('/e2e/public', function () {
                return response()->json(['message' => 'public endpoint']);
            })->name('e2e.public');

            // 保護・未認証エンドポイント（ログイン）
            Route::post('/e2e/login', function () {
                return response()->json(['message' => 'login endpoint']);
            })->name('login');

            // 公開・認証済みエンドポイント
            Route::get('/e2e/authenticated', function () {
                return response()->json(['message' => 'authenticated endpoint']);
            })->middleware('auth:sanctum')->name('e2e.authenticated');

            // 保護・認証済みエンドポイント
            Route::post('/e2e/admin/action', function () {
                return response()->json(['message' => 'admin endpoint']);
            })->middleware('auth:sanctum')->name('admin.action');
        });
    });

    describe('公開・未認証エンドポイント（5 req/min）', function () {
        beforeEach(function () {
            Cache::store('array')->flush();
        });

        it('制限値以内のリクエストは正常に処理される', function () {
            for ($i = 0; $i < 5; $i++) {
                $response = $this->getJson('/e2e/public');
                $response->assertOk();
                $response->assertHeader('X-RateLimit-Limit', '5');
                $response->assertHeader('X-RateLimit-Policy', 'public_unauthenticated');

                $remaining = 5 - ($i + 1);
                $response->assertHeader('X-RateLimit-Remaining', (string) $remaining);
            }
        });

        it('制限値を超えたリクエストは429を返す', function () {
            // 5リクエスト送信して制限値に到達
            for ($i = 0; $i < 5; $i++) {
                $this->getJson('/e2e/public')->assertOk();
            }

            // 6リクエスト目は429を返す
            $response = $this->getJson('/e2e/public');
            $response->assertStatus(429);
            $response->assertHeader('Retry-After');
            $response->assertJsonStructure(['message', 'retry_after']);

            $json = $response->json();
            expect($json['message'])->toBe('Too Many Requests');
            expect($json['retry_after'])->toBeInt();
        });

        it('HTTPヘッダーが正しく設定される', function () {
            $response = $this->getJson('/e2e/public');
            $response->assertOk();

            // 標準ヘッダー
            $response->assertHeader('X-RateLimit-Limit', '5');
            $response->assertHeader('X-RateLimit-Remaining');
            $response->assertHeader('X-RateLimit-Reset');

            // 拡張ヘッダー
            $response->assertHeader('X-RateLimit-Policy', 'public_unauthenticated');
            $response->assertHeader('X-RateLimit-Key');
        });
    });

    describe('保護・未認証エンドポイント（3 req/10min）', function () {
        beforeEach(function () {
            Cache::store('array')->flush();
        });

        it('ログインエンドポイントは厳格なレート制限が適用される', function () {
            for ($i = 0; $i < 3; $i++) {
                $response = $this->postJson('/e2e/login', [
                    'email' => 'test@example.com',
                    'password' => 'password',
                ]);
                $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
                expect($response->headers->get('X-RateLimit-Limit'))->toBe('3');
            }
        });

        it('制限値3を超えると429を返す', function () {
            // 3リクエスト送信
            for ($i = 0; $i < 3; $i++) {
                $this->postJson('/e2e/login', [
                    'email' => 'test@example.com',
                    'password' => 'password',
                ]);
            }

            // 4リクエスト目は429を返す
            $response = $this->postJson('/e2e/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            $response->assertStatus(429);
            $response->assertHeader('Retry-After');
        });
    });

    describe('公開・認証済みエンドポイント（10 req/min）', function () {
        beforeEach(function () {
            Cache::store('array')->flush();
        });

        it('認証済みユーザーは高い制限値が適用される', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/e2e/authenticated');

            $response->assertOk();
            $response->assertHeader('X-RateLimit-Limit', '10');
            $response->assertHeader('X-RateLimit-Policy', 'public_authenticated');
        });

        it('10リクエスト成功後の11リクエスト目で429を返す', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            // 10リクエスト送信
            for ($i = 0; $i < 10; $i++) {
                $response = $this->withHeader('Authorization', "Bearer {$token}")
                    ->getJson('/e2e/authenticated');
                $response->assertOk();
            }

            // 11リクエスト目は429を返す
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/e2e/authenticated');
            $response->assertStatus(429);
            $response->assertJsonStructure(['message', 'retry_after']);
        });
    });

    describe('保護・認証済みエンドポイント（5 req/min）', function () {
        beforeEach(function () {
            Cache::store('array')->flush();
        });

        it('管理者エンドポイントは厳格な制限が適用される', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/e2e/admin/action', ['action' => 'test']);

            $response->assertOk();
            $response->assertHeader('X-RateLimit-Limit', '5');
            $response->assertHeader('X-RateLimit-Policy', 'protected_authenticated');
        });
    });

    describe('429レスポンスの詳細検証', function () {
        beforeEach(function () {
            Cache::store('array')->flush();
        });

        it('429レスポンスはRetry-Afterヘッダーを含む', function () {
            // 5リクエスト送信して制限値に到達
            for ($i = 0; $i < 5; $i++) {
                $this->getJson('/e2e/public')->assertOk();
            }

            // 6リクエスト目は429を返す
            $response = $this->getJson('/e2e/public');
            $response->assertStatus(429);
            $response->assertHeader('Retry-After');

            $retryAfter = $response->headers->get('Retry-After');
            expect($retryAfter)->toBeNumeric();
            expect((int) $retryAfter)->toBeGreaterThanOrEqual(0);
        });

        it('429レスポンスのJSONボディは正しい形式である', function () {
            // 5リクエスト送信
            for ($i = 0; $i < 5; $i++) {
                $this->getJson('/e2e/public')->assertOk();
            }

            // 6リクエスト目
            $response = $this->getJson('/e2e/public');
            $response->assertStatus(429);

            $json = $response->json();
            expect($json)->toHaveKeys(['message', 'retry_after']);
            expect($json['message'])->toBe('Too Many Requests');
            expect($json['retry_after'])->toBeInt();
            expect($json['retry_after'])->toBeGreaterThanOrEqual(0);
        });
    });

});
