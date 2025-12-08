# PostgreSQL接続設定ガイド

本ドキュメントは、Laravel 12 API専用プロジェクトにおけるPostgreSQL 17接続設定の最適化について説明します。

## 目次

- [概要](#概要)
- [環境別接続設定](#環境別接続設定)
  - [Docker環境](#docker環境)
  - [ネイティブ環境](#ネイティブ環境)
  - [本番環境](#本番環境)
- [環境変数の説明](#環境変数の説明)
- [タイムアウト設定](#タイムアウト設定)
- [PDO設定](#pdo設定)
- [トラブルシューティング](#トラブルシューティング)
- [パフォーマンステスト](#パフォーマンステスト)
- [推奨監視項目](#推奨監視項目)

## 概要

PostgreSQL 17接続設定は、以下の目標を達成するために最適化されています：

- **接続の安定性**: タイムアウト設定による障害の早期検知
- **環境別設定分離**: Docker/ネイティブ/本番環境での明確な設定分離
- **ステートレスAPI設計**: 短いトランザクション、接続プール前提設計
- **可観測性**: application_nameによる接続追跡、PostgreSQLログ統合
- **後方互換性**: 既存SQLite環境を破壊しない

## 環境別接続設定

### Docker環境

Docker Compose使用時の推奨設定です。

```env
# .env
DB_CONNECTION=pgsql
DB_HOST=pgsql                    # Docker内: service名
DB_PORT=5432                     # Docker内: デフォルトポート（内部ネットワーク専用）
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=secret
DB_SSLMODE=disable               # ローカル環境はSSL不要

# タイムアウト設定
DB_STATEMENT_TIMEOUT=60000       # 60秒（長時間クエリ防止）
DB_IDLE_TX_TIMEOUT=60000         # 60秒（放置トランザクション防止）
DB_LOCK_TIMEOUT=0                # デッドロック即座検知
DB_CONNECT_TIMEOUT=5             # 5秒（接続タイムアウト）

# アプリケーション名
DB_APP_NAME=laravel-next-b2c-api
```

**起動手順:**

```bash
# PostgreSQLコンテナ起動
./vendor/bin/sail up -d

# 接続確認
./vendor/bin/sail artisan tinker
>>> DB::connection()->getPdo();
>>> DB::select('SELECT version()');

# マイグレーション実行
./vendor/bin/sail artisan migrate:fresh --seed
```

### ネイティブ環境

ホストマシンからDockerコンテナPostgreSQLへ接続する場合の設定です。

**注意**: PostgreSQLは内部ネットワーク専用のため、ホストから直接TCP接続はできません。以下の方法でアクセスしてください：

1. **Docker exec経由でアクセス（推奨）**:
   ```bash
   docker compose exec pgsql psql -U sail -d laravel
   ```

2. **Laravel Sail経由でアクセス**:
   ```env
   # .env
   DB_CONNECTION=pgsql
   DB_HOST=pgsql                    # Docker内: service名
   DB_PORT=5432                     # Docker内: デフォルトポート
   DB_DATABASE=laravel
   DB_USERNAME=sail
   DB_PASSWORD=secret
   DB_SSLMODE=disable               # ローカル環境はSSL不要

   # タイムアウト設定（Docker環境と同じ）
   DB_STATEMENT_TIMEOUT=60000
   DB_IDLE_TX_TIMEOUT=60000
   DB_LOCK_TIMEOUT=0
   DB_CONNECT_TIMEOUT=5

   # アプリケーション名
   DB_APP_NAME=laravel-next-b2c-api
   ```

**起動手順:**

```bash
# PostgreSQLコンテナを起動
docker compose up -d pgsql

# 接続確認（Docker exec経由）
docker compose exec pgsql psql -U sail -d laravel -c "SELECT version();"

# または、Sail経由でアプリケーションから接続
./vendor/bin/sail artisan tinker
>>> DB::connection()->getPdo();
>>> DB::select('SELECT version()');

# テスト実行
./vendor/bin/sail test
```

### 本番環境

マネージドデータベースサービス（AWS RDS、Cloud SQL等）接続時の推奨設定です。

```env
# .env.production
DB_CONNECTION=pgsql
DB_HOST=<マネージドDB エンドポイント>
DB_PORT=5432
DB_DATABASE=<データベース名>
DB_USERNAME=<ユーザー名>
DB_PASSWORD=<パスワード>

# SSL設定（必須）
DB_SSLMODE=verify-full           # SSL必須（証明書検証）
DB_SSLROOTCERT=/path/to/ca-certificate.crt

# タイムアウト設定（より厳格）
DB_STATEMENT_TIMEOUT=30000       # 30秒（本番環境では短めに）
DB_IDLE_TX_TIMEOUT=30000         # 30秒
DB_LOCK_TIMEOUT=0                # デッドロック即座検知
DB_CONNECT_TIMEOUT=5             # 5秒

# アプリケーション名
DB_APP_NAME=laravel-next-b2c-api
```

**注意事項:**

- SSL証明書は事前にダウンロードして配置
- `DB_SSLMODE=verify-full`でホスト名検証を実施
- タイムアウト値は本番環境の要件に応じて調整
- 接続プール（PgBouncer等）の導入を推奨

## 環境変数の説明

### 基本接続設定

| 環境変数 | デフォルト値 | 説明 |
|---------|------------|------|
| `DB_CONNECTION` | `sqlite` | 使用するデータベースドライバー（`pgsql`に変更） |
| `DB_HOST` | `127.0.0.1` | PostgreSQLサーバーのホスト名 |
| `DB_PORT` | `5432` | PostgreSQLサーバーのポート番号 |
| `DB_DATABASE` | `laravel` | データベース名 |
| `DB_USERNAME` | `sail` | データベースユーザー名 |
| `DB_PASSWORD` | `secret` | データベースパスワード |
| `DB_CHARSET` | `utf8` | 文字セット |

### PostgreSQL固有設定

| 環境変数 | デフォルト値 | 説明 |
|---------|------------|------|
| `DB_SEARCH_PATH` | `public` | PostgreSQLスキーマ検索パス |
| `DB_SSLMODE` | `prefer` | SSL接続モード（`disable`, `allow`, `prefer`, `require`, `verify-ca`, `verify-full`） |
| `DB_APP_NAME` | `laravel-next-b2c-api` | アプリケーション名（PostgreSQLログに記録） |

### タイムアウト設定

| 環境変数 | デフォルト値 | 説明 |
|---------|------------|------|
| `DB_STATEMENT_TIMEOUT` | `60000` | クエリ実行タイムアウト（ミリ秒単位） |
| `DB_IDLE_TX_TIMEOUT` | `60000` | アイドルトランザクションタイムアウト（ミリ秒単位） |
| `DB_LOCK_TIMEOUT` | `0` | ロック待機タイムアウト（ミリ秒単位、0=即座検知） |
| `DB_CONNECT_TIMEOUT` | `5` | 接続タイムアウト（秒単位） |

### PDO設定

| 環境変数 | デフォルト値 | 説明 |
|---------|------------|------|
| `DB_EMULATE_PREPARES` | `true` | クライアント側プリペアドステートメントのエミュレート |

## タイムアウト設定

### statement_timeout（クエリ実行タイムアウト）

長時間クエリによるリソース枯渇を防止します。

**推奨値:**
- 開発環境: 60000ms（60秒）
- 本番環境: 30000ms（30秒）

**動作:**
- クエリ実行が設定時間を超えると `SQLSTATE 57014: query_canceled` エラー
- `SELECT pg_sleep(61)` で動作確認可能

**調整ガイドライン:**
- クエリの最適化（インデックス追加、N+1問題解消）を優先
- やむを得ず延長する場合は、該当クエリのみ個別設定を検討

### idle_in_transaction_session_timeout（アイドルトランザクションタイムアウト）

放置トランザクションによるロック保持を防止します。

**推奨値:**
- 開発環境: 60000ms（60秒）
- 本番環境: 30000ms（30秒）

**動作:**
- トランザクション内でアイドル状態が続くと `SQLSTATE 57P01: admin_shutdown` エラー
- 外部API呼び出しをトランザクション外に移動することで回避

**調整ガイドライン:**
- トランザクション範囲を最小化（BEGIN→COMMIT間を短く）
- 外部システム依存処理をトランザクション外に分離

### lock_timeout（ロック待機タイムアウト）

デッドロックを即座に検知します。

**推奨値:**
- 全環境: 0（デッドロック即座検知）

**動作:**
- ロック獲得待機で即座に `SQLSTATE 40P01: deadlock_detected` エラー
- トランザクションのリトライ戦略で対応

### connect_timeout（接続タイムアウト）

接続確立の遅延を検知します。

**推奨値:**
- 全環境: 5秒

**動作:**
- 5秒以内に接続確立できない場合エラー
- ネットワーク疎通やPostgreSQLサーバー起動状態を確認

## PDO設定

### ATTR_EMULATE_PREPARES（プリペアドステートメントのエミュレート）

**推奨値: true（デフォルト）**

**理由:**
- サーバ側プリペアドステートメント肥大化を回避
- Laravel APIの多様なクエリパターンに適合
- PostgreSQLメモリ使用量を削減

**false に設定する場合:**
- 同一クエリを頻繁に実行する場合（バッチ処理等）
- サーバ側でのパラメータ型検証が必要な場合

## トラブルシューティング

### 接続失敗

**症状:**
```
SQLSTATE[08006] [7] could not connect to server
```

**原因と対処:**

1. **PostgreSQLサーバーが起動していない**
   ```bash
   # Docker環境
   ./vendor/bin/sail up -d

   # ネイティブ環境
   docker compose up -d pgsql

   # 状態確認
   docker compose ps
   ```

2. **ホスト名またはポート番号が間違っている**
   - Docker環境: `DB_HOST=pgsql`, `DB_PORT=5432`
   - PostgreSQLは内部ネットワーク専用のため、ホストから直接TCP接続は不可

3. **ネットワーク疎通ができない**
   ```bash
   # 接続確認（Docker exec経由）
   docker compose exec pgsql pg_isready -U sail -p 5432

   # または、コンテナ内から接続確認
   docker compose exec pgsql psql -U sail -d laravel -c "SELECT 1;"
   ```

### タイムアウトエラー

**症状:**
```
SQLSTATE[57014] canceling statement due to statement timeout
```

**原因と対処:**

1. **長時間クエリ**
   - クエリの最適化（EXPLAINで実行計画確認）
   - インデックスの追加
   - N+1問題の解消（Eager Loading使用）

2. **やむを得ず延長する場合**
   ```php
   // 個別クエリのみタイムアウトを延長
   DB::statement('SET statement_timeout = 120000'); // 120秒
   // 長時間クエリ実行
   DB::statement('SET statement_timeout = 60000'); // 元に戻す
   ```

### SSL証明書エラー

**症状:**
```
SQLSTATE[08006] SSL error: certificate verify failed
```

**原因と対処:**

1. **証明書パスが間違っている**
   - `DB_SSLROOTCERT`の値を確認
   - ファイルの存在を確認

2. **証明書の有効期限切れ**
   - 最新の証明書をダウンロード

3. **ホスト名検証失敗**
   - `DB_SSLMODE=verify-ca`に変更（ホスト名検証をスキップ）
   - 本番環境では非推奨

## パフォーマンステスト

### 接続時間計測

```bash
# Docker環境
./vendor/bin/sail artisan tinker
>>> $start = microtime(true);
>>> DB::connection()->getPdo();
>>> echo (microtime(true) - $start) * 1000 . ' ms';

# 期待値: 50ms以下
```

### クエリ実行時間計測

```php
// Eloquent Modelでのクエリログ有効化
DB::enableQueryLog();

// クエリ実行
User::all();

// 実行時間確認
$queries = DB::getQueryLog();
foreach ($queries as $query) {
    echo $query['time'] . ' ms: ' . $query['query'] . PHP_EOL;
}

// 期待値: 各クエリ50ms以下
```

### タイムアウト動作確認

#### 手動テスト（Tinker）

```bash
./vendor/bin/sail artisan tinker
>>> DB::select('SELECT pg_sleep(61)'); // 60秒タイムアウトを超過
# 期待: SQLSTATE[57014] エラー
```

#### 自動テスト（Pest）

**SQLite環境（デフォルト）:**

```bash
# PostgreSQLタイムアウトテストは自動的にスキップされます
./vendor/bin/pest tests/Feature/Database/PostgresTimeoutTest.php

# 出力例:
# WARN  Tests\Feature\Database\PostgresTimeoutTest
#   - statement_timeout超過で適切なエラーが発生する → PostgreSQL接続が必要
#   - (他5件も同様にスキップ)
# Tests:    6 skipped (0 assertions)
```

**PostgreSQL環境（テスト実行）:**

PostgreSQL接続を使用してタイムアウトテストを実行する場合:

```bash
# 1. PostgreSQLコンテナが起動していることを確認
docker compose ps pgsql

# 2. .env.testing.pgsqlを使用してテスト実行
cp .env.testing.pgsql .env.testing
./vendor/bin/pest tests/Feature/Database/PostgresTimeoutTest.php

# 期待される結果:
# PASS  Tests\Feature\Database\PostgresTimeoutTest
#   ✓ statement_timeout超過で適切なエラーが発生する
#   ✓ statement_timeout未超過のクエリは正常実行される
#   ✓ connect_timeoutの範囲内で接続が確立される
#   ✓ lock_timeout設定が正しく適用されている
#   ✓ タイムアウト設定値がPostgreSQLセッションに正しく適用されている
# Tests:    5 passed (1 skipped)
# Duration: ~7s
```

**テスト内容:**

| テスト名 | 検証内容 | 実行時間 |
|---------|---------|---------|
| `statement_timeout超過で適切なエラーが発生する` | 6秒スリープでタイムアウトエラー確認 | ~6s |
| `statement_timeout未超過のクエリは正常実行される` | 1秒スリープで正常実行確認 | ~1s |
| `idle_in_transaction_session_timeout超過` | 6秒アイドルトランザクションでエラー確認 | スキップ推奨 |
| `connect_timeoutの範囲内で接続が確立される` | 接続確立時間が5秒以内であることを確認 | <1s |
| `lock_timeout設定が正しく適用されている` | `SHOW lock_timeout`で設定値確認 | <1s |
| `タイムアウト設定値がPostgreSQLセッションに正しく適用されている` | 全タイムアウト設定値の確認 | <1s |

**注意事項:**
- `idle_in_transaction_session_timeout`テストは実行時間が長いため、デフォルトでスキップされます
- テスト環境では`DB_STATEMENT_TIMEOUT=5000`（5秒）に短縮しています
- 本番環境では`DB_STATEMENT_TIMEOUT=60000`（60秒）を推奨

## 推奨監視項目

### PostgreSQL接続数

```sql
-- 現在の接続数
SELECT count(*) FROM pg_stat_activity;

-- アプリケーション別接続数
SELECT application_name, count(*)
FROM pg_stat_activity
WHERE application_name IS NOT NULL
GROUP BY application_name;

-- 期待値: laravel-next-b2c-api の接続数がPHPプロセス数と一致
```

### statement_timeout超過回数

PostgreSQLログから確認:

```bash
# Dockerコンテナログ確認
docker logs pgsql 2>&1 | grep "statement timeout"

# ログ例:
# ERROR: canceling statement due to statement timeout
```

### アイドルトランザクション数

```sql
-- アイドルトランザクション確認
SELECT pid, state, state_change, query
FROM pg_stat_activity
WHERE state = 'idle in transaction'
AND state_change < now() - interval '30 seconds';

-- 期待値: 0件（長時間アイドルトランザクションがない）
```

### 接続失敗率

Laravelログから確認:

```bash
tail -f storage/logs/laravel.log | grep "PostgreSQL connection failed"
```

### 平均接続時間

アプリケーションログまたはAPM（Application Performance Monitoring）ツールで計測:

- Docker環境: 平均 < 50ms
- ネイティブ環境: 平均 < 100ms
- 本番環境: 平均 < 200ms

## 参考資料

- [PostgreSQL 17 Documentation - libpq Connection Parameters](https://www.postgresql.org/docs/17/libpq-connect.html)
- [PostgreSQL 17 Documentation - Runtime Configuration](https://www.postgresql.org/docs/17/runtime-config-client.html)
- [Laravel 12 Documentation - Database](https://laravel.com/docs/12.x/database)
- [PHP PDO PostgreSQL Driver](https://www.php.net/manual/en/ref.pdo-pgsql.php)
