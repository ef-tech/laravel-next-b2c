# セットアップガイド - 認証機能

Laravel + Next.js B2C アプリケーションの認証機能セットアップ手順

## 目次

- [前提条件](#前提条件)
- [環境セットアップ](#環境セットアップ)
- [データベースセットアップ](#データベースセットアップ)
- [認証機能セットアップ](#認証機能セットアップ)
- [フロントエンドセットアップ](#フロントエンドセットアップ)
- [動作確認](#動作確認)
- [トラブルシューティング](#トラブルシューティング)

---

## 前提条件

### 必須ソフトウェア

| ソフトウェア | バージョン | 用途 |
|------------|----------|------|
| **Docker Desktop** | 最新版 | コンテナ実行環境 |
| **Docker Compose** | v2.x | マルチコンテナ管理 |
| **Git** | 2.x | バージョン管理 |
| **Node.js** | 20.x | フロントエンド実行環境 |
| **npm** | 10.x | パッケージ管理 |

### オプションソフトウェア

| ソフトウェア | バージョン | 用途 |
|------------|----------|------|
| **PHP** | 8.4 | ネイティブ実行（Docker不使用時） |
| **Composer** | 2.x | PHPパッケージ管理 |
| **PostgreSQL** | 17 | データベース（Docker不使用時） |

---

## 環境セットアップ

### 1. リポジトリクローン

```bash
git clone https://github.com/your-org/laravel-next-b2c.git
cd laravel-next-b2c
```

### 2. ブランチ確認

```bash
# 認証機能が実装されているブランチに切り替え
git checkout feature/40/auth-sample
```

### 3. 環境変数設定

#### Laravel API (.env)

```bash
cd backend/laravel-api
cp .env.example .env
```

**.env の主要設定**:

```.env
# アプリケーション設定
APP_NAME="Laravel B2C API"
APP_ENV=local
APP_KEY=  # php artisan key:generate で生成
APP_DEBUG=true
APP_URL=http://localhost:13000
APP_PORT=13000

# 環境変数バリデーション（開発時はスキップ可能）
ENV_VALIDATION_SKIP=true

# データベース設定
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel_b2c
DB_USERNAME=sail
DB_PASSWORD=password

# Laravel Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:13001,localhost:13002
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost

# CORS設定
CORS_ALLOWED_ORIGINS="http://localhost:13001,http://localhost:13002"
CORS_SUPPORTS_CREDENTIALS=true

# セキュリティヘッダー
SECURITY_ENABLE_CSP=true
SECURITY_CSP_REPORT_URI=/api/csp-report

# レート制限
RATELIMIT_CACHE_STORE=redis
```

#### User App (.env.local)

```bash
cd ../../frontend/user-app
cp .env.example .env.local
```

**.env.local の設定**:

```.env
NEXT_PUBLIC_API_URL=http://localhost:13000
NEXT_PUBLIC_API_VERSION=v1
```

#### Admin App (.env.local)

```bash
cd ../admin-app
cp .env.example .env.local
```

**.env.local の設定**:

```.env
NEXT_PUBLIC_API_URL=http://localhost:13000
NEXT_PUBLIC_API_VERSION=v1
```

---

## データベースセットアップ

### 1. Docker Compose でサービス起動

プロジェクトルートで実行:

```bash
cd ../..  # プロジェクトルートに戻る
docker-compose up -d
```

**起動されるサービス**:
- PostgreSQL (ポート: 15432)
- Redis (ポート: 16379)
- Laravel API (ポート: 13000)

### 2. アプリケーションキー生成

```bash
# Docker環境
docker-compose exec laravel.test php artisan key:generate

# またはLaravel Sailを使用
cd backend/laravel-api
./vendor/bin/sail artisan key:generate
```

### 3. マイグレーション実行

```bash
# Docker環境
docker-compose exec laravel.test php artisan migrate

# Laravel Sail
./vendor/bin/sail artisan migrate
```

**作成されるテーブル**:
- `users` - ユーザーテーブル
- `admins` - 管理者テーブル
- `personal_access_tokens` - Sanctumトークンテーブル
- その他Laravelデフォルトテーブル（migrations, password_reset_tokens等）

### 4. Seeder実行（テストデータ作成）

```bash
# Docker環境
docker-compose exec laravel.test php artisan db:seed --class=AdminSeeder

# Laravel Sail
./vendor/bin/sail artisan db:seed --class=AdminSeeder
```

**作成されるデータ**:

| 種別 | メールアドレス | パスワード | ロール |
|-----|--------------|----------|--------|
| 管理者 | admin@example.com | password | super_admin |
| ユーザー | user@example.com | password | - |

---

## 認証機能セットアップ

### 1. Composer パッケージインストール

```bash
cd backend/laravel-api

# Docker環境
docker-compose exec laravel.test composer install

# Laravel Sail
./vendor/bin/sail composer install

# ネイティブ環境
composer install
```

### 2. Sanctum 設定確認

```bash
# config/sanctum.php が存在することを確認
ls -la config/sanctum.php
```

**主要設定**:
- `expiration`: トークン有効期限（デフォルト: 24時間）
- `stateful`: ステートフルなドメイン設定

### 3. ミドルウェア確認

```bash
# UserGuard と AdminGuard が存在することを確認
ls -la app/Http/Middleware/UserGuard.php
ls -la app/Http/Middleware/AdminGuard.php
```

### 4. ルート確認

```bash
# API ルート確認
php artisan route:list --path=api/v1

# 期待される出力（抜粋）:
# POST   api/v1/user/login
# POST   api/v1/user/logout
# GET    api/v1/user/profile
# POST   api/v1/admin/login
# POST   api/v1/admin/logout
# GET    api/v1/admin/dashboard
```

---

## フロントエンドセットアップ

### 1. User App セットアップ

```bash
cd frontend/user-app

# 依存パッケージインストール
npm ci

# 開発サーバー起動（ポート: 13001）
npm run dev
```

### 2. Admin App セットアップ

```bash
cd ../admin-app

# 依存パッケージインストール
npm ci

# 開発サーバー起動（ポート: 13002）
npm run dev
```

### 3. すべてのサービスを一度に起動

プロジェクトルートで:

```bash
# make dev コマンドを使用（推奨）
make dev

# または手動で起動
docker-compose up -d
cd frontend/user-app && npm run dev &
cd frontend/admin-app && npm run dev &
```

---

## 動作確認

### 1. ヘルスチェック確認

```bash
# Laravel API
curl http://localhost:13000/api/health

# 期待される出力:
# {"status":"ok","timestamp":"2025-01-27T12:00:00Z"}
```

### 2. User ログインテスト

#### API直接テスト

```bash
curl -X POST http://localhost:13000/api/v1/user/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```

**期待される出力**:
```json
{
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "user@example.com",
    "created_at": "2025-01-27T00:00:00Z",
    "updated_at": "2025-01-27T00:00:00Z"
  }
}
```

#### フロントエンドテスト

1. ブラウザで `http://localhost:13001` にアクセス
2. ログインページが表示されることを確認
3. 以下の認証情報でログイン:
   - **メールアドレス**: `user@example.com`
   - **パスワード**: `password`
4. プロフィール画面にリダイレクトされることを確認
5. ユーザー名が表示されることを確認

### 3. Admin ログインテスト

#### API直接テスト

```bash
curl -X POST http://localhost:13000/api/v1/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

**期待される出力**:
```json
{
  "token": "2|zyxwvutsrqponmlkjihgfedcba0987654321",
  "admin": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "role": "super_admin",
    "is_active": true,
    "created_at": "2025-01-27T00:00:00Z",
    "updated_at": "2025-01-27T00:00:00Z"
  }
}
```

#### フロントエンドテスト

1. ブラウザで `http://localhost:13002` にアクセス
2. ログインページが表示されることを確認
3. 以下の認証情報でログイン:
   - **メールアドレス**: `admin@example.com`
   - **パスワード**: `password`
4. ダッシュボード画面にリダイレクトされることを確認
5. 管理者名とロールが表示されることを確認

### 4. ガード分離テスト

#### User トークンで Admin エンドポイントにアクセス

```bash
# 1. User トークン取得
USER_TOKEN=$(curl -s -X POST http://localhost:13000/api/v1/user/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' \
  | jq -r '.token')

# 2. Admin エンドポイントにアクセス（失敗するはず）
curl -X GET http://localhost:13000/api/v1/admin/dashboard \
  -H "Authorization: Bearer $USER_TOKEN"

# 期待される出力（401 Unauthorized）:
# {"code":"AUTH.UNAUTHORIZED","message":"認証が必要です","details":{}}
```

#### Admin トークンで User エンドポイントにアクセス

```bash
# 1. Admin トークン取得
ADMIN_TOKEN=$(curl -s -X POST http://localhost:13000/api/v1/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' \
  | jq -r '.token')

# 2. User エンドポイントにアクセス（失敗するはず）
curl -X GET http://localhost:13000/api/v1/user/profile \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 期待される出力（401 Unauthorized）:
# {"code":"AUTH.UNAUTHORIZED","message":"認証が必要です","details":{}}
```

---

## テスト実行

### 1. バックエンドテスト

```bash
cd backend/laravel-api

# 全テスト実行
./vendor/bin/pest

# 認証関連テストのみ実行
./vendor/bin/pest --filter=Auth

# カバレッジレポート生成
./vendor/bin/pest --coverage --min=80
```

### 2. フロントエンドテスト

#### User App テスト

```bash
cd frontend/user-app
npm test
```

#### Admin App テスト

```bash
cd frontend/admin-app
npm test
```

### 3. E2Eテスト

```bash
cd e2e

# 依存パッケージインストール
npm ci

# Playwright ブラウザインストール
npx playwright install --with-deps chromium

# 全E2Eテスト実行
npm test

# User App のみ
npm run test:user

# Admin App のみ
npm run test:admin
```

---

## 管理者アカウント情報

### デフォルト管理者アカウント

| 項目 | 値 |
|-----|-----|
| **名前** | Admin User |
| **メールアドレス** | admin@example.com |
| **パスワード** | password |
| **ロール** | super_admin |
| **有効状態** | 有効 (is_active: true) |

### デフォルトユーザーアカウント

| 項目 | 値 |
|-----|-----|
| **名前** | Test User |
| **メールアドレス** | user@example.com |
| **パスワード** | password |

**⚠️ セキュリティ警告**: 本番環境では必ずこれらのデフォルトアカウントを削除または強力なパスワードに変更してください。

---

## 追加管理者作成

### Tinker を使用

```bash
# Docker環境
docker-compose exec laravel.test php artisan tinker

# Laravel Sail
./vendor/bin/sail artisan tinker
```

**Tinker内で実行**:

```php
use App\Models\Admin;
use App\DDD\Domain\Admin\ValueObjects\AdminRole;

$admin = new Admin();
$admin->name = 'New Admin';
$admin->email = 'newadmin@example.com';
$admin->password = bcrypt('secure_password_here');
$admin->role = AdminRole::ADMIN;
$admin->is_active = true;
$admin->save();
```

### Seeder を使用

```php
// database/seeders/CustomAdminSeeder.php
use App\Models\Admin;
use App\DDD\Domain\Admin\ValueObjects\AdminRole;

class CustomAdminSeeder extends Seeder
{
    public function run()
    {
        Admin::create([
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => bcrypt('secure_password_here'),
            'role' => AdminRole::ADMIN,
            'is_active' => true,
        ]);
    }
}
```

```bash
php artisan db:seed --class=CustomAdminSeeder
```

---

## ポート一覧

| サービス | ポート | URL |
|---------|--------|-----|
| **User App** | 13001 | http://localhost:13001 |
| **Admin App** | 13002 | http://localhost:13002 |
| **Laravel API** | 13000 | http://localhost:13000 |
| **PostgreSQL** | 15432 | localhost:15432 |
| **Redis** | 16379 | localhost:16379 |

---

## サービス停止

```bash
# すべてのサービス停止
docker-compose down

# データベースデータも削除
docker-compose down -v

# フロントエンド開発サーバー停止
# Ctrl+C で停止
```

---

## トラブルシューティング

よくある問題と解決策については、[トラブルシューティングガイド](./TROUBLESHOOTING.md)を参照してください。

---

## 次のステップ

1. **API仕様を確認**: [API仕様書](./API_SPECIFICATION.md)
2. **認証フローを理解**: [認証フロー図](./AUTHENTICATION_FLOW.md)
3. **セキュリティ対策を確認**: [セキュリティベストプラクティス](./SECURITY_BEST_PRACTICES.md)
4. **APIバージョニングを理解**: [APIバージョニング戦略](./API_VERSIONING_STRATEGY.md)

---

## サポート

質問や問題がある場合:

1. [トラブルシューティングガイド](./TROUBLESHOOTING.md)を確認
2. GitHub Issues で報告: https://github.com/your-org/laravel-next-b2c/issues
3. プロジェクトドキュメントを確認: https://docs.example.com
