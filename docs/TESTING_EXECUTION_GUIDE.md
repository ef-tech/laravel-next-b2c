# テスト実行ガイド

本ガイドでは、Laravel Next.js B2Cプロジェクトにおけるテスト実行の方法を説明します。

## 目次
- [クイックスタート](#クイックスタート)
- [ローカルテスト実行](#ローカルテスト実行)
- [CI/CD環境でのテスト実行](#cicd環境でのテスト実行)
- [テスト環境診断](#テスト環境診断)
- [トラブルシューティング](#トラブルシューティング)

---

## クイックスタート

### 最も基本的なテスト実行（推奨）

開発中の日常的なテスト実行:

```bash
# 全テストスイート実行（SQLite高速モード、約30秒）
make test-all

# または、バックエンドテストのみ（約5秒）
make quick-test

# または、フロントエンドテストのみ（約15秒）
make test-frontend-only
```

### PR作成前の推奨テスト実行

Pull Request作成前の完全テスト:

```bash
# Lint + PostgreSQL + カバレッジレポート生成（約3-5分）
make test-pr
```

### テスト環境診断

テストが失敗する場合、まず環境を診断:

```bash
# ポート・環境変数・Docker・DB・ディスク・メモリ確認
make test-diagnose
```

### よく使うコマンドパターン

```bash
# 1. 日常開発：高速テスト（SQLite、2秒）
make quick-test

# 2. 機能完成時：本番同等環境テスト（PostgreSQL、5-10秒）
make test-pgsql

# 3. PR前：完全テスト（Lint + PostgreSQL + カバレッジ、3-5分）
make test-pr

# 4. テスト環境問題時：診断スクリプト実行
make test-diagnose
```

---

## ローカルテスト実行

### テストスイート別実行方法

#### 1. 全テストスイート実行

**SQLite環境（高速モード）**:
```bash
make test-all
```

- **実行内容**: バックエンド（Pest）+ フロントエンド（Jest）+ E2E（Playwright）
- **所要時間**: 約30秒
- **DB環境**: SQLite（インメモリ）
- **用途**: 日常開発での迅速なフィードバック

**PostgreSQL環境（本番同等モード）**:
```bash
make test-all-pgsql
```

- **実行内容**: 全テストスイート並列実行
- **所要時間**: 約5-10分
- **DB環境**: PostgreSQL（本番同等）
- **並列実行**: 4 Shard
- **用途**: 本番環境同等の検証、PR前チェック

#### 2. バックエンドテストのみ実行

**高速テスト（SQLite）**:
```bash
make quick-test
# または
make test-backend-only
```

- **所要時間**: 約2秒
- **テストフレームワーク**: Pest 4
- **DB環境**: SQLite（インメモリ）

**本番同等テスト（PostgreSQL）**:
```bash
make test-pgsql
```

- **所要時間**: 約5-10秒
- **DB環境**: PostgreSQL
- **用途**: 本番環境の動作確認

**並列テスト実行**:
```bash
make test-parallel
```

- **所要時間**: 約3-5分
- **並列実行**: 4 Shard
- **自動処理**: セットアップ → 実行 → クリーンアップ

#### 3. フロントエンドテストのみ実行

```bash
make test-frontend-only
```

- **所要時間**: 約15秒
- **テストフレームワーク**: Jest 29 + Testing Library 16
- **対象アプリ**: Admin App + User App（並列実行）
- **カバレッジ**: 94.73%達成

#### 4. E2Eテストのみ実行

```bash
make test-e2e-only
```

- **所要時間**: 約2-5分
- **テストフレームワーク**: Playwright
- **前提条件**: 全サービス起動済み（`make dev`）
- **対象**: Admin App + User App

### DB環境選択方法

#### SQLite環境に切り替え

```bash
make test-switch-sqlite
```

- **用途**: 高速開発・デバッグ
- **特徴**: インメモリDB、超高速（2秒以内）
- **制約**: PostgreSQL固有機能は検証不可

#### PostgreSQL環境に切り替え

```bash
make test-switch-pgsql
```

- **用途**: 本番環境同等の検証
- **特徴**: 本番DBと同等の動作確認
- **並列テスト**: 4 Shardサポート

#### 環境確認

```bash
# 現在のテストDB環境を確認
cat backend/laravel-api/phpunit.xml | grep DB_CONNECTION

# テスト用DB存在確認
make test-db-check
```

### カバレッジレポート確認方法

#### バックエンドカバレッジ生成

```bash
make test-coverage
```

- **出力先**: `backend/laravel-api/coverage-report/`
- **レポート形式**: HTML
- **確認方法**: ブラウザで `coverage-report/index.html` を開く

#### フロントエンドカバレッジ生成

```bash
# ルートディレクトリで実行
npm run test:coverage

# または個別アプリで実行
cd frontend/admin-app && npm run test:coverage
cd frontend/user-app && npm run test:coverage
```

- **出力先**: `frontend/{admin-app,user-app}/coverage/`
- **レポート形式**: HTML + JSON + LCOV
- **確認方法**: ブラウザで `coverage/index.html` を開く

#### 統合カバレッジレポート生成

```bash
make test-with-coverage
```

- **実行内容**: PostgreSQL環境で全テスト + カバレッジ生成
- **所要時間**: 約5-10分
- **出力先**:
  - バックエンド: `backend/laravel-api/coverage-report/`
  - フロントエンド: `frontend/{admin-app,user-app}/coverage/`

### テスト結果レポート確認

テスト実行後、以下のディレクトリに結果が保存されます:

```
test-results/
├── junit/                  # JUnit XMLレポート
│   ├── backend-test-results.xml
│   ├── frontend-admin-results.xml
│   └── frontend-user-results.xml
├── coverage/               # カバレッジレポート
│   ├── backend/
│   ├── frontend-admin/
│   └── frontend-user/
├── reports/                # 統合レポート
│   └── test-summary.json
└── logs/                   # テスト実行ログ
    ├── backend.log
    ├── frontend-admin.log
    ├── frontend-user.log
    └── e2e.log
```

#### 統合サマリーJSON確認

```bash
cat test-results/reports/test-summary.json
```

**出力例**:
```json
{
  "timestamp": "2025-10-24T20:01:44Z",
  "duration_seconds": 120,
  "total_tests": 245,
  "passed": 240,
  "failed": 5,
  "suites": {
    "backend": {"tests": 135, "passed": 133, "failed": 2},
    "frontend-admin": {"tests": 57, "passed": 55, "failed": 2},
    "frontend-user": {"tests": 53, "passed": 52, "failed": 1}
  }
}
```

---

## CI/CD環境でのテスト実行

### GitHub Actionsワークフロー

プロジェクトには以下のGitHub Actionsワークフローが設定されています:

#### 1. PHP品質チェック (`.github/workflows/php-quality.yml`)

- **担当領域**: `backend/laravel-api/**`
- **自動実行**: Pull Request時（バックエンド変更時のみ）
- **チェック内容**: Laravel Pint + Larastan Level 8

#### 2. PHPテスト (`.github/workflows/test.yml`)

- **担当領域**: `backend/laravel-api/**`
- **自動実行**: Pull Request時（バックエンド変更時のみ）
- **テスト内容**: Pest 4テストスイート実行（4 Shard並列）
- **DB環境**: PostgreSQL

#### 3. フロントエンドテスト (`.github/workflows/frontend-test.yml`)

- **担当領域**: `frontend/**`, `test-utils/**` + API契約監視
- **自動実行**: フロントエンド変更時 または API契約変更時
- **テスト内容**: Jest 29 + Testing Library 16

#### 4. E2Eテスト (`.github/workflows/e2e-tests.yml`)

- **担当領域**: `frontend/**`, `backend/**`, `e2e/**`
- **自動実行**: Pull Request時、mainブランチpush時、手動実行
- **実行方式**: 4 Shard並列実行（約2分完了）
- **レポート**: Playwright HTML/JUnitレポート

### Artifactsダウンロード方法

GitHub Actions実行後、テスト結果をダウンロードできます:

1. **GitHub Actions画面にアクセス**:
   - リポジトリページ → "Actions" タブ

2. **該当するワークフロー実行を選択**:
   - 実行したいワークフローをクリック

3. **Artifactsセクションを確認**:
   - ページ下部の "Artifacts" セクション

4. **ダウンロード**:
   - `test-results-shard-1` - Shard 1のテスト結果
   - `test-results-shard-2` - Shard 2のテスト結果
   - `test-results-shard-3` - Shard 3のテスト結果
   - `test-results-shard-4` - Shard 4のテスト結果
   - `coverage-report` - カバレッジレポート
   - `playwright-report` - E2Eテストレポート

5. **ダウンロードしたzipファイルを解凍**:
   ```bash
   unzip test-results-shard-1.zip
   cd test-results/
   # レポート確認
   ```

### GitHub Actions Summary確認

GitHub Actionsの実行後、統合サマリーが自動的に表示されます:

1. **GitHub Actions画面にアクセス**
2. **該当するワークフロー実行を選択**
3. **Summary画面を確認**:
   - テーブル形式の結果表示
   - 各スイートの成功/失敗数
   - 実行時間

**Summary表示例**:
```markdown
## Test Summary

| Suite | Tests | Passed | Failed | Duration |
|-------|-------|--------|--------|----------|
| Backend | 135 | 133 | 2 | 45s |
| Frontend Admin | 57 | 55 | 2 | 20s |
| Frontend User | 53 | 52 | 1 | 18s |
| **Total** | **245** | **240** | **5** | **83s** |
```

---

## テスト環境診断

テスト実行に問題がある場合、まず環境診断を実行してください。

### 診断スクリプト実行

```bash
make test-diagnose
```

### 診断内容

1. **ポート使用状況確認**:
   - 5ポート（13000, 13001, 13002, 13432, 13379）の使用状況
   - 使用中の場合、プロセスID（PID）と名前を表示

2. **環境変数確認**:
   - 必須環境変数（DB_DATABASE, DB_USERNAME, DB_PASSWORD）の設定状態
   - 未設定の場合、エラーメッセージを表示

3. **Dockerコンテナ確認**:
   - docker psコマンド実行
   - コンテナ一覧とステータス表示

4. **データベース接続確認**:
   - PostgreSQLコンテナの起動状態
   - pg_isready接続確認

5. **システムリソース確認**:
   - ディスク空き容量
   - メモリ使用状況

### 診断結果の読み方

**診断成功例**:
```
[INFO] Starting test environment diagnostics...
[SUCCESS] All required ports are available
[SUCCESS] All required environment variables are set
[SUCCESS] Docker containers are running:
NAMES                   STATUS      PORTS
laravel-api             Up 2 hours  0.0.0.0:13000->13000/tcp
[SUCCESS] PostgreSQL is accepting connections
[SUCCESS] Available disk space: 38Gi
[SUCCESS] Total memory: 16 GB, Free: ~8 GB
[INFO] =========================================
[INFO]    Diagnostic Summary
[INFO] =========================================
[SUCCESS] Passed: 6 checks
[SUCCESS] All diagnostics passed! Environment is ready for testing.
```

**診断失敗例**:
```
[WARN] Port 13000 is in use:
  - PID 12345 (node)
[ERROR] Environment variable DB_USERNAME is not set
[ERROR] Some environment variables are missing. Check .env file.
[WARN] No Docker containers are running
[INFO] Run 'make dev' to start development services
[ERROR] Failed: 2 checks
[WARN] Some diagnostics failed. Please review the output above.
[INFO] Run 'make setup' to initialize the environment
[INFO] Run 'make dev' to start development services
```

---

## トラブルシューティング

テスト実行時の一般的な問題と解決策については、[TESTING_TROUBLESHOOTING_EXTENDED.md](./TESTING_TROUBLESHOOTING_EXTENDED.md)を参照してください。

### クイックトラブルシューティング

#### ポート競合エラー

**症状**:
```
Error: Port 13000 is already in use
```

**解決策**:
```bash
# 1. 診断スクリプトで使用プロセスを確認
make test-diagnose

# 2. 全サービス停止
make dev-stop

# 3. 再度テスト実行
make test-all
```

#### DB接続エラー

**症状**:
```
SQLSTATE[HY000] [2002] Connection refused
```

**解決策**:
```bash
# 1. Dockerコンテナ起動確認
docker compose ps

# 2. PostgreSQLコンテナが起動していない場合
docker compose up -d pgsql

# 3. 接続確認
make test-db-check

# 4. テスト実行
make test-pgsql
```

#### メモリ不足エラー

**症状**:
```
JavaScript heap out of memory
```

**解決策**:
```bash
# Node.jsヒープメモリ増加（8GB）
export NODE_OPTIONS="--max-old-space-size=8192"

# または、並列実行数を減らす
make test-parallel PARALLEL=2
```

#### 並列実行失敗

**症状**:
```
Error: Shard 3 failed
```

**解決策**:
```bash
# 1. テストDB環境クリーンアップ
make test-cleanup

# 2. テストDB環境再セットアップ
make test-setup

# 3. 並列テスト再実行
make test-parallel
```

---

## 参考リンク

- [テストデータベース運用ワークフロー](./TESTING_DATABASE_WORKFLOW.md)
- [トラブルシューティング拡張ガイド](./TESTING_TROUBLESHOOTING_EXTENDED.md)
- [フロントエンドテストガイド](../frontend/TESTING_GUIDE.md)
- [E2Eテストガイド](../e2e/README.md)
