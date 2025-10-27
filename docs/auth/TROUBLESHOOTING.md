# トラブルシューティングガイド - 認証機能

Laravel + Next.js B2C アプリケーションの認証機能に関するよくある問題と解決策

## 目次

- [トークン認証失敗](#トークン認証失敗)
- [CORSエラー](#corsエラー)
- [Admin無効化エラー](#admin無効化エラー)
- [APIバージョンエラー](#apiバージョンエラー)
- [ログインできない](#ログインできない)
- [セッション・Cookie問題](#セッションcookie問題)
- [データベース接続エラー](#データベース接続エラー)
- [環境変数エラー](#環境変数エラー)

---

## トークン認証失敗

### 症状1: `401 Unauthorized - AUTH.UNAUTHORIZED`

**エラーメッセージ**:
```json
{
  "code": "AUTH.UNAUTHORIZED",
  "message": "認証が必要です",
  "details": {}
}
```

#### 原因

1. トークンが`Authorization`ヘッダーに含まれていない
2. トークン形式が間違っている（Bearer プレフィックスがない）
3. トークンが無効または期限切れ

#### 解決策

**1. トークンの存在確認**

```bash
# curl でテスト
curl -X GET http://localhost:13000/api/v1/user/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -v
```

**2. トークン形式確認**

```typescript
// ❌ 間違い
headers: {
  'Authorization': 'YOUR_TOKEN_HERE'
}

// ✅ 正しい
headers: {
  'Authorization': 'Bearer YOUR_TOKEN_HERE'
}
```

**3. トークンの有効期限確認**

```sql
-- トークンの有効期限を確認
SELECT id, tokenable_id, tokenable_type, expires_at, created_at
FROM personal_access_tokens
WHERE token = 'YOUR_HASHED_TOKEN'
LIMIT 1;
```

トークンが期限切れの場合、再ログインが必要です。

---

### 症状2: `401 Unauthorized - AUTH.TOKEN_EXPIRED`

**エラーメッセージ**:
```json
{
  "code": "AUTH.TOKEN_EXPIRED",
  "message": "トークンの有効期限が切れています",
  "details": {}
}
```

#### 原因

トークンの有効期限（デフォルト24時間）が切れています。

#### 解決策

**1. 再ログイン**

フロントエンドで再ログイン処理を実装:

```typescript
// User App - AuthContext.tsx
useEffect(() => {
  const checkAuth = async () => {
    try {
      await fetchWithAuth('/api/v1/user/profile');
    } catch (error) {
      if (error.code === 'AUTH.TOKEN_EXPIRED') {
        // トークン期限切れの場合、ログアウト処理
        logout();
        router.push('/login');
      }
    }
  };

  checkAuth();
}, []);
```

**2. トークン有効期限の延長**

```php
// config/sanctum.php
'expiration' => 24 * 7, // 7日間に延長
```

---

### 症状3: User トークンで Admin エンドポイントにアクセス

**エラーメッセージ**:
```json
{
  "code": "AUTH.UNAUTHORIZED",
  "message": "認証が必要です",
  "details": {}
}
```

#### 原因

`UserGuard` または `AdminGuard` ミドルウェアが `tokenable_type` を検証し、不正なトークンタイプを拒否しています。

#### 解決策

**1. エンドポイントとトークンタイプの一致確認**

| トークンタイプ | 使用可能なエンドポイント |
|--------------|----------------------|
| User | `/api/v1/user/*` のみ |
| Admin | `/api/v1/admin/*` のみ |

**2. トークンタイプの確認**

```sql
-- トークンタイプを確認
SELECT tokenable_type, tokenable_id
FROM personal_access_tokens
WHERE token = 'YOUR_HASHED_TOKEN'
LIMIT 1;
```

**3. 正しいログインエンドポイント使用**

```bash
# User ログイン
curl -X POST http://localhost:13000/api/v1/user/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Admin ログイン
curl -X POST http://localhost:13000/api/v1/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

---

## CORSエラー

### 症状: ブラウザコンソールに CORS エラー

**エラーメッセージ**:
```
Access to fetch at 'http://localhost:13000/api/v1/user/login' from origin
'http://localhost:13001' has been blocked by CORS policy:
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

#### 原因

1. Laravel API の CORS 設定が不足
2. `config/cors.php` の `allowed_origins` にフロントエンドURLが含まれていない
3. `CORS_SUPPORTS_CREDENTIALS` が `false` になっている

#### 解決策

**1. CORS 設定確認**

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:13001,http://localhost:13002')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
```

**2. 環境変数設定**

```.env
# backend/laravel-api/.env
CORS_ALLOWED_ORIGINS="http://localhost:13001,http://localhost:13002"
CORS_SUPPORTS_CREDENTIALS=true
```

**3. Laravel 再起動**

```bash
# Docker環境
docker-compose restart laravel.test

# Laravel Sail
./vendor/bin/sail artisan config:clear
./vendor/bin/sail restart
```

**4. フロントエンド fetch 設定確認**

```typescript
// credentials: 'include' が必要
fetch('http://localhost:13000/api/v1/user/profile', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  credentials: 'include', // ✅ 必須
});
```

---

### 症状: Preflight リクエスト失敗

**エラーメッセージ**:
```
Response to preflight request doesn't pass access control check:
It does not have HTTP ok status.
```

#### 原因

OPTIONS リクエスト（Preflight）が失敗しています。

#### 解決策

**1. CORS ミドルウェア確認**

```php
// app/Http/Kernel.php
protected $middleware = [
    \App\Http\Middleware\TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class, // ✅ 必須
    // ...
];
```

**2. Sanctum ミドルウェア確認**

```php
// app/Http/Kernel.php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // ✅ 必須
    // ...
],
```

---

## Admin無効化エラー

### 症状: `403 Forbidden - AUTH.ADMIN_DISABLED`

**エラーメッセージ**:
```json
{
  "code": "AUTH.ADMIN_DISABLED",
  "message": "管理者アカウントが無効化されています",
  "details": {}
}
```

#### 原因

管理者アカウントの `is_active` が `false` になっています。

#### 解決策

**1. 管理者アカウントの有効化**

```sql
-- SQL で直接有効化
UPDATE admins
SET is_active = true
WHERE email = 'admin@example.com';
```

**2. Tinker で有効化**

```bash
php artisan tinker
```

```php
$admin = App\Models\Admin::where('email', 'admin@example.com')->first();
$admin->is_active = true;
$admin->save();
```

**3. 管理者アカウント状態確認**

```sql
SELECT id, name, email, role, is_active
FROM admins
WHERE email = 'admin@example.com';
```

---

## APIバージョンエラー

### 症状: `404 Not Found` (エンドポイントが見つからない)

**エラーメッセージ**:
```json
{
  "message": "The route api/user/login could not be found."
}
```

#### 原因

APIバージョン（`/api/v1/`）が URL に含まれていません。

#### 解決策

**1. URL にバージョンプレフィックスを追加**

```typescript
// ❌ 間違い
const url = 'http://localhost:13000/api/user/login';

// ✅ 正しい
const url = 'http://localhost:13000/api/v1/user/login';
```

**2. 環境変数で管理**

```typescript
// frontend/.env.local
NEXT_PUBLIC_API_VERSION=v1

// api-client.ts
const API_VERSION = process.env.NEXT_PUBLIC_API_VERSION || 'v1';
const url = `${BASE_URL}/api/${API_VERSION}/user/login`;
```

**3. API ルート確認**

```bash
php artisan route:list --path=api/v1
```

---

## ログインできない

### 症状1: `401 Unauthorized - AUTH.INVALID_CREDENTIALS`

**エラーメッセージ**:
```json
{
  "code": "AUTH.INVALID_CREDENTIALS",
  "message": "メールアドレスまたはパスワードが正しくありません",
  "details": {}
}
```

#### 原因

1. メールアドレスまたはパスワードが間違っている
2. ユーザー/管理者アカウントが存在しない

#### 解決策

**1. アカウント存在確認**

```sql
-- User アカウント確認
SELECT id, name, email, created_at
FROM users
WHERE email = 'user@example.com';

-- Admin アカウント確認
SELECT id, name, email, role, is_active, created_at
FROM admins
WHERE email = 'admin@example.com';
```

**2. Seeder 実行**

```bash
# AdminSeeder を実行
php artisan db:seed --class=AdminSeeder
```

**3. 新しいアカウント作成**

```bash
php artisan tinker
```

```php
// User 作成
$user = new App\Models\User();
$user->name = 'Test User';
$user->email = 'user@example.com';
$user->password = bcrypt('password');
$user->save();

// Admin 作成
$admin = new App\Models\Admin();
$admin->name = 'Admin User';
$admin->email = 'admin@example.com';
$admin->password = bcrypt('password');
$admin->role = App\DDD\Domain\Admin\ValueObjects\AdminRole::SUPER_ADMIN;
$admin->is_active = true;
$admin->save();
```

---

### 症状2: `422 Unprocessable Entity - VALIDATION.FAILED`

**エラーメッセージ**:
```json
{
  "code": "VALIDATION.FAILED",
  "message": "入力内容に誤りがあります",
  "details": {
    "email": ["メールアドレスの形式が正しくありません"],
    "password": ["パスワードは8文字以上である必要があります"]
  }
}
```

#### 原因

リクエストデータがバリデーションに失敗しています。

#### 解決策

**1. バリデーションルール確認**

| フィールド | ルール |
|-----------|--------|
| `email` | 必須、メールアドレス形式 |
| `password` | 必須、文字列、8文字以上 |

**2. リクエストデータ修正**

```typescript
// ✅ 正しいリクエスト
{
  "email": "user@example.com",  // メールアドレス形式
  "password": "password123"      // 8文字以上
}
```

---

## セッション・Cookie問題

### 症状: ログイン後すぐにログアウトされる

#### 原因

1. `SESSION_DOMAIN` 設定が間違っている
2. Cookie の `SameSite` 属性が不適切
3. HTTPS/HTTP の不一致

#### 解決策

**1. セッション設定確認**

```.env
# backend/laravel-api/.env
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false  # 開発環境では false
SESSION_SAME_SITE=lax
```

**2. Sanctum 設定確認**

```.env
SANCTUM_STATEFUL_DOMAINS=localhost:13001,localhost:13002
```

**3. Cookie 確認**

ブラウザの開発者ツールで Cookie を確認:
- `laravel_session` Cookie が設定されていること
- `Domain` が `localhost` であること
- `SameSite` が `Lax` であること

---

## データベース接続エラー

### 症状: `SQLSTATE[08006] Connection refused`

**エラーメッセージ**:
```
SQLSTATE[08006] [7] could not translate host name "postgres" to address:
nodename nor servname provided, or not known
```

#### 原因

1. PostgreSQL サービスが起動していない
2. データベース接続設定が間違っている
3. Docker ネットワークの問題

#### 解決策

**1. PostgreSQL サービス起動確認**

```bash
# Docker Compose でサービス確認
docker-compose ps

# PostgreSQL コンテナ起動
docker-compose up -d postgres
```

**2. データベース接続設定確認**

```.env
# backend/laravel-api/.env
DB_CONNECTION=pgsql
DB_HOST=postgres  # Docker Compose サービス名
DB_PORT=5432
DB_DATABASE=laravel_b2c
DB_USERNAME=sail
DB_PASSWORD=password
```

**3. データベース接続テスト**

```bash
# Docker環境で psql 接続テスト
docker-compose exec postgres psql -U sail -d laravel_b2c

# 接続成功すれば PostgreSQL プロンプトが表示される
# laravel_b2c=#
```

**4. Laravel 設定キャッシュクリア**

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 環境変数エラー

### 症状: `ENV_VALIDATION_FAILED`

**エラーメッセージ**:
```
Environment validation failed: CORS_ALLOWED_ORIGINS is required
```

#### 原因

必須環境変数が設定されていません。

#### 解決策

**1. 環境変数バリデーションをスキップ（開発環境のみ）**

```.env
ENV_VALIDATION_SKIP=true
```

**2. 必須環境変数を設定**

```.env
CORS_ALLOWED_ORIGINS="http://localhost:13001,http://localhost:13002"
CORS_SUPPORTS_CREDENTIALS=true
SECURITY_ENABLE_CSP=true
```

**3. `.env.example` を参照**

```bash
# .env.example をコピー
cp .env.example .env

# 必要な環境変数を設定
vi .env
```

---

## レート制限エラー

### 症状: `429 Too Many Requests - RATE_LIMIT.EXCEEDED`

**エラーメッセージ**:
```json
{
  "code": "RATE_LIMIT.EXCEEDED",
  "message": "リクエスト回数が上限を超えました。しばらくしてから再度お試しください",
  "details": {
    "retry_after": 60
  }
}
```

#### 原因

レート制限（1分間に5回 for ログインエンドポイント）を超過しています。

#### 解決策

**1. 待機**

`retry_after` 秒待ってから再試行します。

**2. レート制限の一時的な無効化（開発環境のみ）**

```.env
# backend/laravel-api/.env
RATELIMIT_CACHE_STORE=array  # メモリキャッシュ（再起動でリセット）
```

**3. Redis キャッシュクリア**

```bash
# Redis CLI で全キャッシュクリア
docker-compose exec redis redis-cli FLUSHALL
```

---

## パフォーマンス問題

### 症状: APIレスポンスが遅い (>1秒)

#### 原因

1. データベースクエリが最適化されていない
2. N+1 クエリ問題
3. キャッシュが機能していない

#### 解決策

**1. Laravel Debugbar でクエリ分析**

```bash
composer require barryvdh/laravel-debugbar --dev
```

**2. クエリログ確認**

```.env
LOG_LEVEL=debug
DB_LOG_QUERIES=true
```

```bash
# ログファイル確認
tail -f storage/logs/laravel.log
```

**3. Eager Loading 使用**

```php
// ❌ N+1 クエリ
$users = User::all();
foreach ($users as $user) {
    $user->profile;  // 各ユーザーごとにクエリ実行
}

// ✅ Eager Loading
$users = User::with('profile')->get();
```

---

## サポート

上記の解決策で問題が解決しない場合:

1. GitHub Issues で報告: https://github.com/your-org/laravel-next-b2c/issues
2. ログファイルを確認: `storage/logs/laravel.log`
3. 詳細なエラー情報を収集:
   ```bash
   # Laravel ログレベルを debug に変更
   LOG_LEVEL=debug

   # エラー詳細を表示
   APP_DEBUG=true
   ```

---

## 関連ドキュメント

- [セットアップガイド](./SETUP_GUIDE.md)
- [API仕様書](./API_SPECIFICATION.md)
- [認証フロー図](./AUTHENTICATION_FLOW.md)
- [セキュリティベストプラクティス](./SECURITY_BEST_PRACTICES.md)
