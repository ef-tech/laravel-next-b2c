# テストトラブルシューティング拡張ガイド

本ガイドでは、Laravel Next.js B2Cプロジェクトにおけるテスト実行時の一般的な問題と解決策を記載します。

## 目次
- [よくある問題と解決策](#よくある問題と解決策)
- [診断スクリプト使用方法](#診断スクリプト使用方法)
- [ログ分析方法](#ログ分析方法)
- [エスカレーション手順](#エスカレーション手順)

---

## よくある問題と解決策

### 1. ポート競合エラー

#### 症状

```
Error: Port 13000 is already in use
Error: listen EADDRINUSE: address already in use :::13001
```

#### 原因

テスト実行に必要なポート（13000, 13001, 13002, 13432, 13379）が他のプロセスによって使用されています。

#### 解決策

**ステップ1: 診断スクリプトで確認**

```bash
make test-diagnose
```

診断結果で使用中のポートとプロセスIDを確認します:

```
[WARN] Port 13000 is in use:
  - PID 12345 (node)
[WARN] Port 13001 is in use:
  - PID 12346 (node)
```

**ステップ2: 全サービス停止**

```bash
# 統合開発サーバー停止
make dev-stop

# またはDocker Compose停止
docker compose down
```

**ステップ3: プロセス強制終了（必要に応じて）**

```bash
# macOS/Linux
lsof -i :13000 | grep LISTEN | awk '{print $2}' | xargs kill -9
lsof -i :13001 | grep LISTEN | awk '{print $2}' | xargs kill -9
lsof -i :13002 | grep LISTEN | awk '{print $2}' | xargs kill -9

# または、個別にプロセスID指定
kill -9 12345
```

**ステップ4: テスト再実行**

```bash
make test-all
```

#### 予防策

- テスト実行前に `make dev-stop` で全サービスを停止
- テスト完了後も `make dev-stop` で確実にプロセスを終了
- 定期的に `make test-diagnose` で環境確認

---

### 2. DB接続エラー

#### 症状

```
SQLSTATE[HY000] [2002] Connection refused
SQLSTATE[08006] [7] could not connect to server: Connection refused
```

#### 原因

- PostgreSQLコンテナが起動していない
- DB接続設定が間違っている
- ネットワーク設定に問題がある

#### 解決策

**ステップ1: Dockerコンテナ状態確認**

```bash
docker compose ps
```

PostgreSQLコンテナが "Up" かつ "healthy" であることを確認:

```
NAME         STATUS          HEALTH
pgsql        Up 10 minutes   healthy
```

**ステップ2: PostgreSQLコンテナ起動（起動していない場合）**

```bash
docker compose up -d pgsql
```

**ステップ3: DB接続確認**

```bash
# テスト用DB存在確認
make test-db-check

# 直接接続確認
docker compose exec pgsql pg_isready -U sail
```

**ステップ4: 環境変数確認**

```bash
# .envファイル確認
cat backend/laravel-api/.env | grep DB_

# 必須環境変数
# DB_CONNECTION=pgsql
# DB_HOST=pgsql
# DB_PORT=13432
# DB_DATABASE=testing
# DB_USERNAME=sail
# DB_PASSWORD=password
```

**ステップ5: テスト環境再セットアップ**

```bash
# クリーンアップ
make test-cleanup

# 再セットアップ
make test-setup

# テスト実行
make test-pgsql
```

#### SQLite環境への切り替え（一時的回避策）

```bash
# SQLite環境に切り替え（高速テスト）
make test-switch-sqlite

# テスト実行
make test-all
```

---

### 3. メモリ不足エラー

#### 症状

```
JavaScript heap out of memory
FATAL ERROR: Ineffective mark-compacts near heap limit Allocation failed
```

#### 原因

- Node.jsヒープメモリ不足
- 並列実行数が多すぎる
- メモリリークの可能性

#### 解決策

**ステップ1: Node.jsヒープメモリ増加**

```bash
# 環境変数設定（8GB）
export NODE_OPTIONS="--max-old-space-size=8192"

# テスト再実行
make test-frontend-only
```

**ステップ2: 並列実行数を減らす**

```bash
# 並列実行数を2に減らす
make test-parallel PARALLEL=2

# または直接スクリプト実行
./scripts/test/main.sh --suite all --parallel 2
```

**ステップ3: テストスイートを分割実行**

```bash
# バックエンドのみ
make test-backend-only

# フロントエンドのみ（後で実行）
make test-frontend-only

# E2Eのみ（後で実行）
make test-e2e-only
```

**ステップ4: システムメモリ確認**

```bash
# macOS
vm_stat

# Linux
free -h

# 診断スクリプト
make test-diagnose
```

#### 長期的対策

- 不要なプロセスを終了
- Docker Desktopのメモリ割り当てを増やす（設定 → Resources → Memory）
- システムRAMのアップグレード検討

---

### 4. 並列実行失敗

#### 症状

```
Error: Shard 3 failed with exit code 1
Error: test database testing_3 does not exist
```

#### 原因

- テスト用DB（testing_1, testing_2, testing_3, testing_4）が存在しない
- 並列実行中のDB競合
- 環境セットアップが不完全

#### 解決策

**ステップ1: テスト環境クリーンアップ**

```bash
make test-cleanup
```

これにより、既存のテスト用DBがすべて削除されます。

**ステップ2: テスト環境再セットアップ**

```bash
make test-setup
```

これにより、testing_1〜testing_4のDBが作成されます。

**ステップ3: DB存在確認**

```bash
make test-db-check
```

**出力例**:
```
✓ Database testing_1 exists
✓ Database testing_2 exists
✓ Database testing_3 exists
✓ Database testing_4 exists
All test databases are ready!
```

**ステップ4: 並列テスト再実行**

```bash
make test-parallel
```

#### Shard単位での実行（デバッグ用）

```bash
# Shard 1のみ実行
cd backend/laravel-api
./vendor/bin/pest --shard=1/4

# Shard 3のみ実行（失敗したShardの特定）
./vendor/bin/pest --shard=3/4
```

---

### 5. E2Eテストタイムアウト

#### 症状

```
Error: page.goto: Timeout 30000ms exceeded
Error: Waiting for service failed after 120 seconds
```

#### 原因

- サービスが起動していない
- サービス起動が遅い
- ネットワーク問題

#### 解決策

**ステップ1: サービス起動確認**

```bash
# Dockerコンテナ確認
docker compose ps

# ヘルスチェック確認
curl http://localhost:13000/api/health
curl http://localhost:13001/api/health
curl http://localhost:13002/api/health
```

**ステップ2: サービス再起動**

```bash
# 全サービス再起動
make dev-stop
make dev

# またはDocker Compose再起動
docker compose restart
```

**ステップ3: ログ確認**

```bash
# Laravel APIログ
docker compose logs -f laravel-api

# User Appログ
docker compose logs -f user-app

# Admin Appログ
docker compose logs -f admin-app
```

**ステップ4: タイムアウト時間延長（一時的）**

```bash
# Playwrightタイムアウト設定（環境変数）
export PLAYWRIGHT_TIMEOUT=60000

# E2Eテスト実行
make test-e2e-only
```

#### 長期的対策

- サービス起動時間の最適化
- ヘルスチェックエンドポイントの実装確認
- ネットワーク設定の見直し

---

### 6. テストファイルが見つからない

#### 症状

```
No tests found
Test suite failed to run
```

#### 原因

- テストファイルの配置場所が間違っている
- テストファイルの命名規則が間違っている
- テスト設定ファイルが正しくない

#### 解決策

**ステップ1: テストファイル配置確認**

```bash
# バックエンドテストファイル
ls backend/laravel-api/tests/

# フロントエンドテストファイル
ls frontend/admin-app/src/**/*.test.tsx
ls frontend/user-app/src/**/*.test.tsx
```

**ステップ2: 命名規則確認**

**バックエンド（Pest）**:
- ファイル名: `*Test.php`
- 配置場所: `backend/laravel-api/tests/Feature/` または `tests/Unit/`

**フロントエンド（Jest）**:
- ファイル名: `*.test.ts`, `*.test.tsx`, `*.spec.ts`, `*.spec.tsx`
- 配置場所: `src/**/__tests__/` または `src/**/`

**E2E（Playwright）**:
- ファイル名: `*.spec.ts`
- 配置場所: `e2e/projects/{admin,user}/tests/`

**ステップ3: テスト設定ファイル確認**

```bash
# Pest設定
cat backend/laravel-api/phpunit.xml

# Jest設定
cat jest.config.js
cat jest.base.js

# Playwright設定
cat e2e/playwright.config.ts
```

---

## 診断スクリプト使用方法

### 基本的な使用方法

```bash
make test-diagnose
```

### 診断項目詳細

#### 1. ポート使用状況診断

**確認内容**:
- 13000（Laravel API）
- 13001（User App）
- 13002（Admin App）
- 13432（PostgreSQL）
- 13379（Redis）

**出力例**:
```
[INFO] Checking port usage for test services...
[DEBUG] Port 13000 is free
[DEBUG] Port 13001 is free
[DEBUG] Port 13002 is free
[WARN] Port 13432 is in use:
  - PID 12345 (postgres)
[DEBUG] Port 13379 is free
[SUCCESS] All required ports are available
```

#### 2. 環境変数診断

**確認内容**:
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

**出力例**:
```
[INFO] Checking required environment variables...
[DEBUG] Environment variable DB_DATABASE is set
[ERROR] Environment variable DB_USERNAME is not set
[ERROR] Environment variable DB_PASSWORD is not set
[ERROR] Some environment variables are missing. Check .env file.
```

**解決策**:
```bash
# .envファイル確認
cat backend/laravel-api/.env

# .env.exampleからコピー
cp backend/laravel-api/.env.example backend/laravel-api/.env

# 必要な環境変数を設定
```

#### 3. Dockerコンテナ診断

**確認内容**:
- docker psコマンド実行
- 実行中のコンテナ一覧表示

**出力例**:
```
[INFO] Checking Docker container status...
[SUCCESS] Docker containers are running:
NAMES                STATUS              PORTS
laravel-api          Up 2 hours          0.0.0.0:13000->13000/tcp
admin-app            Up 2 hours          0.0.0.0:13002->13002/tcp
user-app             Up 2 hours          0.0.0.0:13001->13001/tcp
pgsql                Up 2 hours          0.0.0.0:13432->5432/tcp
redis                Up 2 hours          0.0.0.0:13379->6379/tcp
```

#### 4. データベース接続診断

**確認内容**:
- PostgreSQLコンテナ起動状態
- pg_isready接続確認

**出力例**:
```
[INFO] Checking database connection...
[SUCCESS] PostgreSQL is accepting connections
```

**失敗時の出力**:
```
[INFO] Checking database connection...
[WARN] PostgreSQL container is not running
```

#### 5. システムリソース診断

**確認内容**:
- ディスク空き容量
- メモリ使用状況

**出力例（macOS）**:
```
[INFO] Checking disk space...
[SUCCESS] Available disk space: 38Gi
[INFO] Checking memory usage...
[SUCCESS] Total memory: 16 GB, Free: ~8 GB
```

**出力例（Linux）**:
```
[INFO] Checking disk space...
[SUCCESS] Available disk space: 45G
[INFO] Checking memory usage...
[SUCCESS] Memory: Total: 16G, Free: 10G
```

### 診断結果サマリー

**全診断成功時**:
```
[INFO] =========================================
[INFO]    Diagnostic Summary
[INFO] =========================================
[SUCCESS] Passed: 6 checks
[SUCCESS] All diagnostics passed! Environment is ready for testing.
```

**一部診断失敗時**:
```
[INFO] =========================================
[INFO]    Diagnostic Summary
[INFO] =========================================
[SUCCESS] Passed: 4 checks
[ERROR] Failed: 2 checks
[WARN] Some diagnostics failed. Please review the output above.
[INFO] Run 'make setup' to initialize the environment
[INFO] Run 'make dev' to start development services
```

---

## ログ分析方法

### ログファイル構造

テスト実行後、以下のログファイルが生成されます:

```
test-results/logs/
├── backend.log          # バックエンドテスト実行ログ
├── frontend-admin.log   # Admin Appテスト実行ログ
├── frontend-user.log    # User Appテスト実行ログ
└── e2e.log              # E2Eテスト実行ログ
```

### ログファイル確認方法

#### 全ログ確認

```bash
# 最新のログ表示
tail -n 100 test-results/logs/backend.log

# リアルタイムログ監視
tail -f test-results/logs/backend.log
```

#### エラーのみ抽出

```bash
# ERRORを含む行のみ表示
grep "ERROR" test-results/logs/backend.log

# FAILEDを含む行のみ表示
grep "FAILED" test-results/logs/frontend-admin.log

# 複数パターン検索
grep -E "(ERROR|FAILED|Exception)" test-results/logs/e2e.log
```

### エラーメッセージの解読方法

#### バックエンドエラー（Pest/PHPUnit）

**エラー例1: アサーションエラー**
```
FAILED  Tests\Feature\Auth\LoginTest > ログインが成功すること
Expected status code 200 but got 500.
Failed asserting that 500 matches expected 200.
```

**解読**:
- テスト: `LoginTest`の「ログインが成功すること」テスト
- 期待: ステータスコード200
- 実際: ステータスコード500（サーバーエラー）

**解決策**:
1. Laravel APIログ確認: `storage/logs/laravel.log`
2. 例外スタックトレース確認
3. デバッグ実行: `./vendor/bin/pest --filter LoginTest`

**エラー例2: DB接続エラー**
```
SQLSTATE[HY000] [2002] Connection refused
```

**解読**:
- PostgreSQL接続失敗

**解決策**:
1. `docker compose ps` でDB起動確認
2. `make test-db-check` でDB存在確認
3. `.env`ファイルのDB設定確認

#### フロントエンドエラー（Jest）

**エラー例1: コンポーネントレンダリングエラー**
```
FAIL  src/components/Button/Button.test.tsx
  ● Button Component › renders with correct text
    Unable to find an element with the text: Click me
```

**解読**:
- テスト: `Button.test.tsx`の「renders with correct text」テスト
- 期待: "Click me"テキストを持つ要素
- 実際: 要素が見つからない

**解決策**:
1. コンポーネント実装確認: `Button.tsx`
2. テストコード確認: `Button.test.tsx`
3. デバッグ実行: `npm test -- Button.test.tsx`

**エラー例2: モックエラー**
```
TypeError: Cannot read property 'get' of undefined
```

**解読**:
- モックが正しく設定されていない

**解決策**:
1. `jest.setup.ts`のモック設定確認
2. `test-utils/`の共通ユーティリティ確認
3. MSW（Mock Service Worker）設定確認

#### E2Eエラー（Playwright）

**エラー例1: セレクタエラー**
```
Error: page.locator: Timeout 30000ms exceeded.
Selector: button >> text=Login
```

**解読**:
- 要素: "Login"テキストを持つbutton要素
- 問題: 30秒待機したが見つからない

**解決策**:
1. ページHTML確認: ブラウザで手動アクセス
2. セレクタ修正: より具体的なセレクタに変更
3. 待機時間延長: `page.waitForSelector()`追加

**エラー例2: 認証エラー**
```
Error: Request failed with status code 401
```

**解読**:
- HTTPステータス401（未認証）

**解決策**:
1. 認証トークン確認: `e2e/storage/`の認証ファイル
2. global-setup実行確認: `e2e/fixtures/global-setup.ts`
3. APIヘルスチェック確認: Laravel API起動状態

### ログレベル別フィルタリング

```bash
# DEBUGログ表示
grep "\[DEBUG\]" test-results/logs/backend.log

# INFOログ表示
grep "\[INFO\]" test-results/logs/backend.log

# WARNログ表示
grep "\[WARN\]" test-results/logs/backend.log

# ERRORログ表示
grep "\[ERROR\]" test-results/logs/backend.log

# SUCCESSログ表示
grep "\[SUCCESS\]" test-results/logs/backend.log
```

---

## エスカレーション手順

### レベル1: セルフトラブルシューティング

1. **診断スクリプト実行**:
   ```bash
   make test-diagnose
   ```

2. **ログ確認**:
   ```bash
   tail -n 100 test-results/logs/*.log
   ```

3. **ドキュメント確認**:
   - [TESTING_EXECUTION_GUIDE.md](./TESTING_EXECUTION_GUIDE.md)
   - 本ドキュメント

4. **環境再セットアップ**:
   ```bash
   make test-cleanup
   make test-setup
   make test-all
   ```

### レベル2: チームサポート

1. **エラー情報収集**:
   ```bash
   # 診断結果保存
   make test-diagnose > diagnostic-results.txt 2>&1

   # ログ収集
   tar -czf test-logs.tar.gz test-results/logs/
   ```

2. **再現手順記録**:
   - 実行したコマンド
   - エラーメッセージ
   - 環境情報（OS、Docker version、Node.js version等）

3. **チケット作成**:
   - GitHubイシュー作成
   - Slackチャンネルに投稿

### レベル3: 開発チームエスカレーション

1. **詳細情報提供**:
   - 診断結果ファイル（diagnostic-results.txt）
   - ログファイル（test-logs.tar.gz）
   - 環境情報
   - 再現手順

2. **問題の分類**:
   - インフラ問題（Docker、DB、ネットワーク）
   - コード問題（テストコード、実装コード）
   - 設定問題（環境変数、設定ファイル）

3. **緊急度の設定**:
   - 緊急: 本番環境影響、全テスト実行不可
   - 高: CI/CD失敗、特定スイート実行不可
   - 中: 一部テスト失敗、回避策あり
   - 低: 軽微な警告、パフォーマンス問題

---

## 参考リンク

- [テスト実行ガイド](./TESTING_EXECUTION_GUIDE.md)
- [テストデータベース運用ワークフロー](./TESTING_DATABASE_WORKFLOW.md)
- [フロントエンドテストガイド](../frontend/TESTING_GUIDE.md)
- [E2Eテストガイド](../e2e/README.md)
