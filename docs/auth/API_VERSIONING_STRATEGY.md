# APIバージョニング戦略

Laravel + Next.js B2C アプリケーションにおけるAPIバージョニング戦略とベストプラクティス

## 目次

- [概要](#概要)
- [バージョニングポリシー](#バージョニングポリシー)
- [破壊的変更の定義](#破壊的変更の定義)
- [バージョンサポート期間](#バージョンサポート期間)
- [バージョン移行手順](#バージョン移行手順)
- [後方互換性ルール](#後方互換性ルール)
- [実装ガイド](#実装ガイド)

---

## 概要

### バージョニング方式

このプロジェクトでは **URL Pathバージョニング** を採用しています。

```
/api/{version}/{resource}
```

**例**:
- v1: `/api/v1/user/login`
- v2: `/api/v2/user/login`

### 採用理由

| メリット | 説明 |
|---------|------|
| **明確性** | URLを見ればAPIバージョンが一目瞭然 |
| **キャッシュフレンドリー** | バージョンごとに異なるURLでキャッシュ可能 |
| **ドキュメント化しやすい** | バージョンごとにドキュメントを分離可能 |
| **テストしやすい** | E2Eテストでバージョンを明示的に指定可能 |

### 他の方式との比較

| 方式 | メリット | デメリット | 採用 |
|-----|---------|----------|------|
| **URLパス** | 明確、キャッシュ対応 | URLが長くなる | ✅ 採用 |
| **クエリパラメータ** | URLが短い | キャッシュに不向き | ❌ |
| **ヘッダー** | URLを変更不要 | 不透明、デバッグ困難 | ❌ |
| **コンテンツネゴシエーション** | RESTful | 複雑、実装コスト高 | ❌ |

---

## バージョニングポリシー

### セマンティックバージョニング（簡易版）

APIバージョンは **メジャーバージョン** のみを使用します。

```
v{MAJOR}
```

- **v1**: 初期バージョン
- **v2**: 破壊的変更を含む次バージョン
- **v3**: さらなる破壊的変更を含むバージョン

### バージョンアップのタイミング

| 変更内容 | バージョンアップ | 例 |
|---------|----------------|-----|
| 破壊的変更 | 必須 | レスポンス形式変更、エンドポイント削除 |
| 非破壊的追加 | 不要 | 新しいフィールド追加、新しいエンドポイント追加 |
| バグ修正 | 不要 | エラーハンドリング修正、パフォーマンス改善 |

---

## 破壊的変更の定義

以下の変更は **破壊的変更** として扱い、新しいメジャーバージョンを作成する必要があります。

### 1. レスポンス形式の変更

#### ❌ 破壊的変更

```json
// v1 (旧)
{
  "token": "abc123",
  "user": {
    "id": 1,
    "name": "John Doe"
  }
}

// v2 (新) - フィールド名変更
{
  "access_token": "abc123",  // ❌ フィールド名変更
  "user_data": {             // ❌ フィールド名変更
    "id": 1,
    "name": "John Doe"
  }
}
```

#### ✅ 非破壊的変更

```json
// v1 (旧)
{
  "token": "abc123",
  "user": {
    "id": 1,
    "name": "John Doe"
  }
}

// v1 (新) - フィールド追加
{
  "token": "abc123",
  "user": {
    "id": 1,
    "name": "John Doe",
    "avatar_url": "https://example.com/avatar.jpg"  // ✅ 新しいフィールド追加
  }
}
```

### 2. リクエストパラメータの変更

#### ❌ 破壊的変更

```typescript
// v1 (旧)
POST /api/v1/user/login
{
  "email": "user@example.com",
  "password": "password123"
}

// v2 (新) - 必須パラメータ追加
POST /api/v2/user/login
{
  "email": "user@example.com",
  "password": "password123",
  "device_id": "abc123"  // ❌ 新しい必須パラメータ
}
```

#### ✅ 非破壊的変更

```typescript
// v1 (旧)
POST /api/v1/user/login
{
  "email": "user@example.com",
  "password": "password123"
}

// v1 (新) - オプションパラメータ追加
POST /api/v1/user/login
{
  "email": "user@example.com",
  "password": "password123",
  "device_id": "abc123"  // ✅ オプションパラメータ（デフォルト値あり）
}
```

### 3. エンドポイントの削除・変更

#### ❌ 破壊的変更

```
v1: POST /api/v1/user/login   // 削除
v2: POST /api/v2/auth/login   // ❌ URLパス変更
```

#### ✅ 非破壊的変更

```
v1: POST /api/v1/user/login        // 既存エンドポイント維持
v1: POST /api/v1/user/login/email  // ✅ 新しいエンドポイント追加
```

### 4. ステータスコードの変更

#### ❌ 破壊的変更

```
// v1 (旧)
POST /api/v1/user/login
Response: 200 OK

// v2 (新)
POST /api/v2/user/login
Response: 201 Created  // ❌ ステータスコード変更
```

### 5. エラーレスポンス形式の変更

#### ❌ 破壊的変更

```json
// v1 (旧)
{
  "code": "AUTH.INVALID_CREDENTIALS",
  "message": "Invalid credentials"
}

// v2 (新)
{
  "error": {  // ❌ 構造変更
    "code": "AUTH.INVALID_CREDENTIALS",
    "message": "Invalid credentials"
  }
}
```

---

## バージョンサポート期間

### サポートポリシー

| バージョン状態 | 期間 | 対応内容 |
|--------------|------|---------|
| **Current** | 無期限 | 新機能追加、バグ修正、セキュリティ修正 |
| **Maintenance** | 6ヶ月 | バグ修正、セキュリティ修正のみ |
| **Deprecated** | 3ヶ月 | セキュリティ修正のみ（緊急時） |
| **End of Life** | - | サポート終了 |

### サポートタイムライン例

```
v1リリース (2025-01-01)
├─ v1 Current: 2025-01-01 ~ 2025-06-30 (6ヶ月)
│
v2リリース (2025-07-01)
├─ v2 Current: 2025-07-01 ~ (継続中)
├─ v1 Maintenance: 2025-07-01 ~ 2025-12-31 (6ヶ月)
│
v1非推奨アナウンス (2025-10-01)
├─ v1 Deprecated: 2026-01-01 ~ 2026-03-31 (3ヶ月)
│
v1サポート終了 (2026-04-01)
└─ v1 End of Life: 2026-04-01 ~
```

### 非推奨通知方法

1. **レスポンスヘッダー**:
   ```http
   Deprecation: true
   Sunset: Sat, 31 Mar 2026 23:59:59 GMT
   Link: </api/v2/docs>; rel="successor-version"
   ```

2. **ドキュメント**: 公式ドキュメントに非推奨バッジを表示

3. **メール通知**: API利用者にメール通知

---

## バージョン移行手順

### v1 → v2 移行のステップ

#### フェーズ1: 準備期間（1-2ヶ月前）

1. **v2の設計と実装**
   ```php
   // routes/api.php
   Route::prefix('v2')->group(function () {
       Route::prefix('user')->group(function () {
           Route::post('/login', [V2\User\LoginController::class, 'login']);
       });
   });
   ```

2. **v2ドキュメント作成**
   - OpenAPI 3.0.0仕様書
   - 移行ガイド（v1との差分）
   - コードサンプル

3. **v2テスト環境公開**
   - ステージング環境で v2 API を公開
   - クライアント開発者向けに早期アクセス提供

#### フェーズ2: v2リリース（当日）

1. **v2の本番リリース**
   ```bash
   # デプロイ
   git tag v2.0.0
   php artisan migrate
   ```

2. **v1の非推奨マーク**
   ```php
   // app/Http/Middleware/DeprecationWarning.php
   public function handle($request, Closure $next)
   {
       $response = $next($request);
       $response->headers->set('Deprecation', 'true');
       $response->headers->set('Sunset', 'Sat, 31 Mar 2026 23:59:59 GMT');
       return $response;
   }
   ```

3. **告知**
   - 公式ブログで v2 リリース告知
   - v1 非推奨アナウンス
   - メール通知

#### フェーズ3: 移行期間（6ヶ月）

1. **クライアント移行サポート**
   - 移行に関する質問対応
   - バグレポート受付

2. **v1メンテナンス**
   - 重大なバグ修正
   - セキュリティ修正

3. **v2改善**
   - フィードバック反映
   - パフォーマンス改善

#### フェーズ4: v1非推奨期間（3ヶ月）

1. **v1使用状況モニタリング**
   ```php
   // ログ記録
   Log::warning('API v1 used', [
       'endpoint' => $request->path(),
       'client_id' => $request->user()->id,
   ]);
   ```

2. **最終移行リマインド**
   - 残っているクライアントに個別連絡

#### フェーズ5: v1サポート終了

1. **v1エンドポイント削除**
   ```php
   // routes/api.php
   // v1ルートをすべて削除またはコメントアウト
   ```

2. **v1ドキュメントアーカイブ**
   - ドキュメントサイトから削除
   - アーカイブページに移動

---

## 後方互換性ルール

### 許可される変更（非破壊的）

#### 1. 新しいエンドポイント追加

```php
// v1に新しいエンドポイントを追加
Route::prefix('v1')->group(function () {
    Route::post('/user/login', [UserLoginController::class, 'login']);
    Route::post('/user/refresh-token', [UserTokenController::class, 'refresh']); // ✅ 追加
});
```

#### 2. 新しいレスポンスフィールド追加

```php
// v1 UserResource.php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'avatar_url' => $this->avatar_url, // ✅ 新しいフィールド追加
    ];
}
```

#### 3. 新しいオプションリクエストパラメータ追加

```php
// v1 LoginRequest.php
public function rules()
{
    return [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
        'remember_me' => 'sometimes|boolean', // ✅ オプションパラメータ追加
    ];
}
```

#### 4. バリデーションルールの緩和

```php
// v1 旧ルール
'password' => 'required|string|min:12'

// v1 新ルール（緩和）
'password' => 'required|string|min:8' // ✅ より緩いルール
```

### 禁止される変更（破壊的）

#### 1. レスポンスフィールドの削除・リネーム

```php
// ❌ 破壊的変更
public function toArray($request)
{
    return [
        'id' => $this->id,
        'full_name' => $this->name, // ❌ フィールド名変更
        // 'email' => $this->email, // ❌ フィールド削除
    ];
}
```

#### 2. 必須リクエストパラメータの追加

```php
// ❌ 破壊的変更
public function rules()
{
    return [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
        'device_id' => 'required|string', // ❌ 新しい必須パラメータ
    ];
}
```

#### 3. バリデーションルールの厳格化

```php
// ❌ 破壊的変更
'password' => 'required|string|min:16' // ❌ より厳しいルール（12→16）
```

#### 4. ステータスコードの変更

```php
// ❌ 破壊的変更
return response()->json($data, 201); // 200 → 201 変更
```

---

## 実装ガイド

### ディレクトリ構造

```
app/Http/Controllers/Api/
├── V1/
│   ├── User/
│   │   ├── LoginController.php
│   │   ├── LogoutController.php
│   │   └── ProfileController.php
│   └── Admin/
│       ├── LoginController.php
│       └── DashboardController.php
└── V2/
    ├── User/
    │   ├── LoginController.php
    │   └── ProfileController.php
    └── Admin/
        └── LoginController.php
```

### ルート定義

```php
// routes/api.php

// Version 1
Route::prefix('v1')->group(function () {
    Route::prefix('user')->group(function () {
        Route::post('/login', [V1\User\LoginController::class, 'login']);
        Route::post('/logout', [V1\User\LogoutController::class, 'logout'])
            ->middleware(['auth:sanctum', \App\Http\Middleware\UserGuard::class]);
        Route::get('/profile', [V1\User\ProfileController::class, 'show'])
            ->middleware(['auth:sanctum', \App\Http\Middleware\UserGuard::class]);
    });

    Route::prefix('admin')->group(function () {
        Route::post('/login', [V1\Admin\LoginController::class, 'login']);
        Route::post('/logout', [V1\Admin\LogoutController::class, 'logout'])
            ->middleware(['auth:sanctum', \App\Http\Middleware\AdminGuard::class]);
        Route::get('/dashboard', [V1\Admin\DashboardController::class, 'show'])
            ->middleware(['auth:sanctum', \App\Http\Middleware\AdminGuard::class]);
    });
});

// Version 2（将来実装）
// Route::prefix('v2')->group(function () {
//     // v2 routes here
// });
```

### フロントエンドでのバージョン指定

```typescript
// frontend/user-app/src/lib/api-client.ts

const API_VERSION = process.env.NEXT_PUBLIC_API_VERSION || 'v1';
const BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:13000';

export const API_ENDPOINTS = {
  user: {
    login: `${BASE_URL}/api/${API_VERSION}/user/login`,
    logout: `${BASE_URL}/api/${API_VERSION}/user/logout`,
    profile: `${BASE_URL}/api/${API_VERSION}/user/profile`,
  },
};
```

### 環境変数設定

```.env
# .env.local (User App)
NEXT_PUBLIC_API_VERSION=v1
NEXT_PUBLIC_API_URL=http://localhost:13000

# .env.local (Admin App)
NEXT_PUBLIC_API_VERSION=v1
NEXT_PUBLIC_API_URL=http://localhost:13000
```

---

## バージョニングのベストプラクティス

### 1. バージョン間でコードを共有する

```php
// 共通ロジックを abstract クラスに抽出
abstract class BaseLoginController
{
    protected function generateToken(Authenticatable $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }
}

// V1実装
class V1\User\LoginController extends BaseLoginController
{
    // v1固有のロジック
}

// V2実装
class V2\User\LoginController extends BaseLoginController
{
    // v2固有のロジック
}
```

### 2. バージョンごとに異なるレスポンス形式を使用する

```php
// app/Http/Resources/V1/UserResource.php
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

// app/Http/Resources/V2/UserResource.php
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user_id' => $this->id, // フィールド名変更
            'full_name' => $this->name,
            'email_address' => $this->email,
            'avatar_url' => $this->avatar_url, // 新しいフィールド
        ];
    }
}
```

### 3. E2Eテストでバージョンを検証する

```typescript
// e2e/projects/user/tests/api-versioning.spec.ts
test('user login endpoint uses /api/v1/user/login', async ({ page }) => {
  const apiCalls: string[] = [];
  page.on('request', (req) => {
    if (req.url().includes('/api/')) {
      apiCalls.push(req.url());
    }
  });

  await loginPage.login(testEmail, testPassword);

  const loginCall = apiCalls.find((url) => url.includes('/login'));
  expect(loginCall).toContain('/api/v1/user/login');
});
```

---

## まとめ

### 重要ポイント

1. **破壊的変更は新しいメジャーバージョンで対応**
2. **v1は最低6ヶ月間サポート**（Maintenance期間含む）
3. **非破壊的変更は既存バージョンで対応可能**
4. **非推奨期間は3ヶ月**
5. **クライアントに十分な移行期間を提供**

### チェックリスト

#### 新バージョンリリース前

- [ ] 破壊的変更の文書化
- [ ] 移行ガイド作成
- [ ] OpenAPI仕様書更新
- [ ] E2Eテスト追加
- [ ] ステージング環境でテスト
- [ ] クライアント開発者への事前通知

#### リリース後

- [ ] 旧バージョンに非推奨ヘッダー追加
- [ ] 公式ブログでアナウンス
- [ ] メール通知送信
- [ ] 使用状況モニタリング開始

---

## 関連ドキュメント

- [API仕様書](./API_SPECIFICATION.md)
- [認証フロー図](./AUTHENTICATION_FLOW.md)
- [セキュリティベストプラクティス](./SECURITY_BEST_PRACTICES.md)
- [トラブルシューティングガイド](./TROUBLESHOOTING.md)
