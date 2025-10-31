# API V1 Implementation Guide

## 目次

1. [設計思想](#設計思想)
2. [実装パターン](#実装パターン)
3. [テスト戦略](#テスト戦略)
4. [トラブルシューティング](#トラブルシューティング)
5. [パフォーマンス最適化](#パフォーマンス最適化)

## 設計思想

### 1. APIバージョニング戦略

#### URLベースバージョニング

```
/api/v1/health
/api/v1/login
/api/v1/users
```

**採用理由**:
- シンプルで明示的
- キャッシュ・CDN対応が容易
- ドキュメント作成が容易
- ブラウザでの動作確認が簡単

**他の方式との比較**:

| 方式 | メリット | デメリット | 採用可否 |
|------|---------|-----------|---------|
| URLベース | シンプル、明示的 | URLが長くなる | ✅ 採用 |
| ヘッダーベース | RESTful | クライアント実装が複雑 | ❌ 不採用 |
| クエリパラメーター | 後方互換性 | キャッシュ困難 | ❌ 不採用 |

### 2. DDD/クリーンアーキテクチャ準拠

#### 4層構造

```
┌─────────────────────────────────────────┐
│ HTTP Layer (app/Http/Controllers/Api/V1)│ ← V1固有
├─────────────────────────────────────────┤
│ Application Layer (ddd/Application)     │ ← バージョン独立
├─────────────────────────────────────────┤
│ Domain Layer (ddd/Domain)               │ ← バージョン独立
├─────────────────────────────────────────┤
│ Infrastructure Layer (ddd/Infrastructure)│ ← バージョン独立
└─────────────────────────────────────────┘
```

**原則**:
- Domain/Application層はバージョンに依存しない
- バージョン固有のロジックはHTTP層のみに配置
- Presenter/RequestはInfrastructure層に配置

#### 依存関係の制約

```
✅ 許可される依存:
V1 Controller → Application UseCase
V1 Controller → Domain ValueObject
V1 Presenter → Domain Entity (読み取りのみ)

❌ 禁止される依存:
Domain → V1 Controller
Application → V1 Presenter
Domain → V1 Request
```

### 3. API契約の明示化

#### レスポンスヘッダー

```http
HTTP/1.1 200 OK
X-API-Version: v1
X-Request-Id: 550e8400-e29b-41d4-a716-446655440000
Content-Type: application/json
```

**必須ヘッダー**:
- `X-API-Version`: APIバージョン（v1/v2/...）
- `X-Request-Id`: リクエスト追跡ID（UUID v4）
- `Content-Type`: application/json

## 実装パターン

### 1. 新規エンドポイント追加

#### Step 1: ルーティング定義

```php
// routes/api/v1.php
Route::prefix('v1')
    ->name('v1.')
    ->middleware(['api'])
    ->group(function (): void {
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');
    });
```

#### Step 2: コントローラー作成

```php
// app/Http/Controllers/Api/V1/UserController.php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Ddd\Application\User\UseCases\GetUsers\GetUsersUseCase;
use Ddd\Infrastructure\Presentation\Api\V1\User\UserListPresenter;
use Illuminate\Http\JsonResponse;

final class UserController extends Controller
{
    public function __construct(
        private readonly GetUsersUseCase $getUsersUseCase,
        private readonly UserListPresenter $presenter
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->getUsersUseCase->execute();

        return response()->json(
            $this->presenter->present($users),
            200,
            ['X-API-Version' => 'v1']
        );
    }
}
```

#### Step 3: Presenter作成

```php
// ddd/Infrastructure/Presentation/Api/V1/User/UserListPresenter.php
<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Presentation\Api\V1\User;

use Ddd\Domain\User\Entities\UserCollection;

final class UserListPresenter
{
    /**
     * @return array{users: array<int, array{id: int, email: string, name: string}>}
     */
    public function present(UserCollection $users): array
    {
        return [
            'users' => $users->map(fn ($user) => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ])->toArray(),
        ];
    }
}
```

#### Step 4: Request作成（POSTの場合）

```php
// ddd/Infrastructure/Requests/Api/V1/User/CreateUserRequest.php
<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;

final class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
```

#### Step 5: テスト作成

```php
// tests/Feature/Api/V1/UserControllerTest.php
<?php

declare(strict_types=1);

use function Pest\Laravel\{getJson, postJson, assertDatabaseHas};

describe('V1 User API', function () {
    test('GET /api/v1/users should return user list', function (): void {
        $response = getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertHeader('X-API-Version', 'v1')
            ->assertJsonStructure([
                'users' => [
                    '*' => ['id', 'email', 'name'],
                ],
            ]);
    });

    test('POST /api/v1/users should create new user', function (): void {
        $response = postJson('/api/v1/users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertHeader('X-API-Version', 'v1')
            ->assertJsonStructure(['id', 'email', 'name']);

        assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    });
});
```

### 2. エンドポイント修正（V2での変更）

#### V1を維持しながらV2を追加

```php
// routes/api/v2.php
Route::prefix('v2')
    ->name('v2.')
    ->middleware(['api'])
    ->group(function (): void {
        // V2固有の変更
        Route::get('/users', [V2\UserController::class, 'index'])
            ->name('users.index');
    });

// app/Http/Controllers/Api/V2/UserController.php
final class UserController extends Controller
{
    // V2固有の実装
    // - V1と異なるレスポンス構造
    // - 追加フィールド
    // - 改善されたエラーハンドリング
}
```

### 3. 認証エンドポイント

#### Sanctum + APIバージョニング

```php
// routes/api/v1.php
Route::prefix('v1')->name('v1.')->middleware(['api'])->group(function (): void {
    // 未認証エンドポイント
    Route::post('/register', [AuthController::class, 'register'])
        ->name('register')
        ->middleware('throttle:5,1');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login')
        ->middleware('throttle:5,1');

    // 認証済みエンドポイント
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        Route::get('/user', [AuthController::class, 'user'])
            ->name('user');
    });
});
```

## テスト戦略

### 1. テストレベル

#### Unit Tests (単体テスト)
- **対象**: ドメインロジック、ValueObject、Service
- **範囲**: バージョンに依存しない
- **実行頻度**: コミット毎

#### Feature Tests (機能テスト)
- **対象**: V1エンドポイント、Middleware
- **範囲**: HTTPリクエスト → レスポンス
- **実行頻度**: プッシュ毎

#### Architecture Tests (アーキテクチャテスト)
- **対象**: 依存関係制約、命名規則
- **範囲**: V1実装がDDD原則に準拠
- **実行頻度**: CI/CD

#### E2E Tests (エンドツーエンドテスト)
- **対象**: 実際のブラウザでのAPI呼び出し
- **範囲**: フロントエンド → V1 API
- **実行頻度**: リリース前

### 2. テストカバレッジ目標

```
Minimum Coverage: 85%
Target Coverage:  90%
Critical Paths:   100%
```

**Critical Paths**:
- 認証フロー (register, login, logout)
- トークン管理
- エラーハンドリング

### 3. モックとスタブ

#### 外部依存のモック

```php
use Illuminate\Support\Facades\Http;

test('external API call should be mocked', function (): void {
    Http::fake([
        'external-api.com/*' => Http::response(['status' => 'success'], 200),
    ]);

    $response = getJson('/api/v1/external-data');

    $response->assertStatus(200);
    Http::assertSent(fn ($request) =>
        $request->url() === 'https://external-api.com/data'
    );
});
```

#### データベースのテストデータ

```php
use function Pest\Laravel\{actingAs};
use Ddd\Domain\User\Entities\User;

test('authenticated endpoint should use test data', function (): void {
    $user = User::factory()->create();

    $response = actingAs($user)->getJson('/api/v1/user');

    $response->assertStatus(200)
        ->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
});
```

## トラブルシューティング

### 1. ルーティングエラー

#### 問題: 404 Not Found

```http
GET /api/v1/users HTTP/1.1
Response: 404 Not Found
```

**原因**:
- ルートが登録されていない
- ミドルウェアグループが正しくない
- プレフィックスの設定ミス

**解決策**:

```bash
# ルート一覧確認
php artisan route:list --path=api/v1

# キャッシュクリア
php artisan route:clear
php artisan config:clear
```

### 2. X-API-Versionヘッダーが返されない

#### 問題: レスポンスヘッダーにX-API-Versionがない

**原因**:
- ApiVersionミドルウェアが適用されていない
- ミドルウェアグループの順序が間違っている

**解決策**:

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        // ApiVersionは最優先
        \App\Http\Middleware\ApiVersion::class,
        \App\Http\Middleware\ForceJsonResponse::class,
        // ...
    ],
];
```

### 3. テストでAuthエラー

#### 問題: auth:sanctumテストで401 Unauthorized

```php
test('authenticated endpoint test fails', function (): void {
    $user = User::factory()->create();

    $response = getJson('/api/v1/user'); // 401エラー
});
```

**解決策**:

```php
use function Pest\Laravel\actingAs;

test('authenticated endpoint test passes', function (): void {
    $user = User::factory()->create();

    $response = actingAs($user, 'sanctum')
        ->getJson('/api/v1/user');

    $response->assertStatus(200);
});
```

### 4. レート制限エラー

#### 問題: テストで429 Too Many Requests

```php
test('login endpoint test fails with 429', function (): void {
    for ($i = 0; $i < 6; $i++) {
        $response = postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
    }
    // 6回目で429エラー
});
```

**解決策**:

```php
// tests/Pest.php
uses(RefreshDatabase::class)->in('Feature');

// .env.ci
RATELIMIT_CACHE_STORE=array
```

### 5. Architecture Test失敗

#### 問題: V1コントローラーがDomain層に依存している

```bash
FAILED  V1 controllers must not depend on Domain layer directly
```

**原因**:
```php
// ❌ 悪い例
final class UserController extends Controller
{
    public function index()
    {
        $users = User::all(); // Domain層に直接依存
    }
}
```

**解決策**:
```php
// ✅ 良い例
final class UserController extends Controller
{
    public function __construct(
        private readonly GetUsersUseCase $useCase
    ) {}

    public function index()
    {
        $users = $this->useCase->execute(); // Application層を経由
    }
}
```

## パフォーマンス最適化

### 1. N+1問題の回避

```php
// ❌ 悪い例 (N+1発生)
$users = User::all();
foreach ($users as $user) {
    $user->posts; // N回クエリ発行
}

// ✅ 良い例 (Eager Loading)
$users = User::with('posts')->get(); // 2回のクエリのみ
```

### 2. キャッシュ戦略

```php
use Illuminate\Support\Facades\Cache;

final class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = Cache::remember('v1_users_list', 3600, function () {
            return $this->getUsersUseCase->execute();
        });

        return response()->json(
            $this->presenter->present($users)
        );
    }
}
```

### 3. ペジネーション

```php
final class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int)$request->query('per_page', 20), 100);

        $users = $this->getUsersUseCase->execute(
            page: (int)$request->query('page', 1),
            perPage: $perPage
        );

        return response()->json(
            $this->presenter->present($users)
        );
    }
}
```

### 4. レート制限の設定

```php
// routes/api/v1.php
Route::middleware('throttle:60,1')->group(function (): void {
    // 1分間に60リクエストまで
    Route::get('/users', [UserController::class, 'index']);
});

Route::middleware('throttle:5,1')->group(function (): void {
    // 1分間に5リクエストまで（厳格）
    Route::post('/login', [AuthController::class, 'login']);
});
```

## まとめ

V1 APIの実装は以下の原則に従います：

1. **URLベースバージョニング** - シンプルで明示的
2. **DDD/クリーンアーキテクチャ準拠** - バージョン独立なドメイン層
3. **API契約の明示化** - X-API-Versionヘッダー
4. **完全なテストカバレッジ** - 85%以上
5. **パフォーマンス最適化** - キャッシュ、ペジネーション、レート制限

次のステップ: [V2 Roadmap](./v2-roadmap.md)
