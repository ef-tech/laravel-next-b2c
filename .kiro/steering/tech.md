# Technology Stack

## アーキテクチャ
- **API専用最適化Laravel**: 必要最小限4パッケージ構成による超高速起動
- **🏗️ DDD/クリーンアーキテクチャ (4層構造)**:
  - **Domain層** (`ddd/Domain/`): Entities、ValueObjects、Repository Interfaces、Domain Events、Domain Services
  - **Application層** (`ddd/Application/`): UseCases、DTOs、Service Interfaces、Queries、Application Exceptions
  - **Infrastructure層** (`ddd/Infrastructure/`): Repository実装（Eloquent）、External Services、Framework固有コード
  - **HTTP層** (`app/Http/`): Controllers、Requests、Resources
  - **依存方向**: HTTP → Application → Domain ← Infrastructure（依存性逆転）
- **ステートレス設計**: `SESSION_DRIVER=array`でセッション除去、水平スケーリング対応
- **マイクロフロントエンド型構成**: 管理者用とユーザー用アプリケーションの完全分離
- **トークンベース認証**: Laravel Sanctum 4.0によるセキュアなステートレス認証
- **Docker化インフラ**: Laravel Sailによるコンテナベース開発環境
- **フルスタックTypeScript**: フロントエンドからバックエンドまでの型安全性

### 🚀 Laravel API最適化成果
**パフォーマンス改善メトリクス**:
- 起動速度: **33.3%向上** (33.3ms達成)
- メモリ効率: **0.33KB/request** (画期的改善)
- 依存関係: **96.5%削減** (114→4パッケージ)
- レスポンス: **11.8ms** (<20ms目標達成)

## フロントエンド技術
### フレームワーク・ライブラリ
- **Next.js**: 15.5.4 (React Server Components、App Router対応)
- **React**: 19.1.0 (最新のConcurrent Features)
- **TypeScript**: ^5 (厳密な型チェック)
- **Tailwind CSS**: ^4.0.0 (最新版CSS framework)

### ビルド・開発ツール
- **Turbopack**: Next.js標準バンドラー (`--turbopack`フラグ)
- **ESLint**: ^9 (コード品質管理、モノレポ統一設定)
- **Prettier**: ^3 (コードフォーマッター、Tailwind CSS統合)
- **PostCSS**: Tailwind CSS統合用

### コード品質管理 (モノレポ統一設定)
- **共通設定**: ルート`package.json`でワークスペース全体を管理
- **husky**: ^9.1.7 (Gitフック管理、`.husky/`直下にフック直接配置する推奨方法に移行済み)
- **lint-staged**: ^15 (ステージされたファイルのみlint/format実行)
- **設定ファイル**: 各アプリに`eslint.config.mjs`（ESLint 9対応flat config）
- **テストコード品質管理**:
  - **eslint-plugin-jest**: ^28.14.0 (Jest専用ESLintルール、flat/recommended適用)
  - **eslint-plugin-testing-library**: ^6.5.0 (Testing Library専用ルール、flat/react適用)
  - **eslint-plugin-jest-dom**: ^5.5.0 (Jest-DOM専用ルール、flat/recommended適用)
  - **共通Base設定**: `frontend/.eslint.base.mjs` - テストファイル専用オーバーライド設定
  - **適用ルールレベル**: errorレベル（CI/CD厳格チェック対応）

### デュアルアプリケーション構成
- **Admin App** (`frontend/admin-app/`): 管理者向けダッシュボード
- **User App** (`frontend/user-app/`): エンドユーザー向けアプリケーション

### テスト環境
- **Jest**: ^29.7.0 (テストランナー、モノレポ対応)
- **React Testing Library**: ^16.3.0 (React 19対応)
- **@testing-library/jest-dom**: ^6.9.1 (DOM matcher拡張)
- **jest-environment-jsdom**: ^29.7.0 (DOM環境シミュレーション)
- **MSW**: ^2.11.3 (APIモック、global.fetch対応)
- **next-router-mock**: ^0.9.13 (Next.js Router モック)
- **テスト構成**: モノレポ共通設定（jest.base.js）+ プロジェクト統括設定（jest.config.js）

### E2Eテスト環境
- **Playwright**: ^1.47.2 (E2Eテストフレームワーク、クロスブラウザ対応)
- **テストプロジェクト構成**: Admin App / User App 分離実行
- **認証統合**: Laravel Sanctum認証対応（global-setup実装済み）
- **Page Object Model**: 保守性の高いテスト設計パターン採用
- **並列実行**: Shard機能によるCI/CD最適化（4並列デフォルト）
- **環境変数管理**: `.env`ファイルによる柔軟なURL/認証情報設定
- **CI/CD統合**: GitHub Actions自動実行（Pull Request時、約2分完了）

## バックエンド技術 - 🏆 API専用最適化済み
### 言語・フレームワーク
- **PHP**: ^8.4 (最新のPHP機能対応)
- **Laravel**: ^12.0 (**API専用最適化済み** - Web機能削除)
- **Composer**: パッケージ管理

### 💾 最小依存関係構成 (4コアパッケージ)
- **Laravel**: ^12.0 (フレームワークコア)
- **Laravel Sanctum**: ^4.0 (トークン認証)
- **Laravel Tinker**: ^2.10 (REPL環境)
- **Laravel Pint**: ^1.24 (コードフォーマッター)

### ステートレスAPI設計詳細
- **セッション除去**: `SESSION_DRIVER=array`でステートレス化
- **Web機能削除**: `routes/web.php`簡略化、View関連機能除去
- **APIルート専用**: `routes/api.php`に集約、RESTful設計
- **CORS最適化**: Next.jsフロントエンドとの完全統合

### 🔐 Laravel Sanctum認証システム詳細
**認証エンドポイント** (`routes/api.php`):
- **POST `/api/login`**: メール・パスワードによるログイン、Personal Access Token発行
- **POST `/api/logout`**: トークン無効化、ログアウト処理
- **GET `/api/me`**: 認証ユーザー情報取得（`auth:sanctum` middleware保護）
- **GET `/api/tokens`**: 発行済みトークン一覧取得
- **POST `/api/tokens/{id}/revoke`**: 特定トークン無効化
- **POST `/api/tokens/refresh`**: トークン更新（新規トークン発行）

**📊 ヘルスチェックエンドポイント** (`routes/api.php`):
- **GET `/api/health`**: APIサーバー稼働状態確認（ルート名: `health`）
  - **レスポンス**: `{ "status": "ok", "timestamp": "2025-10-12T..." }` (JSON形式)
  - **用途**: Dockerヘルスチェック統合、ロードバランサー監視、サービス死活監視
  - **動的ポート対応**: `APP_PORT`環境変数による柔軟なポート設定
  - **認証不要**: パブリックエンドポイント（middleware: なし）

**トークン管理機能**:
- **Personal Access Tokens**: UUIDベーストークン（`personal_access_tokens`テーブル）
- **有効期限管理**: `SANCTUM_EXPIRATION` 環境変数で設定可能（デフォルト: 60日）
- **自動期限切れ削除**: `tokens:prune` コマンドをScheduler統合（毎日実行）
- **Token Abilities**: 権限管理機能（`*` = 全権限）
- **Last Used At**: トークン最終使用日時記録

**セキュリティ設定**:
- **Middleware**: `auth:sanctum` による認証保護
- **CSRF保護**: SPA用CSRF設定（`config/sanctum.php`）
- **Stateful Domains**: `localhost:13001`, `localhost:13002`（開発環境）
- **レート制限**: API保護設定
- **PHPStan Level 8準拠**: 型安全性保証、静的解析合格

**Scheduled Tasks統合**:
```php
// app/Console/Kernel.php
$schedule->command('tokens:prune')->daily();
```

**環境変数**:
```env
SANCTUM_STATEFUL_DOMAINS=localhost:13001,localhost:13002
SESSION_DRIVER=array  # ステートレス設計
SANCTUM_EXPIRATION=60 # トークン有効期限（日数）

# 🌐 CORS環境変数設定
CORS_ALLOWED_ORIGINS=http://localhost:13001,http://localhost:13002  # 開発環境
# 本番環境例: CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com

# 環境変数バリデーションスキップ（緊急時のみ、migrate/seed実行時に使用可能）
# ENV_VALIDATION_SKIP=true
```

### データベース・ストレージ
- **PostgreSQL**: 17-alpine (主データベース - ステートレス設計対応)
  - **接続最適化**: タイムアウト設定（connect_timeout/statement_timeout）、PDOオプション最適化
  - **環境別設定**: Docker/Native/Production環境に応じた接続パラメータ最適化
  - **信頼性向上**: ServiceProvider方式によるタイムアウト設定、エラーハンドリング強化
- **Redis**: alpine (キャッシュ管理 - セッションストレージ不使用)
- **MinIO**: オブジェクトストレージ (S3互換)

**最適化ポイント**:
- セッションストレージをRedisから除去、キャッシュのみ使用
- ステートレス設計によりDBコネクション最適化
- PostgreSQL接続タイムアウト設定（デフォルト: 接続5秒、ステートメント30秒）
- PDOオプション最適化（エミュレーション無効、エラーモード例外設定）

### 開発・テストツール
- **Laravel Pint**: ^1.24 (コードフォーマッター - コアパッケージ)
- **Larastan (PHPStan)**: ^3.0 (静的解析ツール - Level 8厳格チェック)
- **Pest**: ^3.12 (モダンテストフレームワーク - PHPUnitから完全移行、Architecture Testing統合)
  - **Architecture Tests**: `tests/Arch/` - 依存方向検証、レイヤー分離チェック、命名規約検証
  - **テストカバレッジ**: 96.1%達成（Domain層100%、Application層98%、Infrastructure層94%）
  - **テストDB環境**: SQLite（高速開発）/PostgreSQL（本番同等）の柔軟な切り替え、並列テスト実行対応
- **Laravel Sail**: ^1.41 (Docker開発環境 - カスタムポート対応)
- **Laravel Tinker**: ^2.10.1 (REPL環境 - コアパッケージ)
- **Faker**: ^1.23 (テストデータ生成)

### PHP品質管理システム
**統合コード品質ツール**: Laravel Pint (フォーマット) + Larastan (静的解析) + Git Hooks + CI/CD

#### Laravel Pint設定 (`pint.json`)
```json
{
  "preset": "laravel",
  "rules": {
    "simplified_null_return": true,
    "no_unused_imports": true
  }
}
```

#### Larastan設定 (`phpstan.neon`)
```neon
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    level: 8
    paths:
        - app
        - config
        - database
        - routes
        - tests
```

#### Git Hooks自動化 (.husky/)
- **設定場所**: ルート`.husky/`ディレクトリ（Husky v9推奨方法: 直接フック配置）
- **Pre-commit** (`.husky/pre-commit`): lint-staged実行 (変更PHPファイルのみPint自動フォーマット、変更TSXファイルはESLint + Prettier)
- **Pre-push** (`.husky/pre-push`): `composer quality`実行 (Pint + Larastan全体チェック)
- **非推奨警告解消済み**: `.husky/_/`内の自動生成フックから`.husky/`直下の推奨方法に完全移行

#### CI/CD統合 (GitHub Actions v4) - 発火タイミング最適化済み

**共通最適化機能**:
- **Concurrency設定**: PR内の連続コミットで古い実行を自動キャンセル（リソース効率化）
- **Paths Filter**: 関連ファイル変更時のみワークフロー実行（実行頻度60-70%削減）
- **Pull Request Types明示**: 必要なイベントのみ実行（opened, synchronize, reopened, ready_for_review）
- **キャッシング統一化**: Node.js（setup-node内蔵）、Composer（cache-files-dir）でヒット率80%以上

**ワークフロー一覧**:

1. **PHP品質チェック** (`.github/workflows/php-quality.yml`)
   - **担当領域**: `backend/laravel-api/**`
   - **自動実行**: Pull Request時（バックエンド変更時のみ）
   - **チェック内容**: Pint検証 + Larastan Level 8静的解析
   - **Concurrency**: `${{ github.workflow }}-${{ github.event_name }}-${{ github.ref }}`

2. **PHPテスト** (`.github/workflows/test.yml`)
   - **担当領域**: `backend/laravel-api/**`
   - **自動実行**: Pull Request時（バックエンド変更時のみ）
   - **テスト内容**: Pest 4テストスイート実行
   - **キャッシング**: Composer cache-files-dir方式（最適化済み）

3. **フロントエンドテスト** (`.github/workflows/frontend-test.yml`)
   - **担当領域**: `frontend/**`, `test-utils/**` + **API契約監視**
   - **API契約監視パス**:
     - `backend/laravel-api/app/Http/Controllers/Api/**`
     - `backend/laravel-api/app/Http/Resources/**`
     - `backend/laravel-api/routes/api.php`
   - **自動実行**: フロントエンド変更時 **または** API契約変更時
   - **テスト内容**: Jest 29 + Testing Library 16（カバレッジ94.73%）
   - **API契約整合性検証**: APIレスポンス形式変更を早期検出

4. **E2Eテスト** (`.github/workflows/e2e-tests.yml`)
   - **担当領域**: `frontend/**`, `backend/**`, `e2e/**`
   - **自動実行**: Pull Request時、mainブランチpush時、手動実行
   - **実行方式**: 4 Shard並列実行（Matrix戦略）、約2分完了
   - **タイムアウト**: 20分（最適化済み、旧60分）
   - **パフォーマンス最適化**:
     - Composerキャッシング（`actions/cache@v4`）
     - Concurrency設定（PR重複実行キャンセル）
     - Paths Filter（影響範囲のみ実行）
   - **実行環境**: Docker開発モード起動（ビルド不要、高速化）
   - **レポート**: Playwright HTML/JUnitレポート、失敗時のスクリーンショット・トレース
   - **Artifacts**: 各Shardごとのテストレポート保存

**ワークフロー実行条件マトリクス**:
| 変更内容 | frontend-test | php-quality | test | e2e-tests |
|---------|--------------|-------------|------|-----------|
| フロントエンドのみ | ✅ | ❌ | ❌ | ✅ |
| バックエンドのみ | ❌ | ✅ | ✅ | ✅ |
| API Controllers変更 | ✅ | ✅ | ✅ | ✅ |
| API Resources変更 | ✅ | ✅ | ✅ | ✅ |
| E2Eテストのみ | ❌ | ❌ | ❌ | ✅ |
| README更新のみ | ❌ | ❌ | ❌ | ❌ |

### 📝 最適化ドキュメント体系
**`backend/laravel-api/docs/` に包括的ドキュメントを格納**:

**Laravel API最適化ドキュメント**:
- `laravel-optimization-process.md`: 最適化プロセス完了レポート
- `performance-report.md`: パフォーマンス改善定量分析
- `development-setup.md`: API専用開発環境構築手順
- `database-connection.md`: PostgreSQL接続設定ガイド（環境別設定・タイムアウト最適化・トラブルシューティング）
- `migration-guide.md`: 他プロジェクトへの移行ガイド
- `troubleshooting.md`: トラブルシューティング完全ガイド
- `configuration-changes.md`: 全設定変更の詳細記録
- `laravel-pint-larastan-team-guide.md`: Laravel Pint・Larastanチーム運用ドキュメント

**テストDB運用ドキュメント**:
- `docs/TESTING_DATABASE_WORKFLOW.md`: テストDB設定ワークフローガイド（SQLite/PostgreSQL切り替え、並列テスト実行、Makefileタスク運用）

**フロントエンドテストコードESLintドキュメント** (`docs/`):
- `JEST_ESLINT_INTEGRATION_GUIDE.md`: Jest/Testing Library ESLint統合ガイド（設定概要、プラグイン詳細、適用ルール）
- `JEST_ESLINT_QUICKSTART.md`: クイックスタートガイド（5分セットアップ、基本ワークフロー、トラブルシューティング）
- `JEST_ESLINT_TROUBLESHOOTING.md`: トラブルシューティング完全ガイド（設定問題、実行エラー、ルール調整）
- `JEST_ESLINT_CONFIG_EXAMPLES.md`: 設定サンプル集（プロジェクト別設定例、カスタマイズパターン）

**🌐 CORS環境変数設定ドキュメント** (`docs/`):
- `CORS_CONFIGURATION_GUIDE.md`: CORS環境変数設定完全ガイド（環境別設定、セキュリティベストプラクティス、トラブルシューティング、テスト戦略）

**🏗️ DDD/クリーンアーキテクチャドキュメント**:
- `ddd-architecture.md`: DDD 4層構造アーキテクチャ概要、依存方向ルール、主要パターン
- `ddd-development-guide.md`: DDD開発ガイドライン、実装パターン、ベストプラクティス
- `ddd-testing-strategy.md`: DDD層別テスト戦略、Architecture Tests、テストパターン
- `ddd-troubleshooting.md`: DDDトラブルシューティングガイド、よくある問題と解決策

**Dockerトラブルシューティング**:
- `DOCKER_TROUBLESHOOTING.md`: Dockerトラブルシューティング完全ガイド
  - **ポート設定問題**: APP_PORT設定、ポート80で起動する問題の解決方法
  - **イメージ再ビルド**: Dockerfileビルド引数変更時の再ビルド手順
  - **完全クリーンアップ**: コンテナ・イメージ・ボリューム削除手順
  - **プロジェクト固有イメージ命名**: laravel-next-b2c/app による他プロジェクトとの競合回避

## 開発環境
### Docker Compose構成（統合環境）
```yaml
サービス構成:
# バックエンド
- laravel-api: Laravel 12 API (PHP 8.4) - ポート: 13000
  - イメージ名: laravel-next-b2c/app（プロジェクト固有、他プロジェクトとの競合回避）
  - APP_PORTデフォルト値: 13000（Dockerfile最適化済み、ランタイム変更可能）
  - healthcheck: curl http://127.0.0.1:${APP_PORT}/api/health (5秒間隔、動的ポート対応)
    - エンドポイント: GET /api/health → { "status": "ok", "timestamp": "..." }
- pgsql: PostgreSQL 17-alpine - ポート: 13432
  - healthcheck: pg_isready -U sail (5秒間隔)
- redis: Redis alpine - ポート: 13379
  - healthcheck: redis-cli ping (5秒間隔)
- mailpit: 開発用メールサーバー - SMTP: 11025, UI: 13025
  - healthcheck: wget --spider http://127.0.0.1:8025 (10秒間隔)
- minio: オブジェクトストレージ - API: 13900, Console: 13010
  - healthcheck: curl http://127.0.0.1:9000/minio/health/live (10秒間隔)

# フロントエンド
- admin-app: Next.js 15.5 管理者アプリ - ポート: 13002
  - healthcheck: curl http://127.0.0.1:13002/api/health (10秒間隔)
  - depends_on: laravel-api (healthy)
- user-app: Next.js 15.5 ユーザーアプリ - ポート: 13001
  - healthcheck: curl http://127.0.0.1:13001/api/health (10秒間隔)
  - depends_on: laravel-api (healthy)

# テスト環境
- e2e-tests: Playwright E2Eテスト (オンデマンド実行)
  - depends_on: admin-app, user-app, laravel-api (全てhealthy)
```

**Docker Compose統合の利点**:
- 全サービス一括起動（`docker compose up -d`）
- 統一されたネットワーク設定
- サービス間通信の最適化
- 環境変数の一元管理
- E2Eテスト環境の完全統合
- プロジェクト固有Dockerイメージ命名（laravel-next-b2c/app）による他プロジェクトとの競合回避

**ヘルスチェック機能統合**:
- 全サービスのヘルスチェック機能による起動状態監視
- `docker compose ps`でリアルタイム状態確認（healthy/unhealthy表示）
- 依存関係の自動管理（depends_on: service_healthy）による起動順序制御
- IPv4明示対応（localhost→127.0.0.1）によるDNS解決問題の回避
- サービス障害の早期検知と自動再起動対応

### Laravel Sail構成（個別起動）
```yaml
Laravel Sailサービス:
- laravel.test: メインアプリケーション (PHP 8.4)
- redis: キャッシュサーバー
- pgsql: PostgreSQL 17
- mailpit: 開発用メールサーバー
- minio: オブジェクトストレージ
```

### 必要ツール
- **Docker**: コンテナ実行環境
- **Docker Compose**: マルチコンテナ管理
- **Node.js**: フロントエンド開発 (LTS推奨)
- **Git**: バージョン管理

## 共通開発コマンド
### Docker Compose（推奨 - 統合環境）
```bash
# リポジトリルートで実行

# 全サービス起動
docker compose up -d

# サービス状態確認（ヘルスチェック含む）
docker compose ps
# 出力例:
# NAME         STATUS        HEALTH
# laravel-api  Up 2 minutes  healthy
# admin-app    Up 2 minutes  healthy
# user-app     Up 2 minutes  healthy
# pgsql        Up 2 minutes  healthy
# redis        Up 2 minutes  healthy

# ヘルスチェック詳細確認
docker inspect --format='{{json .State.Health}}' <container-name>

# ログ確認
docker compose logs -f

# 特定サービスのログ確認
docker compose logs -f admin-app
docker compose logs -f user-app
docker compose logs -f laravel-api

# サービス再起動
docker compose restart admin-app
docker compose restart user-app

# 全サービス停止
docker compose down

# ボリューム含めて完全削除
docker compose down -v

# Laravel APIコマンド実行
docker compose exec laravel-api php artisan migrate
docker compose exec laravel-api php artisan db:seed

# E2Eテスト実行（全サービス起動後）
docker compose run --rm e2e-tests
```

### バックエンド (Laravel)
```bash
# 開発サーバー起動 (統合)
composer dev

# 個別コマンド
php artisan serve         # APIサーバー
php artisan queue:listen   # キュー処理
php artisan pail          # ログ監視
npm run dev               # Vite開発サーバー

# テスト実行 (Pest 4 + Architecture Tests)
composer test                    # Pest テストスイート実行（96.1%カバレッジ）
./vendor/bin/pest                # Pest 直接実行
./vendor/bin/pest --coverage     # カバレッジレポート生成
./vendor/bin/pest --parallel     # 並列実行
./vendor/bin/pest tests/Arch     # Architecture Testsのみ実行（依存方向検証）

# テストインフラ管理 (Makefile - プロジェクトルートから実行)
make quick-test                  # 高速SQLiteテスト（~2秒）
make test-pgsql                  # PostgreSQLテスト（本番同等、~5-10秒）
make test-parallel               # 並列テスト実行（4 Shard）
make test-coverage               # カバレッジレポート生成
make ci-test                     # CI/CD相当の完全テスト（~20-30秒）
make test-switch-sqlite          # SQLite環境に切り替え
make test-switch-pgsql           # PostgreSQL環境に切り替え
make test-setup                  # 並列テスト環境構築（PostgreSQL test DBs作成）
make test-cleanup                # テスト環境クリーンアップ（test DBs削除）
make test-db-check               # テスト用DB存在確認

# 推奨テストフロー
# 1. 日常開発: make quick-test (SQLite・2秒)
# 2. 機能完成時: make test-pgsql (PostgreSQL・5-10秒)
# 3. PR前: make ci-test (完全テスト・20-30秒)

# コード品質管理 (統合コマンド)
composer quality          # フォーマットチェック + 静的解析
composer quality:fix      # フォーマット自動修正 + 静的解析

# コードフォーマット (Laravel Pint)
composer pint             # 全ファイル自動フォーマット
composer pint:test        # フォーマットチェックのみ（修正なし）
composer pint:dirty       # Git変更ファイルのみフォーマット
vendor/bin/pint           # 直接実行

# 静的解析 (Larastan/PHPStan Level 8)
composer stan             # 静的解析実行
composer stan:baseline    # ベースライン生成（既存エラー記録）
vendor/bin/phpstan analyse  # 直接実行
```

### フロントエンド (Next.js)
```bash
# 各アプリディレクトリで実行
npm run dev    # 開発サーバー (Turbopack有効)
npm run build  # 本番ビルド
npm start      # 本番サーバー
npm run lint   # ESLintチェック

# モノレポルートから実行可能
npm run lint          # 全ワークスペースでlint実行
npm run lint:fix      # 全ワークスペースでlint自動修正
npm run format        # Prettier実行
npm run format:check  # Prettierチェックのみ
npm run type-check    # TypeScriptチェック

# テスト実行 (Jest + Testing Library)
npm test              # 全テスト実行
npm run test:watch    # ウォッチモード
npm run test:coverage # カバレッジレポート生成
npm run test:admin    # Admin Appのみテスト
npm run test:user     # User Appのみテスト
```

### E2Eテスト (Playwright)
```bash
# e2eディレクトリで実行
cd e2e

# セットアップ（初回のみ）
npm install
npx playwright install chromium

# テスト実行
npm test              # 全E2Eテスト実行
npm run test:ui       # UIモードで実行（デバッグ推奨）
npm run test:debug    # デバッグモード
npm run test:admin    # Admin Appテストのみ
npm run test:user     # User Appテストのみ
npm run report        # HTMLレポート表示

# CI/CD環境
npm run test:ci       # CI環境用実行（headless）

# コード生成（Codegen）
npm run codegen:admin # Admin App用テスト自動生成
npm run codegen:user  # User App用テスト自動生成
```

### Laravel Sail環境（個別起動）
```bash
# Laravel APIディレクトリで実行
cd backend/laravel-api

# 環境起動・停止
./vendor/bin/sail up -d
./vendor/bin/sail down

# Laravel Artisanコマンド
./vendor/bin/sail artisan <command>

# Composer操作
./vendor/bin/sail composer <command>
```

## 環境変数設定
### ポート設定 (カスタマイズ済み)

#### バックエンドポート (backend/laravel-api/.env)
```env
APP_PORT=13000                    # Laravel アプリケーション
                                  # - Dockerfileデフォルト値: 13000（最適化済み、旧80から変更）
                                  # - ランタイム変更可能（再ビルド不要）
                                  # - compose.yamlで環境変数として設定
FORWARD_REDIS_PORT=13379          # Redis
FORWARD_DB_PORT=13432             # PostgreSQL
FORWARD_MAILPIT_PORT=11025        # Mailpit SMTP
FORWARD_MAILPIT_DASHBOARD_PORT=13025  # Mailpit UI
FORWARD_MINIO_PORT=13900          # MinIO API
FORWARD_MINIO_CONSOLE_PORT=13010  # MinIO Console
```

#### フロントエンドポート（固定設定）
```env
# User App: http://localhost:13001
# - frontend/user-app/package.json の dev/start スクリプトで --port 13001 指定
# - Dockerfile: EXPOSE 13001
# - docker-compose.yml: ports: "13001:13001"

# Admin App: http://localhost:13002
# - frontend/admin-app/package.json の dev/start スクリプトで --port 13002 指定
# - Dockerfile: EXPOSE 13002
# - docker-compose.yml: ports: "13002:13002"
```

**ポート固定設計の利点**:
- **13000番台統一**: 複数プロジェクト並行開発時のポート競合回避
- **固定ポート**: チーム開発での環境統一、E2Eテスト安定性向上、Docker環境統一
- **デフォルトポート回避**: 他のNext.js/Laravelプロジェクトとの同時実行可能
- **Docker統合**: コンテナポートマッピングの一貫性、環境変数不要
- **E2Eテスト**: テストURLの固定化、環境差異の最小化
- **Dockerfileデフォルト値最適化**: APP_PORT=13000（旧80から変更、ランタイム変更可能）
- **プロジェクト固有イメージ**: laravel-next-b2c/app（他プロジェクトとの競合回避）

### E2Eテスト環境変数 (e2e/.env)
```env
E2E_ADMIN_URL=http://localhost:13002  # Admin App URL (固定ポート)
E2E_USER_URL=http://localhost:13001   # User App URL (固定ポート)
E2E_API_URL=http://localhost:13000    # Laravel API URL

E2E_ADMIN_EMAIL=admin@example.com     # 管理者メールアドレス
E2E_ADMIN_PASSWORD=password           # 管理者パスワード

E2E_USER_EMAIL=user@example.com       # ユーザーメールアドレス
E2E_USER_PASSWORD=password            # ユーザーパスワード
```

### 主要設定
- **Database**: SQLite (開発用デフォルト) / PostgreSQL (Docker環境)
  - **PostgreSQL接続設定** (`.env`):
    - `DB_CONNECTION=pgsql`
    - `DB_HOST=pgsql` (Docker) / `DB_HOST=127.0.0.1` (Native)
    - `DB_PORT=13432` (統一ポート)
    - `DB_CONNECT_TIMEOUT=5` (接続タイムアウト: 5秒)
    - `DB_STATEMENT_TIMEOUT=30000` (ステートメントタイムアウト: 30秒)
    - `DB_SSLMODE=prefer` (開発) / `DB_SSLMODE=verify-full` (本番)
  - **環境別テンプレート**: `backend/laravel-api/.env.docker`, `.env.native`, `.env.production`
- **Cache**: Database (デフォルト) / Redis (Docker環境)
- **Queue**: Database / Redis
- **Mail**: ログ出力 / Mailpit (開発環境)
- **File Storage**: Local / MinIO (オブジェクトストレージ)

## セキュリティ・品質管理
- **トークンベース認証**: Laravel Sanctum 4.0によるセキュアなステートレス認証
- **CSRFプロテクション**: APIエンドポイント専用設定
- **CORS最適化**: Next.jsフロントエンドとの統合設定
- **XDEBUG**: 開発・デバッグサポート
- **環境分離**: .env設定による環境別管理
- **型安全性**: TypeScript全面採用
- **コード品質**: ESLint 9 + Prettier + Laravel Pint統合
- **自動品質チェック**: husky + lint-stagedによるpre-commitフック
- **モダンテストフレームワーク**: Pest 4による包括的テスト（12+テストケース）、Architecture Testingサポート
- **統合.gitignore**: モノレポ全体のファイル管理（2024年12月更新）

## パフォーマンス最適化 - 🏆 業界標準以上の成果
### バックエンド最適化
- **最小依存関係**: 114→**4パッケージ** (96.5%削減)
- **ステートレス設計**: セッション除去でメモリ効率最大化
- **API専用最適化**: Web機能削除で起動速度**33.3%向上**
- **Redis**: 高速キャッシング (セッションストレージ不使用)
- **PostgreSQL**: 高性能データベース、ステートレス設計対応

### フロントエンド最適化
- **Turbopack**: Next.js 15.5最新バンドラーで高速ビルド
- **React 19**: 最新のConcurrent Featuresでパフォーマンス最大化
- **Tailwind CSS 4**: 最新CSSフレームワークでスタイル効率化

### 統合最適化
- **オプコード最適化**: Laravel標準最適化 + カスタム追加最適化
- **定量的パフォーマンス測定**: 90+テストケースで継続的パフォーマンス監視