# 開発環境セットアップ手順

Laravel最小限パッケージ構成（API専用）での開発環境構築手順を説明します。

## 目次
- [API専用構成での環境構築](#api専用構成での環境構築)
- [Docker/Laravel Sail環境](#dockerlaravel-sail環境)
- [開発者向けクイックスタート](#開発者向けクイックスタート)
- [CI/CD パイプライン動作確認](#cicd-パイプライン動作確認)
- [トラブルシューティング](#トラブルシューティング)

---

## API専用構成での環境構築

### 前提条件
- Docker Desktop インストール済み
- Git インストール済み
- Node.js LTS (Next.jsフロントエンド用)

### 1. プロジェクトクローンとセットアップ

```bash
# リポジトリクローン
git clone <repository-url>
cd laravel-next-b2c

# バックエンド（Laravel API）ディレクトリへ移動
cd backend/laravel-api
```

### 2. 環境設定ファイル準備

```bash
# .envファイル作成
cp .env.example .env

# 重要な設定を確認・編集
SESSION_DRIVER=array           # API専用: セッション無効化
AUTH_GUARD=sanctum            # デフォルト認証をSanctumに
APP_PORT=13000                # カスタムポート
DB_HOST=pgsql                 # Docker内サービス名
REDIS_HOST=redis              # Docker内サービス名
```

### 3. API専用設定の確認

以下の設定がAPI専用構成になっていることを確認：

**bootstrap/app.php**: Web機能が削除されていることを確認
```php
->withRouting(
    api: __DIR__.'/../routes/api.php',      // API専用
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    // web: は削除されている
)
```

**composer.json**: 最小限の依存関係
```json
{
  "require": {
    "php": "^8.4",
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.0",
    "laravel/tinker": "^2.10.1"
  }
}
```

---

## Docker/Laravel Sail環境

### 1. Laravel Sail の起動

```bash
# 初回セットアップ（依存関係インストール）
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# Sail環境起動
./vendor/bin/sail up -d

# 確認: 以下のコンテナが起動していることを確認
./vendor/bin/sail ps
```

### 2. Laravel アプリケーションセットアップ

```bash
# アプリケーションキー生成
./vendor/bin/sail artisan key:generate

# データベースマイグレーション実行（Sanctum用テーブル含む）
./vendor/bin/sail artisan migrate

# Laravel最適化実行
./vendor/bin/sail artisan optimize
```

### 3. 動作確認

```bash
# ヘルスチェックエンドポイント確認
curl http://localhost:13000/up
# Expected: {"status": "ok"}

# API エンドポイント確認（認証なしでは401エラーが期待される）
curl http://localhost:13000/api/user
# Expected: {"message": "Unauthenticated."}
```

### Docker環境のポート設定

| サービス | ポート | 用途 |
|---------|--------|------|
| Laravel API | 13000 | メインアプリケーション |
| PostgreSQL | 13432 | データベース |
| Redis | 13379 | キャッシュ・セッション |
| Mailpit | 13025 | 開発用メールUI |
| MinIO | 13900 | オブジェクトストレージ |

---

## 開発者向けクイックスタート

### 1. 基本的な開発フロー

```bash
# 1. 環境起動
./vendor/bin/sail up -d

# 2. 開発用コマンド
./vendor/bin/sail artisan tinker    # REPL環境
./vendor/bin/sail artisan pail      # ログ監視
./vendor/bin/sail artisan queue:listen  # キュー処理

# 3. テスト実行
./vendor/bin/sail test              # 全テスト
./vendor/bin/sail test --filter=Sanctum  # 認証テストのみ
```

### 2. API認証確認の動作確認

**ユーザー作成とトークン生成**:
```bash
./vendor/bin/sail artisan tinker

# Tinker内で実行
$user = App\Models\User::factory()->create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password123')
]);

$token = $user->createToken('dev-token');
echo $token->plainTextToken;
// 出力されたトークンをコピー
```

**API認証テスト**:
```bash
# トークンを使用してAPI認証をテスト
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -H "Accept: application/json" \
     http://localhost:13000/api/user

# 期待される結果: ユーザー情報のJSON
```

### 3. フロントエンドとの連携確認

**CORS設定確認**:
```bash
# プリフライトリクエストテスト
curl -X OPTIONS \
     -H "Origin: http://localhost:3000" \
     -H "Access-Control-Request-Method: GET" \
     http://localhost:13000/api/user

# レスポンスヘッダーでCORSが適切に設定されているか確認
```

**Next.js開発サーバーとの連携**:
```bash
# フロントエンドディレクトリへ移動
cd ../../frontend/admin-app  # または user-app

# 依存関係インストール
npm install

# 開発サーバー起動
npm run dev
# http://localhost:3000 でフロントエンド起動

# APIとの通信確認
curl -X GET \
     -H "Origin: http://localhost:3000" \
     http://localhost:13000/up
```

---

## CI/CD パイプライン動作確認

### ローカルでのCI/CD検証

**1. テスト実行**:
```bash
# 全テスト実行
./vendor/bin/sail test

# カバレッジ付きテスト（PHPUnit設定による）
./vendor/bin/sail test --coverage

# 期待される結果
Tests:    92 passed
Time:     <15s
Memory:   <100MB
```

**2. コード品質チェック**:
```bash
# Laravel Pint（コードスタイル）
./vendor/bin/sail pint --test
# Expected: All files pass

# Larastan（静的解析） - 設定されている場合
./vendor/bin/sail artisan analyse
```

**3. パフォーマンス確認**:
```bash
# パフォーマンステスト実行
./vendor/bin/sail test --filter=PerformanceBenchmarkTest

# 期待される結果
✓ 平均起動時間: <50ms
✓ 平均メモリ使用量: <5MB
✓ APIレスポンス時間: <20ms
```

### GitHub Actions設定（参考）

**.github/workflows/laravel.yml** (推奨設定):
```yaml
name: Laravel Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:17-alpine
        env:
          POSTGRES_PASSWORD: password
        ports:
          - 5432:5432
      redis:
        image: redis:alpine
        ports:
          - 6379:6379

    steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        extensions: pdo_pgsql, redis

    - name: Install dependencies
      working-directory: ./backend/laravel-api
      run: composer install --prefer-dist --no-progress

    - name: Configure environment
      working-directory: ./backend/laravel-api
      run: |
        cp .env.example .env
        php artisan key:generate
        echo "SESSION_DRIVER=array" >> .env
        echo "DB_CONNECTION=pgsql" >> .env
        echo "DB_HOST=localhost" >> .env
        echo "DB_PASSWORD=password" >> .env

    - name: Run migrations
      working-directory: ./backend/laravel-api
      run: php artisan migrate --force

    - name: Run tests
      working-directory: ./backend/laravel-api
      run: php artisan test
```

---

## API専用構成の特徴と注意点

### ✅ 有効な機能
- **API認証**: Laravel Sanctum によるトークンベース認証
- **CORS**: Next.jsフロントエンドとの連携
- **ヘルスチェック**: `/up` エンドポイント
- **API ルーティング**: `/api/*` パターン
- **データベース操作**: Eloquent ORM
- **キューシステム**: Redis/Database キュー

### ❌ 無効化された機能
- **Webルート**: `routes/web.php` は削除済み
- **セッション**: `SESSION_DRIVER=array` で無効化
- **CSRF**: API専用でCSRF保護は無効
- **Cookie暗号化**: 不要な暗号化処理を除去
- **ビューレンダリング**: Bladeテンプレートは削除済み

### 認証フローの違い

**従来のWeb認証**:
```php
// セッションベース（使用不可）
Auth::attempt($credentials);
```

**API専用認証**:
```php
// トークンベース（推奨）
$user = User::where('email', $request->email)->first();
if ($user && Hash::check($request->password, $user->password)) {
    $token = $user->createToken('api-token');
    return response()->json(['token' => $token->plainTextToken]);
}
```

---

## トラブルシューティング

### よくある問題と解決方法

**1. Docker環境でポート競合**:
```bash
# ポート使用状況確認
lsof -i :13000

# 競合している場合は.envでポート変更
APP_PORT=13001  # 別のポートに変更
```

**2. データベース接続エラー**:
```bash
# PostgreSQL起動確認
./vendor/bin/sail ps | grep pgsql

# マイグレーション状態確認
./vendor/bin/sail artisan migrate:status

# データベース接続テスト
./vendor/bin/sail artisan tinker
>>> DB::connection()->getPdo();
```

**3. Sanctum認証が動作しない**:
```bash
# personal_access_tokens テーブル確認
./vendor/bin/sail artisan migrate:status

# Sanctum設定確認
./vendor/bin/sail artisan config:show auth

# トークン生成テスト
./vendor/bin/sail artisan tinker
>>> $user = User::first();
>>> $token = $user->createToken('test');
>>> echo $token->plainTextToken;
```

**4. CORS エラー**:
```bash
# CORS設定確認
./vendor/bin/sail artisan config:show cors

# 設定キャッシュクリア
./vendor/bin/sail artisan config:clear
```

### パフォーマンス最適化の確認

**最適化実行**:
```bash
./vendor/bin/sail artisan optimize
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
```

**最適化効果測定**:
```bash
./vendor/bin/sail test --filter=PerformanceBenchmarkTest
```

### ログとデバッグ

**ログ監視**:
```bash
# リアルタイムログ監視
./vendor/bin/sail artisan pail

# ログファイル確認
./vendor/bin/sail exec laravel.test tail -f storage/logs/laravel.log
```

**デバッグ情報**:
```bash
# システム情報確認
./vendor/bin/sail artisan about

# 設定確認
./vendor/bin/sail artisan config:show database
./vendor/bin/sail artisan config:show auth
```

---

## 追加リソース

### 関連ドキュメント
- [Laravel最適化プロセス詳細](./laravel-optimization-process.md)
- [トラブルシューティングガイド](./troubleshooting.md)
- [設定変更詳細](./configuration-changes.md)
- [パフォーマンスレポート](./performance-report.md)

### 外部リソース
- [Laravel 12.x Documentation](https://laravel.com/docs/12.x)
- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum)
- [Laravel Sail](https://laravel.com/docs/12.x/sail)
- [Next.js Documentation](https://nextjs.org/docs)

### 開発支援ツール
```bash
# 便利なエイリアス設定（任意）
echo 'alias sail="./vendor/bin/sail"' >> ~/.bashrc
echo 'alias artisan="./vendor/bin/sail artisan"' >> ~/.bashrc
echo 'alias test="./vendor/bin/sail test"' >> ~/.bashrc

source ~/.bashrc
```

この設定により、`sail artisan migrate`の代わりに`artisan migrate`で実行可能になります。