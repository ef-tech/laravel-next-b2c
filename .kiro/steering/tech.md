# Technology Stack

## アーキテクチャ
- **マイクロフロントエンド型構成**: 管理者用とユーザー用アプリケーションの分離
- **API駆動型設計**: Laravel APIサーバーとNext.jsクライアントの疎結合
- **Docker化インフラ**: Laravel Sailによるコンテナベース開発環境
- **フルスタックTypeScript**: フロントエンドからバックエンドまでの型安全性

## フロントエンド技術
### フレームワーク・ライブラリ
- **Next.js**: 15.5.4 (React Server Components、App Router対応)
- **React**: 19.1.0 (最新のConcurrent Features)
- **TypeScript**: ^5 (厳密な型チェック)
- **Tailwind CSS**: ^4.0.0 (最新版CSS framework)

### ビルド・開発ツール
- **Turbopack**: Next.js標準バンドラー (`--turbopack`フラグ)
- **ESLint**: ^9 (コード品質管理)
- **PostCSS**: Tailwind CSS統合用

### デュアルアプリケーション構成
- **Admin App** (`frontend/admin-app/`): 管理者向けダッシュボード
- **User App** (`frontend/user-app/`): エンドユーザー向けアプリケーション

## バックエンド技術
### 言語・フレームワーク
- **PHP**: ^8.4 (最新のPHP機能対応)
- **Laravel**: ^12.0 (最新LTS版)
- **Composer**: パッケージ管理

### データベース・ストレージ
- **PostgreSQL**: 17-alpine (主データベース)
- **Redis**: alpine (キャッシュ・セッション管理)
- **MinIO**: オブジェクトストレージ (S3互換)

### 開発・テストツール
- **Laravel Pint**: ^1.24 (コードフォーマッター)
- **PHPUnit**: ^11.5.3 (テストフレームワーク)
- **Laravel Sail**: ^1.41 (Docker開発環境)
- **Laravel Tinker**: ^2.10.1 (REPL環境)
- **Faker**: ^1.23 (テストデータ生成)

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

# テスト実行
composer test
php artisan test

# コードフォーマット
vendor/bin/pint
```

### フロントエンド (Next.js)
```bash
# 各アプリディレクトリで実行
npm run dev    # 開発サーバー (Turbopack有効)
npm run build  # 本番ビルド
npm start      # 本番サーバー
npm run lint   # ESLintチェック
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
- **XDEBUG**: 開発・デバッグサポート
- **CSRFプロテクション**: Laravel標準実装
- **環境分離**: .env設定による環境別管理
- **型安全性**: TypeScript全面採用
- **コード品質**: ESLint + Laravel Pint統合
- **テスト環境**: PHPUnit統合、テスト用データベース自動作成
- **統合.gitignore**: モノレポ全体のファイル管理（2024年12月更新）

## パフォーマンス最適化
- **Turbopack**: 高速バンドリング
- **React 19**: 最新のパフォーマンス機能
- **Redis**: 高速キャッシング
- **PostgreSQL**: 高性能データベース
- **Vite**: 高速開発ビルド
- **オプコード最適化**: Laravel標準最適化