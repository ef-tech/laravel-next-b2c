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
- **ワークフロー**: `.github/workflows/php-quality-check.yml`
- **自動実行**: Pull Request時に品質チェック
- **チェック内容**: Pint検証 + Larastan Level 8静的解析 + Pest テスト実行
- **Actionsバージョン**: v4 (最新版)

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
### Docker構成 (Laravel Sail)
```yaml
サービス構成:
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
```

### Docker環境
```bash
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
```env
APP_PORT=13000                    # Laravel アプリケーション
FORWARD_REDIS_PORT=13379          # Redis
FORWARD_DB_PORT=13432             # PostgreSQL
FORWARD_MAILPIT_PORT=11025        # Mailpit SMTP
FORWARD_MAILPIT_DASHBOARD_PORT=13025  # Mailpit UI
FORWARD_MINIO_PORT=13900          # MinIO API
FORWARD_MINIO_CONSOLE_PORT=13010  # MinIO Console
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