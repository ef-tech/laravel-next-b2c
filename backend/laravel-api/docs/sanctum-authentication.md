# Laravel Sanctum API認証ドキュメント

## 概要

本ドキュメントは、Laravel Sanctum 4.0を用いたトークンベースAPI認証の実装ガイドです。

### 主な機能

- **トークンベース認証**: Personal Access Tokensによるステートレス認証
- **認証エンドポイント**: ログイン、ログアウト、ユーザー情報取得
- **トークン管理**: トークンの発行、一覧取得、削除
- **セキュリティ**: SHA-256ハッシュ化、レート制限、CORS対応

### アーキテクチャ

- **ステートレス設計**: `SESSION_DRIVER=array`、セッション不使用
- **水平スケーリング対応**: トークンはPostgreSQLに保存
- **API専用最適化**: Web機能削除、最小依存関係（4コアパッケージ）

## インストール手順

### 1. 既存環境での確認

Laravel Sanctum 4.0は既にインストール済みです：

```bash
# composer.jsonで確認
cat composer.json | grep sanctum
# "laravel/sanctum": "^4.0"
```

### 2. 設定ファイルの確認

**config/sanctum.php**:
```php
return [
    'stateful' => [],  // ステートレスAPI専用
    'guard' => ['web'],
    'expiration' => null,  // トークン有効期限（nullは無期限）
];
```

**config/auth.php**:
```php
'defaults' => [
    'guard' => 'api',
],

'guards' => [
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

### 3. マイグレーション実行

```bash
php artisan migrate
```

`personal_access_tokens`テーブルが作成されます。

### 4. Userモデル設定

**app/Models/User.php**:
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

## 認証エンドポイント

### POST /api/login

ユーザー認証とトークン発行

**リクエスト**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**レスポンス（200 OK）**:
```json
{
  "token": "1|abc123...",
  "user": {
    "id": "uuid-string",
    "name": "John Doe",
    "email": "user@example.com"
  },
  "token_type": "Bearer"
}
```

**エラーレスポンス（401 Unauthorized）**:
```json
{
  "message": "Invalid credentials"
}
```

**バリデーションエラー（422 Unprocessable Entity）**:
```json
{
  "message": "The email field must be a valid email address.",
  "errors": {
    "email": ["The email field must be a valid email address."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

### POST /api/logout

認証済みユーザーのログアウト（現在のトークン失効）

**ヘッダー**:
```
Authorization: Bearer {token}
```

**レスポンス（200 OK）**:
```json
{
  "message": "Logged out successfully"
}
```

**エラーレスポンス（401 Unauthorized）**:
```json
{
  "message": "Unauthenticated"
}
```

### GET /api/user

認証済みユーザー情報取得

**ヘッダー**:
```
Authorization: Bearer {token}
```

**レスポンス（200 OK）**:
```json
{
  "id": "uuid-string",
  "name": "John Doe",
  "email": "user@example.com"
}
```

**エラーレスポンス（401 Unauthorized）**:
```json
{
  "message": "Unauthenticated"
}
```

## トークン管理エンドポイント

### POST /api/tokens

新規トークン発行

**ヘッダー**:
```
Authorization: Bearer {existing-token}
```

**リクエスト（オプション）**:
```json
{
  "name": "Mobile App Token"
}
```

**レスポンス（201 Created）**:
```json
{
  "token": "2|xyz789...",
  "name": "Mobile App Token",
  "created_at": "2025-10-09T00:00:00.000000Z"
}
```

### GET /api/tokens

トークン一覧取得

**ヘッダー**:
```
Authorization: Bearer {token}
```

**レスポンス（200 OK）**:
```json
{
  "tokens": [
    {
      "id": 1,
      "name": "API Token",
      "created_at": "2025-10-09T00:00:00.000000Z",
      "last_used_at": "2025-10-09T01:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Mobile App Token",
      "created_at": "2025-10-09T02:00:00.000000Z",
      "last_used_at": null
    }
  ]
}
```

### DELETE /api/tokens/{id}

特定トークン削除

**ヘッダー**:
```
Authorization: Bearer {token}
```

**レスポンス（200 OK）**:
```json
{
  "message": "Token deleted successfully"
}
```

**エラーレスポンス（404 Not Found）**:
```json
{
  "message": "Token not found"
}
```

### DELETE /api/tokens

全トークン削除（現在のトークン除外）

**ヘッダー**:
```
Authorization: Bearer {token}
```

**レスポンス（200 OK）**:
```json
{
  "message": "All tokens deleted successfully"
}
```

## Next.jsフロントエンド統合

### ログイン実装

**例: Admin App / User App**

```typescript
// lib/api-client.ts
export async function login(email: string, password: string) {
  const response = await fetch('http://localhost:13000/api/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Login failed');
  }

  const data = await response.json();

  // トークンをlocalStorageに保存
  localStorage.setItem('auth_token', data.token);
  localStorage.setItem('user', JSON.stringify(data.user));

  return data;
}
```

### 保護されたAPIアクセス

```typescript
// lib/api-client.ts
export async function fetchProtectedData() {
  const token = localStorage.getItem('auth_token');

  if (!token) {
    throw new Error('No authentication token found');
  }

  const response = await fetch('http://localhost:13000/api/user', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  });

  if (response.status === 401) {
    // トークン無効 - ログイン画面にリダイレクト
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    window.location.href = '/login';
    return;
  }

  if (!response.ok) {
    throw new Error('Failed to fetch data');
  }

  return await response.json();
}
```

### ログアウト実装

```typescript
// lib/api-client.ts
export async function logout() {
  const token = localStorage.getItem('auth_token');

  if (token) {
    await fetch('http://localhost:13000/api/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    });
  }

  // ローカルストレージをクリア
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');

  // ログイン画面にリダイレクト
  window.location.href = '/login';
}
```

### React 19カスタムフック例

```typescript
// hooks/useAuth.ts
'use client';

import { useState, useEffect } from 'react';

interface User {
  id: string;
  name: string;
  email: string;
}

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);

  const login = async (email: string, password: string) => {
    const data = await loginAPI(email, password);
    setUser(data.user);
  };

  const logout = async () => {
    await logoutAPI();
    setUser(null);
  };

  return { user, loading, login, logout };
}
```

## トラブルシューティング

### CORS設定エラー

**症状**: `Access-Control-Allow-Origin` エラー

**解決策**:

1. `config/cors.php`を確認:
```php
'allowed_origins' => [
    'http://localhost:13001',  // User App
    'http://localhost:13002',  // Admin App
],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => false,
```

2. キャッシュクリア:
```bash
php artisan config:clear
```

### トークン検証失敗

**症状**: 401 Unauthorized エラー（有効なトークンを送信しているにも関わらず）

**解決策**:

1. Authorizationヘッダー形式を確認:
```
Authorization: Bearer {token}
```

2. トークンが削除されていないか確認:
```bash
php artisan tinker
>>> User::find('user-id')->tokens;
```

3. guard設定を確認:
```php
// config/auth.php
'defaults' => ['guard' => 'api'],
'guards' => [
    'api' => ['driver' => 'sanctum', 'provider' => 'users'],
],
```

### マイグレーションエラー

**症状**: `personal_access_tokens`テーブル作成失敗

**解決策**:

1. マイグレーションファイル確認:
```bash
ls database/migrations/*personal_access_tokens*.php
```

2. マイグレーション再実行:
```bash
php artisan migrate:fresh
```

3. tokenable_id型確認（UUID/ULID対応）:
```php
// database/migrations/xxx_create_personal_access_tokens_table.php
$table->morphs('tokenable');  // UUIDの場合はuuidMorphs()
```

### 401エラー（未認証）

**症状**: 認証済みエンドポイントで401エラー

**解決策**:

1. トークン存在確認:
```javascript
console.log(localStorage.getItem('auth_token'));
```

2. トークン有効期限確認:
```php
// config/sanctum.php
'expiration' => null,  // nullの場合は無期限
```

3. ミドルウェア設定確認:
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // 保護されたルート
});
```

## セキュリティベストプラクティス

### トークン保護

1. **HTTPS通信**: 本番環境では必ずHTTPSを使用
2. **トークン有効期限**: 本番環境では適切な有効期限を設定（例: 7日間）
3. **XSS対策**: React 19のJSXエスケープ機能を活用、CSP設定推奨

### レート制限

ログインエンドポイントにレート制限を適用:

```php
// routes/api.php
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
```

### パスワードハッシュ化

Userモデルで自動ハッシュ化:

```php
protected function casts(): array
{
    return [
        'password' => 'hashed',
    ];
}
```

## テスト

### Pest 4テスト実行

```bash
# 全認証テスト実行
php artisan test --filter Auth

# 特定テスト実行
php artisan test --filter LoginTest

# カバレッジレポート生成
php artisan test --coverage
```

### テスト結果

- **27テストケース**: 全て成功
- **85アサーション**: 全て成功
- **カバレッジ**: 認証機能90%以上

## 参考資料

- [Laravel Sanctum 公式ドキュメント](https://laravel.com/docs/12.x/sanctum)
- [Next.js 15.5 App Router](https://nextjs.org/docs/app)
- [React 19 Documentation](https://react.dev/)
