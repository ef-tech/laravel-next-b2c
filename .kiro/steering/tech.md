# Technology Stack

## アーキテクチャ
- **API専用最適化Laravel**: 必要最小限4パッケージ構成による超高速起動
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
- **husky**: ^9 (Gitフック管理、pre-commit自動実行)
- **lint-staged**: ^15 (ステージされたファイルのみlint/format実行)
- **設定ファイル**: 各アプリに`eslint.config.mjs`（ESLint 9対応flat config）

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

### データベース・ストレージ
- **PostgreSQL**: 17-alpine (主データベース - ステートレス設計対応)
- **Redis**: alpine (キャッシュ管理 - セッションストレージ不使用)
- **MinIO**: オブジェクトストレージ (S3互換)

**最適化ポイント**:
- セッションストレージをRedisから除去、キャッシュのみ使用
- ステートレス設計によりDBコネクション最適化

### 開発・テストツール
- **Laravel Pint**: ^1.24 (コードフォーマッター - コアパッケージ)
- **Larastan (PHPStan)**: ^3.0 (静的解析ツール - Level 8厳格チェック)
- **Pest**: ^3.12 (モダンテストフレームワーク - PHPUnitから完全移行、Architecture Testingサポート)
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

#### Git Hooks自動化 (backend/.husky/)
- **Pre-commit**: lint-staged実行 (変更PHPファイルのみPint自動フォーマット)
- **Pre-push**: `composer quality`実行 (Pint + Larastan全体チェック)

#### CI/CD統合 (GitHub Actions v4)
- **PHP品質チェックワークフロー**: `.github/workflows/php-quality-check.yml`
  - **自動実行**: Pull Request時に品質チェック
  - **チェック内容**: Pint検証 + Larastan Level 8静的解析 + Pest テスト実行
  - **Actionsバージョン**: v4 (最新版)
- **E2Eテストワークフロー**: `.github/workflows/e2e-tests.yml`
  - **自動実行**: Pull Request時（frontend/**, backend/**, e2e/** 変更時）、mainブランチpush時、手動実行
  - **実行方式**: 4 Shard並列実行（Matrix戦略）、約2分完了
  - **タイムアウト**: 20分（最適化済み、旧60分）
  - **パフォーマンス最適化**:
    - Composerキャッシング（`actions/cache@v4`）
    - 並列実行制御（`concurrency`設定でPR重複実行キャンセル）
    - pathsフィルター（影響範囲のみ実行）
  - **実行環境**: Docker開発モード起動（ビルド不要、高速化）
  - **レポート**: Playwright HTML/JUnitレポート、失敗時のスクリーンショット・トレース
  - **Artifacts**: 各Shardごとのテストレポート保存

### 📝 最適化ドキュメント体系
**`backend/laravel-api/docs/` に包括的ドキュメントを格納**:
- `laravel-optimization-process.md`: 最適化プロセス完了レポート
- `performance-report.md`: パフォーマンス改善定量分析
- `development-setup.md`: API専用開発環境構築手順
- `migration-guide.md`: 他プロジェクトへの移行ガイド
- `troubleshooting.md`: トラブルシューティング完全ガイド
- `configuration-changes.md`: 全設定変更の詳細記録
- `laravel-pint-larastan-team-guide.md`: Laravel Pint・Larastanチーム運用ドキュメント

## 開発環境
### Docker Compose構成（統合環境）
```yaml
サービス構成:
# バックエンド
- laravel-api: Laravel 12 API (PHP 8.4) - ポート: 13000
- pgsql: PostgreSQL 17-alpine - ポート: 13432
- redis: Redis alpine - ポート: 13379
- mailpit: 開発用メールサーバー - SMTP: 11025, UI: 13025
- minio: オブジェクトストレージ - API: 13900, Console: 13010

# フロントエンド
- admin-app: Next.js 15.5 管理者アプリ - ポート: 13002
- user-app: Next.js 15.5 ユーザーアプリ - ポート: 13001

# テスト環境
- e2e-tests: Playwright E2Eテスト (オンデマンド実行)
```

**Docker Compose統合の利点**:
- 全サービス一括起動（`docker compose up -d`）
- 統一されたネットワーク設定
- サービス間通信の最適化
- 環境変数の一元管理
- E2Eテスト環境の完全統合

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

# テスト実行 (Pest 4)
composer test                    # Pest テストスイート実行
./vendor/bin/pest                # Pest 直接実行
./vendor/bin/pest --coverage     # カバレッジレポート生成
./vendor/bin/pest --parallel     # 並列実行

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