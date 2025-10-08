# テストデータベース運用ワークフローガイド

Laravel 12 Pest 4テスト環境における、SQLiteとPostgreSQLのハイブリッドテストデータベース運用ガイド

## 目次

- [概要](#概要)
- [テスト用データベース設定](#テスト用データベース設定)
- [ローカル開発環境](#ローカル開発環境)
- [CI/CD環境](#cicd環境)
- [トラブルシューティング](#トラブルシューティング)
- [推奨運用フロー](#推奨運用フロー)

---

## 概要

### ハイブリッドテストデータベース戦略

このプロジェクトでは、**開発速度**と**本番環境互換性**を両立するため、2種類のデータベースを使い分けます：

| データベース | 用途 | 特徴 | 実行時間 |
|------------|------|------|---------|
| **SQLite (in-memory)** | 日常開発・高速テスト | メモリ内実行、軽量、瞬時起動 | ~2秒 |
| **PostgreSQL** | 本番同等テスト・CI/CD | 本番環境と同じDB、SQL互換性検証 | ~5-10秒 |

### テスト用データベース一覧

| データベース名 | 用途 | 接続設定 |
|--------------|------|---------|
| `app_test` | PostgreSQL単体テスト用 | `pgsql_testing`接続 |
| `testing_1` ~ `testing_4` | PostgreSQL並列テスト用（4 Shard） | `pgsql_testing`接続 |
| `:memory:` | SQLite高速テスト用 | `sqlite`接続 |

---

## テスト用データベース設定

### 接続設定（`config/database.php`）

#### 1. PostgreSQLテスト専用接続（`pgsql_testing`）

```php
'pgsql_testing' => [
    'driver' => 'pgsql',
    'host' => env('DB_TEST_HOST', env('DB_HOST', '127.0.0.1')),
    'port' => env('DB_TEST_PORT', env('DB_PORT', '5432')),
    'database' => env('DB_TEST_DATABASE', 'app_test'),
    'username' => env('DB_TEST_USERNAME', env('DB_USERNAME', 'root')),
    'password' => env('DB_TEST_PASSWORD', env('DB_PASSWORD', '')),
    // ... その他の設定
],
```

**特徴:**
- 環境変数 `DB_TEST_*` を優先、未設定時は `DB_*` にフォールバック
- デフォルトデータベース名: `app_test`
- Docker環境対応（ホスト: `pgsql`、ポート: `13432`）

#### 2. SQLite高速テスト接続

```php
'sqlite' => [
    'driver' => 'sqlite',
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    // ...
],
```

### 環境別設定ファイル

#### SQLiteテスト環境（`.env.testing.sqlite`）

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# 最適化設定
BCRYPT_ROUNDS=4
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

#### PostgreSQLテスト環境（`.env.testing.pgsql`）

```env
APP_ENV=testing
DB_CONNECTION=pgsql_testing

# Docker環境用
DB_TEST_HOST=pgsql
DB_TEST_PORT=13432
DB_TEST_DATABASE=app_test
DB_TEST_USERNAME=sail
DB_TEST_PASSWORD=password

# 最適化設定
BCRYPT_ROUNDS=4
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

---

## ローカル開発環境

### 環境切り替え方法

#### 方法1: Makefileターゲット（推奨）

```bash
# SQLite環境に切り替え
make test-switch-sqlite

# PostgreSQL環境に切り替え
make test-switch-pgsql
```

#### 方法2: スクリプト直接実行

```bash
# SQLite環境に切り替え
./scripts/switch-test-env.sh sqlite

# PostgreSQL環境に切り替え
./scripts/switch-test-env.sh pgsql
```

### テスト実行コマンド

#### 高速開発テスト（SQLite）

```bash
# Makefileから実行
make quick-test

# または直接実行
cd backend/laravel-api
./vendor/bin/pest
```

**実行時間:** ~2秒  
**用途:** TDD開発、リファクタリング検証

#### 本番同等テスト（PostgreSQL）

```bash
# Makefileから実行（Docker環境チェック付き）
make test-pgsql

# または環境変数指定で直接実行
cd backend/laravel-api
DB_CONNECTION=pgsql_testing \
DB_TEST_HOST=pgsql \
DB_TEST_PORT=13432 \
DB_TEST_DATABASE=app_test \
DB_TEST_USERNAME=sail \
DB_TEST_PASSWORD=password \
./vendor/bin/pest
```

**実行時間:** ~5-10秒  
**用途:** PR前の最終確認、PostgreSQL固有機能のテスト

#### 並列テスト実行（PostgreSQL）

```bash
# Makefileから実行（セットアップ→実行→クリーンアップ）
make test-parallel

# 手動実行
./scripts/parallel-test-setup.sh 4
cd backend/laravel-api
./vendor/bin/pest --parallel
./scripts/parallel-test-cleanup.sh 4
```

**実行時間:** ~3-5秒（4並列）  
**用途:** 大規模テストスイートの高速実行

#### カバレッジ付きテスト

```bash
make test-coverage

# または
cd backend/laravel-api
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=85
```

### Docker環境のセットアップ

#### PostgreSQLコンテナ起動

```bash
# Docker Composeで起動
make docker-up

# または
docker compose up -d pgsql redis
```

#### テスト用データベース作成

```bash
# 自動セットアップ（app_test + testing_1〜testing_4）
make test-setup

# または
./scripts/parallel-test-setup.sh 4
```

#### データベース存在確認

```bash
make test-db-check

# 出力例:
# ✅ app_test: 存在します
# ✅ testing_1: 存在します
# ✅ testing_2: 存在します
# ✅ testing_3: 存在します
# ✅ testing_4: 存在します
```

---

## CI/CD環境

### GitHub Actions並列PostgreSQLテスト

#### ワークフロー構成（`.github/workflows/test.yml`）

```yaml
jobs:
  test:
    strategy:
      matrix:
        shard: [1, 2, 3, 4]
    
    services:
      postgres:
        image: postgres:17-alpine
        env:
          POSTGRES_USER: sail
          POSTGRES_PASSWORD: password
        ports:
          - 13432:5432
    
    steps:
      - name: Create shard test database
        run: PGPASSWORD=password psql -h 127.0.0.1 -p 13432 -U sail -d postgres -c "CREATE DATABASE testing_${{ matrix.shard }} OWNER sail;"
      
      - name: Run Pest Tests (Shard ${{ matrix.shard }})
        run: ./vendor/bin/pest --shard=${{ matrix.shard }}/4
        env:
          DB_CONNECTION: pgsql_testing
          DB_TEST_HOST: 127.0.0.1
          DB_TEST_PORT: 13432
          DB_TEST_DATABASE: testing_${{ matrix.shard }}
          DB_TEST_USERNAME: sail
          DB_TEST_PASSWORD: password
```

#### 並列実行の仕組み

1. **PostgreSQL Serviceコンテナ起動** - ポート `13432:5432` でマッピング
2. **4並列Matrixジョブ** - Shard 1〜4が同時実行
3. **各Shard専用DB作成** - `testing_1`、`testing_2`、`testing_3`、`testing_4`
4. **並列テスト実行** - `./vendor/bin/pest --shard=${{ matrix.shard }}/4`

**利点:**
- 各Shardが独立したデータベースを使用（データ競合なし）
- 4並列実行で実行時間を1/4に短縮
- 本番環境と同じPostgreSQL 17で互換性検証

---

## トラブルシューティング

### PostgreSQL接続エラー

#### エラー: `could not find driver (Connection: pgsql_testing)`

**原因:** PostgreSQL PDOドライバーが有効でない

**解決策:**
```bash
# PHP拡張確認
php -m | grep pdo_pgsql

# Docker環境で実行（推奨）
docker compose exec laravel.test ./vendor/bin/pest

# または環境変数で接続設定を指定
DB_CONNECTION=sqlite ./vendor/bin/pest
```

#### エラー: `PostgreSQLコンテナが起動していません`

**原因:** Docker PostgreSQLコンテナが未起動

**解決策:**
```bash
# コンテナ状態確認
docker compose ps pgsql

# コンテナ起動
make docker-up

# または
docker compose up -d pgsql
```

#### エラー: `psql: error: connection to server on socket failed`

**原因:** ポート設定の不一致

**解決策:**
```bash
# 正しいポート（13432）を指定
docker compose exec -T pgsql psql -U sail -h localhost -p 13432 -d postgres -c '\l'

# または.env.testing.pgsqlでDB_TEST_PORT=13432を確認
```

### マイグレーション失敗時の対処法

#### エラー: `Migration table not found`

**解決策:**
```bash
# テスト環境でマイグレーション実行
cd backend/laravel-api
php artisan migrate --env=testing

# PostgreSQL環境の場合
DB_CONNECTION=pgsql_testing \
DB_TEST_DATABASE=app_test \
php artisan migrate --force
```

#### エラー: `Syntax error: PostgreSQL固有SQL`

**解決策:**
1. SQLite環境で再実行してエラー切り分け
   ```bash
   make test-switch-sqlite
   make quick-test
   ```

2. PostgreSQL固有機能の使用を確認
   - `ARRAY`型、`JSONB`型、ウィンドウ関数など
   - 必要に応じてSQLiteとPostgreSQLで条件分岐

### 並列テスト競合時の解決方法

#### エラー: `Database already exists: testing_1`

**解決策:**
```bash
# テスト環境クリーンアップ
make test-cleanup

# または手動削除
./scripts/parallel-test-cleanup.sh 4

# 再セットアップ
make test-setup
```

---

## 推奨運用フロー

### 日常開発フロー

```bash
# 1. SQLite高速テスト（開発中）
make quick-test

# 2. 機能完成時: PostgreSQL本番同等テスト
make test-pgsql

# 3. PR前: CI/CD相当の完全テスト
make ci-test
```

### 各フローの使い分け

| フロー | コマンド | 実行時間 | 用途 |
|--------|---------|---------|------|
| **高速開発テスト** | `make quick-test` | ~2秒 | TDD開発、リファクタリング検証 |
| **本番同等テスト** | `make test-pgsql` | ~5-10秒 | 機能完成時、PostgreSQL固有機能テスト |
| **並列テスト** | `make test-parallel` | ~3-5秒 | 大規模テストスイートの高速実行 |
| **カバレッジ確認** | `make test-coverage` | ~10-15秒 | PR前、品質基準確認（85%以上） |
| **完全テスト** | `make ci-test` | ~20-30秒 | PR前、CI/CD相当の全チェック |

### 環境ヘルスチェック

```bash
# Docker環境、Laravel設定、テスト環境、DB接続の総合チェック
make health

# 出力例:
# 🏥 環境ヘルスチェック実行中...
# 
# 📋 Docker環境:
# pgsql   Up (healthy)
# redis   Up (healthy)
# 
# 📋 Laravel設定:
# Laravel Framework 12.x
# 
# 📋 テスト環境:
# Pest 4.x
# 
# 📋 データベース接続:
# Migration table: ✅
# 
# ✅ ヘルスチェック完了
```

### 開発環境スタート

```bash
# Docker起動 + SQLite設定
make dev

# 出力:
# ✅ 開発環境の準備が完了しました！
#    テスト実行: make test
```

### 本番同等テスト環境

```bash
# Docker起動 + PostgreSQL設定
make prod-test

# 出力:
# ✅ 本番同等テスト環境の準備が完了しました！
#    テスト実行: make test-pgsql
```

---

## 設定ファイル一覧

| ファイル | 用途 |
|---------|------|
| `config/database.php` | データベース接続設定 |
| `.env.testing.sqlite` | SQLiteテスト環境設定 |
| `.env.testing.pgsql` | PostgreSQLテスト環境設定 |
| `phpunit.xml` | Pest実行設定（SQLiteデフォルト） |
| `scripts/switch-test-env.sh` | 環境切り替えスクリプト |
| `scripts/parallel-test-setup.sh` | 並列テスト環境セットアップ |
| `scripts/parallel-test-cleanup.sh` | 並列テスト環境クリーンアップ |
| `scripts/check-test-db.sh` | テスト用DB存在確認 |
| `Makefile` | テストワークフロー統合 |

---

## まとめ

このハイブリッドテストデータベース戦略により、以下を実現できます：

✅ **高速な開発サイクル** - SQLiteで2秒以内のテスト実行  
✅ **本番環境互換性** - PostgreSQLで実環境と同等のテスト  
✅ **並列実行最適化** - 4 Shard並列で実行時間を1/4に短縮  
✅ **CI/CD統合** - GitHub Actionsで自動品質検証  
✅ **簡単な環境切り替え** - Makefileコマンド1つで切り替え可能

推奨フロー:  
**日常開発: SQLite → 機能完成: PostgreSQL → PR前: 完全テスト → CI/CD: 並列PostgreSQL**
