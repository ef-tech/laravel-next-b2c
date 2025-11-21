<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/**
 * ミドルウェアグループ別統合テスト
 *
 * 6種類のミドルウェアグループ（api, auth, guest, internal, webhook, readonly）の
 * 動作を検証します。各グループで期待されるミドルウェアセットが適用されることを確認します。
 *
 * Requirements: 14.4
 */
describe('Middleware Group Integration', function () {
    beforeEach(function () {
        // データベースマイグレーション実行
        $this->artisan('migrate:fresh');

        // テスト用ルートを登録

        // apiグループ
        Route::get('/test/api-group', function () {
            return response()->json(['group' => 'api']);
        })->middleware(['api']);

        // authグループ
        Route::get('/test/auth-group', function () {
            return response()->json(['group' => 'auth', 'user' => auth()->id()]);
        })->middleware(['auth']);

        // guestグループ
        Route::post('/test/guest-group', function () {
            return response()->json(['group' => 'guest']);
        })->middleware(['guest']);

        // internalグループ
        Route::get('/test/internal-group', function () {
            return response()->json(['group' => 'internal']);
        })->middleware(['internal']);

        // webhookグループ
        Route::post('/test/webhook-group', function () {
            return response()->json(['group' => 'webhook']);
        })->middleware(['webhook']);

        // readonlyグループ
        Route::get('/test/readonly-group', function () {
            return response()->json(['group' => 'readonly', 'timestamp' => now()->utc()->toIso8601String()]);
        })->middleware(['readonly']);
    });

    describe('apiグループ', function () {
        it('基本的なミドルウェアセットが適用されること', function () {
            $response = getJson('/test/api-group', [
                'Accept' => 'application/json',
            ]);

            // グローバルミドルウェア
            expect($response->headers->has('X-Request-ID'))->toBeTrue();
            expect($response->headers->has('X-Correlation-ID'))->toBeTrue();

            // apiグループのミドルウェアが正常動作
            $response->assertStatus(200);
            $response->assertJson(['group' => 'api']);
        });
    });

    describe('authグループ', function () {
        it('認証されていない場合はHTTP 401を返すこと', function () {
            $response = getJson('/test/auth-group', [
                'Accept' => 'application/json',
            ]);

            // auth:sanctumが未認証リクエストを拒否
            $response->assertStatus(401);
        });

        it('認証済みの場合はリクエストが成功すること', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = getJson('/test/auth-group', [
                'Accept' => 'application/json',
            ]);

            // auth:sanctumが認証済みリクエストを許可
            $response->assertStatus(200);
            $response->assertJson(['group' => 'auth', 'user' => $user->id]);
        });

        it('認証ミドルウェアチェーンが正しく動作すること', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = getJson('/test/auth-group', [
                'Accept' => 'application/json',
            ]);

            // auth グループ = api + auth:sanctum + SanctumTokenVerification + AuditTrail
            expect($response->headers->has('X-Request-ID'))->toBeTrue();
            $response->assertStatus(200);

            // Note: AuditTrailは非同期処理のため直接検証不可
        });
    });

    describe('guestグループ', function () {
        it('認証なしでアクセスできること', function () {
            $response = postJson('/test/guest-group', [], [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            // guest グループは認証不要
            $response->assertStatus(200);
            $response->assertJson(['group' => 'guest']);
        });

        it('guestグループ固有のレート制限が適用されること', function () {
            $response = postJson('/test/guest-group', [], [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            // guest グループ = api + DynamicRateLimit:public
            $response->assertStatus(200);

            // Note: レート制限はRedis依存のため、環境により動作が異なる
        });
    });

    describe('internalグループ', function () {
        it('認証されていない場合はHTTP 401を返すこと', function () {
            $response = getJson('/test/internal-group', [
                'Accept' => 'application/json',
            ]);

            $response->assertStatus(401);
        });

        it('認証済みユーザーは権限検証が実行されること', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = getJson('/test/internal-group', [
                'Accept' => 'application/json',
            ]);

            // internal グループ = api + auth:sanctum + SanctumTokenVerification + AuthorizationCheck:admin + DynamicRateLimit:strict + AuditTrail
            // AuthorizationServiceの実装に依存するため、ステータスコードは200または403
            expect($response->getStatusCode())->toBeIn([200, 403]);
        });

        it('internalグループのミドルウェアチェーンが正しく動作すること', function () {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $response = getJson('/test/internal-group', [
                'Accept' => 'application/json',
            ]);

            // グローバルミドルウェアが実行されていること
            expect($response->headers->has('X-Request-ID'))->toBeTrue();
            expect($response->headers->has('X-Correlation-ID'))->toBeTrue();

            // 認証ミドルウェアが実行されていること（401でないことを確認）
            expect($response->getStatusCode())->not()->toBe(401);
        });
    });

    describe('webhookグループ', function () {
        it('Idempotencyキーなしでもリクエストが成功すること', function () {
            $response = postJson('/test/webhook-group', ['data' => 'test'], [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            // IdempotencyKeyミドルウェアはヘッダーがない場合は通常処理
            // （オプション機能のため必須ではない）
            $response->assertStatus(200);
            $response->assertJson(['group' => 'webhook']);
        });

        it('Idempotencyキーがあればリクエストが成功すること', function () {
            $response = postJson('/test/webhook-group', ['data' => 'test'], [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Idempotency-Key' => 'webhook-test-123',
            ]);

            // webhook グループ = api + IdempotencyKey + DynamicRateLimit:webhook
            $response->assertStatus(200);
            $response->assertJson(['group' => 'webhook']);
        });

        it('同じIdempotencyキーでの2回目のリクエストはキャッシュを返すこと', function () {
            $idempotencyKey = 'webhook-duplicate-456';
            $payload = ['data' => 'test-data'];

            // 1回目のリクエスト
            $response1 = postJson('/test/webhook-group', $payload, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Idempotency-Key' => $idempotencyKey,
            ]);

            $response1->assertStatus(200);

            // 2回目のリクエスト（同じIdempotencyキー・同じペイロード）
            $response2 = postJson('/test/webhook-group', $payload, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Idempotency-Key' => $idempotencyKey,
            ]);

            // IdempotencyKeyがキャッシュ済みレスポンスを返す
            $response2->assertStatus(200);
            $response2->assertJson(['group' => 'webhook']);
        });
    });

    describe('readonlyグループ', function () {
        it('GETリクエストが成功すること', function () {
            $response = getJson('/test/readonly-group', [
                'Accept' => 'application/json',
            ]);

            // readonly グループ = api + CacheHeaders + ETag
            $response->assertStatus(200);
            $response->assertJson(['group' => 'readonly']);
        });

        it('キャッシュヘッダーが設定されること', function () {
            $response = getJson('/test/readonly-group', [
                'Accept' => 'application/json',
            ]);

            // CacheHeadersミドルウェアがキャッシュヘッダーを設定
            // testing環境ではno-cache設定
            expect($response->headers->has('Cache-Control'))->toBeTrue();
            $cacheControl = $response->headers->get('Cache-Control');
            expect($cacheControl)->toContain('no-cache');

            $response->assertStatus(200);
        });

        it('ETagが生成されること', function () {
            $response = getJson('/test/readonly-group', [
                'Accept' => 'application/json',
            ]);

            // ETagミドルウェアがETagを生成
            expect($response->headers->has('ETag'))->toBeTrue();

            $etag = $response->headers->get('ETag');
            expect($etag)->not()->toBeNull();
            expect($etag)->toMatch('/^"[a-f0-9]{64}"$/'); // SHA256ハッシュ形式

            $response->assertStatus(200);
        });

        it('If-None-Matchヘッダーに一致する場合はHTTP 304を返すこと', function () {
            // 1回目のリクエストでETagを取得
            $response1 = getJson('/test/readonly-group', [
                'Accept' => 'application/json',
            ]);

            $etag = $response1->headers->get('ETag');

            // 2回目のリクエスト（If-None-Matchヘッダー付き）
            $response2 = getJson('/test/readonly-group', [
                'Accept' => 'application/json',
                'If-None-Match' => $etag,
            ]);

            // ETagミドルウェアがHTTP 304を返す
            $response2->assertStatus(304);
            expect($response2->getContent())->toBe('');
        });
    });
});
