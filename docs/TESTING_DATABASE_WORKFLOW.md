# Laravelテスト用データベース設定ワークフロー手順書

## 概要

このドキュメントでは、Laravel 12 + Pest 4 テストフレームワークにおけるテスト用データベースの設定と運用手順について説明します。

### プロジェクト構成
- **Laravel**: 12 (PHP 8.4)
- **テストフレームワーク**: Pest 4
- **データベース**: PostgreSQL 17 (Docker環境)
- **コンテナ管理**: Laravel Sail / Docker Compose

### テスト環境の種類
1. **SQLite in-memory** - 高速テスト実行（デフォルト）
2. **PostgreSQL** - 本番環境と同じDB（Docker環境）
3. **CI/CD環境** - GitHub ActionsでのPostgreSQL並列テスト

---

## 1. ローカル開発環境でのテスト実行

### 1.1 SQLite in-memory テスト（推奨：高速）

```bash
# backend/laravel-apiディレクトリで実行
cd backend/laravel-api

# 通常のテスト実行
./vendor/bin/pest

# カバレッジ付きテスト実行
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage

# 特定のテストファイルのみ実行
./vendor/bin/pest tests/Feature/Auth/LoginTest.php

# 並列テスト実行（高速化）
./vendor/bin/pest --parallel
```

**設定確認**: `phpunit.xml`
```xml
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

### 1.2 PostgreSQL テスト（本番環境同等）

```bash
# Docker環境を起動
cd /path/to/project-root
docker compose up -d pgsql redis

# PostgreSQL設定でテスト実行
cd backend/laravel-api
DB_CONNECTION=pgsql \
DB_HOST=127.0.0.1 \
DB_PORT=13432 \
DB_DATABASE=testing \
DB_USERNAME=sail \
DB_PASSWORD=password \
./vendor/bin/pest
```

**設定方法**: `.env.testing`ファイル作成
```env
# .env.testing
APP_ENV=testing
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=13432
DB_DATABASE=testing
DB_USERNAME=sail
DB_PASSWORD=password
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

**使用方法**:
```bash
# .env.testingを使用したテスト実行
./vendor/bin/pest --env=testing
```

---

## 2. Docker/Laravel Sail環境でのテスト用DB準備

### 2.1 Docker Compose設定確認

**PostgreSQLサービス設定**: `docker-compose.yml`
```yaml
pgsql:
  image: 'postgres:17-alpine'
  environment:
    POSTGRES_DB: '${DB_DATABASE:-laravel}'
    POSTGRES_USER: '${DB_USERNAME:-sail}'
    POSTGRES_PASSWORD: '${DB_PASSWORD:-secret}'
  volumes:
    # テスト用DBの自動作成スクリプト
    - './backend/laravel-api/vendor/laravel/sail/database/pgsql/create-testing-database.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql'
```

### 2.2 Sailでのテスト実行

```bash
# Sailコンテナ内でテスト実行
./vendor/bin/sail test

# Sailコンテナ内での直接Pest実行
./vendor/bin/sail exec laravel-api ./vendor/bin/pest

# PostgreSQL使用でのテスト実行
./vendor/bin/sail exec laravel-api bash -c "
DB_CONNECTION=pgsql \
DB_HOST=pgsql \
DB_PORT=5432 \
DB_DATABASE=testing \
DB_USERNAME=sail \
DB_PASSWORD=secret \
./vendor/bin/pest"
```

### 2.3 テスト用DB初期化

```bash
# マイグレーション実行
./vendor/bin/sail artisan migrate --env=testing --database=pgsql_testing

# テスト用DBリセット
./vendor/bin/sail artisan migrate:fresh --env=testing --database=pgsql_testing

# シーダー実行（必要に応じて）
./vendor/bin/sail artisan db:seed --env=testing --database=pgsql_testing
```

---

## 3. CI/CD（GitHub Actions）でのPostgreSQLテスト環境

### 3.1 現在のGitHub Actions設定

**ファイル**: `.github/workflows/test.yml`

**PostgreSQLサービス設定**:
```yaml
services:
  postgres:
    image: postgres:17-alpine
    env:
      POSTGRES_USER: sail
      POSTGRES_PASSWORD: password
      POSTGRES_DB: testing
    ports:
      - 13432:5432
    options: >-
      --health-cmd pg_isready
      --health-interval 10s
      --health-timeout 5s
      --health-retries 5
```

**並列テスト実行**:
```yaml
strategy:
  matrix:
    shard: [1, 2, 3, 4]  # 4並列でテスト実行

steps:
  - name: Run Pest Tests (Shard ${{ matrix.shard }})
    run: ./vendor/bin/pest --shard=${{ matrix.shard }}/4
```

### 3.2 CI環境でのDB設定

**環境変数**:
```yaml
env:
  DB_CONNECTION: pgsql
  DB_HOST: 127.0.0.1
  DB_PORT: 13432
  DB_DATABASE: testing
  DB_USERNAME: sail
  DB_PASSWORD: password
```

**マイグレーション実行**:
```yaml
- name: Run database migrations
  run: php artisan migrate --force
  env:
    DB_CONNECTION: pgsql
    # ... その他の環境変数
```

---

## 4. テスト用DB初期化・データリセット手順

### 4.1 RefreshDatabaseの使用

**基本的なテストクラス設定**:
```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('example test', function () {
    // テストごとにDBがリフレッシュされる
    $user = User::factory()->create();
    // ...
});
```

### 4.2 手動でのDBリセット

```bash
# SQLite（デフォルト）の場合
# メモリ上なので自動リセット

# PostgreSQLの場合
# テスト用データベースドロップ・再作成
./vendor/bin/sail exec pgsql dropdb -U sail testing
./vendor/bin/sail exec pgsql createdb -U sail testing

# マイグレーション再実行
./vendor/bin/sail artisan migrate --env=testing --database=pgsql_testing
```

### 4.3 テストデータセットアップ

**Factoryを使用したデータ作成**:
```php
test('user can create post', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $response = $this->post('/api/posts', [
        'title' => 'Test Post',
        'content' => 'Test content'
    ]);
    
    $response->assertCreated();
});
```

**Seederを使用した初期データ**:
```php
beforeEach(function () {
    $this->seed([
        RolesSeeder::class,
        PermissionsSeeder::class,
    ]);
});
```

---

## 5. 並列テスト実行時のDB設定（Pest --parallel対応）

### 5.1 SQLite並列テスト

```bash
# 自動的に各プロセスで独立したメモリDBを使用
./vendor/bin/pest --parallel
```

### 5.2 PostgreSQL並列テスト

**データベース設定**: `config/database.php`
```php
'connections' => [
    'pgsql_testing' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => function () {
            $token = env('TEST_TOKEN', '1');
            return "testing_$token";
        },
        // ... その他の設定
    ],
],
```

**並列実行スクリプト**:
```bash
#!/bin/bash
# scripts/parallel-test-setup.sh

# 並列テスト用データベース作成
for i in {1..4}; do
    ./vendor/bin/sail exec pgsql createdb -U sail "testing_$i" 2>/dev/null || true
    DB_DATABASE="testing_$i" php artisan migrate --force
done

# 並列テスト実行
./vendor/bin/pest --parallel --processes=4
```

### 5.3 GitHub Actionsでの並列実行

**現在の設定**:
```yaml
strategy:
  matrix:
    shard: [1, 2, 3, 4]

steps:
  - name: Run Pest Tests (Shard ${{ matrix.shard }})
    run: ./vendor/bin/pest --shard=${{ matrix.shard }}/4
```

**利点**:
- 4つの並列ジョブで実行時間短縮
- 各シャードは独立したPostgreSQLインスタンス使用
- 失敗時は該当シャードのログのみアップロード

---

## 6. 環境別設定切り替え方法

### 6.1 環境変数での切り替え

```bash
# SQLite使用（デフォルト）
./vendor/bin/pest

# PostgreSQL使用
DB_CONNECTION=pgsql ./vendor/bin/pest

# カスタム設定ファイル使用
./vendor/bin/pest --env=testing
```

### 6.2 設定ファイル管理

**ディレクトリ構成**:
```
backend/laravel-api/
├── .env                 # 開発環境
├── .env.testing         # テスト環境（PostgreSQL）
├── .env.testing.sqlite  # テスト環境（SQLite）
└── phpunit.xml          # デフォルトテスト設定
```

**設定切り替えスクリプト**:
```bash
# scripts/switch-test-env.sh
#!/bin/bash

case "$1" in
    "sqlite")
        cp .env.testing.sqlite .env.testing
        echo "Switched to SQLite testing environment"
        ;;
    "pgsql")
        cp .env.testing.pgsql .env.testing
        echo "Switched to PostgreSQL testing environment"
        ;;
    *)
        echo "Usage: $0 {sqlite|pgsql}"
        exit 1
        ;;
esac
```

### 6.3 複数環境対応のMakefile

```makefile
# Makefile
.PHONY: test test-sqlite test-pgsql test-parallel test-coverage

# SQLiteテスト（高速）
test-sqlite:
	cd backend/laravel-api && ./vendor/bin/pest

# PostgreSQLテスト（本番同等）
test-pgsql:
	cd backend/laravel-api && DB_CONNECTION=pgsql \
		DB_HOST=127.0.0.1 \
		DB_PORT=13432 \
		DB_DATABASE=testing \
		DB_USERNAME=sail \
		DB_PASSWORD=password \
		./vendor/bin/pest

# 並列テスト
test-parallel:
	cd backend/laravel-api && ./vendor/bin/pest --parallel

# カバレッジテスト
test-coverage:
	cd backend/laravel-api && XDEBUG_MODE=coverage ./vendor/bin/pest --coverage
```

---

## 7. 推奨運用フロー

### 7.1 日常開発での推奨手順

```bash
# 1. 開発中の高速テスト（SQLite）
./vendor/bin/pest tests/Feature/新機能Test.php

# 2. 機能完成時の詳細テスト（PostgreSQL）
make test-pgsql

# 3. PR前の完全テスト
make test-parallel && make test-coverage
```

### 7.2 環境別テスト戦略

| 環境 | DB | 用途 | 実行タイミング |
|------|----|----|------------|
| 開発ローカル | SQLite | 高速フィードバック | コード変更毎 |
| 開発ローカル | PostgreSQL | 本番同等検証 | 機能完成時 |
| CI/CD | PostgreSQL | 品質保証 | PR/Push時 |

### 7.3 トラブルシューティング対応

**よくある問題と解決方法**:

1. **PostgreSQL接続エラー**
```bash
# コンテナ状態確認
docker compose ps pgsql

# ログ確認
docker compose logs pgsql

# 再起動
docker compose restart pgsql
```

2. **マイグレーション失敗**
```bash
# テストDBリセット
./vendor/bin/sail artisan migrate:fresh --env=testing

# 権限確認
./vendor/bin/sail exec pgsql psql -U sail -d testing -c "\l"
```

3. **並列テスト競合**
```bash
# プロセス数調整
./vendor/bin/pest --parallel --processes=2

# SQLiteで代替実行
DB_CONNECTION=sqlite ./vendor/bin/pest --parallel
```

---

## 8. 追加設定・カスタマイズ

### 8.1 テスト専用データベース設定

**config/database.php**への追加設定:
```php
'testing' => [
    'driver' => 'pgsql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'testing'),
    'username' => env('DB_USERNAME', 'sail'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'prefer',
    // テスト専用の最適化
    'options' => [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_PERSISTENT => false,
    ],
],
```

### 8.2 性能最適化設定

**phpunit.xml**での最適化:
```xml
<php>
    <!-- キャッシュ無効化 -->
    <env name="CACHE_STORE" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    
    <!-- メール無効化 -->
    <env name="MAIL_MAILER" value="array"/>
    
    <!-- ログ最小化 -->
    <env name="LOG_LEVEL" value="emergency"/>
    
    <!-- BCrypt高速化 -->
    <env name="BCRYPT_ROUNDS" value="4"/>
</php>
```

### 8.3 継続的改善

- **テスト実行時間監視**: Pest実行時間をログ記録
- **DB容量監視**: テストデータ蓄積の監視
- **並列度調整**: CI環境での最適なshard数の調整
- **カバレッジ目標**: 85%以上の維持

---

## まとめ

このワークフローにより、開発効率と品質を両立したLaravelテスト環境を運用できます:

✅ **SQLite**: 日常開発での高速フィードバック  
✅ **PostgreSQL**: 本番環境との整合性確認  
✅ **並列実行**: CI/CDでの高速品質チェック  
✅ **柔軟な切り替え**: 環境に応じた最適な設定  

定期的な設定見直しと性能改善により、持続可能なテスト環境を維持してください。