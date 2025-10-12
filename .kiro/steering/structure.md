# Project Structure

## ルートディレクトリ構成
```
laravel-next-b2c/
├── backend/             # バックエンドAPI層
│   └── laravel-api/     # Laravel APIアプリケーション
├── frontend/            # フロントエンド層
│   ├── admin-app/       # 管理者向けアプリケーション
│   └── user-app/        # エンドユーザー向けアプリケーション
├── e2e/                 # E2Eテスト環境 (Playwright)
├── .github/             # GitHub設定
│   └── workflows/       # GitHub Actionsワークフロー (CI/CD) - 発火タイミング最適化済み
│       ├── e2e-tests.yml          # E2Eテスト（4 Shard並列、Concurrency + Paths最適化）
│       ├── frontend-test.yml      # フロントエンドテスト（API契約監視含む）
│       ├── php-quality.yml        # PHP品質チェック（Pint + Larastan）
│       └── test.yml               # PHPテスト（Pest 4、Composerキャッシュ最適化）
├── .claude/             # Claude Code設定・コマンド
├── .kiro/               # Kiro仕様駆動開発設定
├── .husky/              # Gitフック管理 (husky設定)
├── .idea/               # IntelliJ IDEA設定 (IDE固有、gitignore済み)
├── .git/                # Gitリポジトリ
├── docker-compose.yml   # Docker Compose統合設定（全サービス一括起動、ヘルスチェック統合、プロジェクト固有イメージ命名）
│                        # Laravel APIヘルスチェック: curl http://127.0.0.1:${APP_PORT}/api/health (動的ポート対応)
├── .dockerignore        # Dockerビルド除外設定（モノレポ対応）
├── .gitignore           # 統合ファイル除外設定 (モノレポ対応)
├── Makefile             # テストインフラ管理タスク（quick-test, test-pgsql, test-parallel, test-setup, etc.）
├── package.json         # モノレポルート設定 (ワークスペース管理、共通スクリプト)
├── node_modules/        # 共通依存関係
├── CLAUDE.md            # プロジェクト開発ガイドライン
├── README.md            # プロジェクト概要
└── DOCKER_TROUBLESHOOTING.md  # Dockerトラブルシューティング完全ガイド（APP_PORTポート設定問題、イメージ再ビルド、完全クリーンアップ手順）
```

## バックエンド構造 (`backend/laravel-api/`)
### 🏗️ DDD/クリーンアーキテクチャ + Laravel標準構成
```
laravel-api/
├── ddd/                 # 🏗️ DDD/クリーンアーキテクチャ層 (新規)
│   ├── Domain/          # Domain層（ビジネスロジック中核）
│   │   └── User/        # ユーザー集約
│   │       ├── Entities/           # エンティティ（User.php）
│   │       ├── ValueObjects/       # 値オブジェクト（Email.php, UserId.php）
│   │       ├── Repositories/       # Repositoryインターフェース（UserRepositoryInterface.php）
│   │       ├── Events/             # ドメインイベント（UserRegistered.php）
│   │       ├── Services/           # ドメインサービス
│   │       └── Exceptions/         # ドメイン例外
│   ├── Application/     # Application層（ユースケース）
│   │   └── User/        # ユーザーユースケース
│   │       ├── UseCases/           # ユースケース（RegisterUserUseCase.php）
│   │       ├── DTOs/               # データ転送オブジェクト（RegisterUserInput.php, RegisterUserOutput.php）
│   │       ├── Services/           # アプリケーションサービスインターフェース（TransactionManager.php, EventBus.php）
│   │       ├── Queries/            # クエリインターフェース（UserQueryInterface.php）
│   │       └── Exceptions/         # アプリケーション例外
│   └── Infrastructure/  # Infrastructure層（外部システム実装）
│       └── Persistence/ # 永続化実装
│           ├── Eloquent/           # Eloquent Repository実装（EloquentUserRepository.php）
│           ├── Query/              # Query実装（EloquentUserQuery.php）
│           └── Services/           # サービス実装（LaravelTransactionManager.php, LaravelEventBus.php）
├── app/                 # Laravel標準アプリケーション層（既存MVC共存）
│   ├── Console/         # Artisanコマンド
│   │   └── Commands/    # カスタムコマンド
│   │       └── PruneExpiredTokens.php  # 🔐 期限切れトークン削除コマンド（tokens:prune）
│   ├── Http/            # 🏗️ HTTP層（DDD統合）
│   │   ├── Controllers/ # Controllerからユースケース呼び出し
│   │   │   ├── Api/     # 📊 API基本機能コントローラー
│   │   │   │   └── HealthController.php  # ヘルスチェック（GET /api/health）
│   │   │   ├── Auth/    # 🔐 認証コントローラー
│   │   │   │   ├── LoginController.php     # ログイン処理（POST /api/login, POST /api/logout）
│   │   │   │   ├── MeController.php        # 認証ユーザー情報（GET /api/me）
│   │   │   │   └── TokenController.php     # トークン管理（GET /api/tokens, POST /api/tokens/{id}/revoke）
│   │   ├── Middleware/  # ミドルウェア
│   │   │   └── Authenticate.php  # 🔐 Sanctum認証ミドルウェア（auth:sanctum）
│   │   ├── Requests/    # リクエストバリデーション
│   │   │   └── Auth/    # 🔐 認証リクエスト
│   │   │       └── LoginRequest.php  # ログインバリデーション（email, password必須）
│   │   └── Resources/   # APIリソース
│   │       └── UserResource.php  # ユーザーAPIレスポンス
│   ├── Models/          # Eloquentモデル（Infrastructure層で使用）
│   │   └── User.php     # 🔐 Userモデル（HasApiTokens trait使用）
│   └── Providers/       # サービスプロバイダー（DI設定含む）
├── bootstrap/           # アプリケーション初期化
├── config/              # 設定ファイル
│   ├── sanctum.php      # 🔐 Sanctum認証設定（stateful_domains, expiration等）
│   └── auth.php         # 認証設定（guards: sanctum）
├── database/            # データベース関連
│   ├── factories/       # モデルファクトリー
│   ├── migrations/      # マイグレーション
│   │   └── 2019_12_14_000001_create_personal_access_tokens_table.php  # 🔐 Sanctumトークンテーブル
│   └── seeders/         # シーダー
├── docker/              # Docker設定 (PHP 8.0-8.4対応、APP_PORTデフォルト13000最適化済み)
├── docs/                # 🏗️ プロジェクトドキュメント（DDD + 最適化ガイド + インフラ検証 + テストDB運用 + 認証 + Docker）
│   ├── ddd-architecture.md        # DDD 4層構造アーキテクチャ概要
│   ├── ddd-development-guide.md   # DDD開発ガイドライン
│   ├── ddd-testing-strategy.md    # DDD層別テスト戦略
│   ├── ddd-troubleshooting.md     # DDDトラブルシューティング
│   ├── database-connection.md     # PostgreSQL接続設定ガイド（環境別設定・タイムアウト最適化・トラブルシューティング）
│   ├── VERIFICATION.md            # Dockerヘルスチェック検証手順ドキュメント
│   ├── TESTING_DATABASE_WORKFLOW.md  # テストDB運用ワークフローガイド（SQLite/PostgreSQL切り替え、並列テスト実行）
│   ├── sanctum-authentication-guide.md  # 🔐 Sanctum認証ガイド（エンドポイント、トークン管理、セキュリティ設定、トラブルシューティング）
│   ├── DOCKER_TROUBLESHOOTING.md  # Dockerトラブルシューティング（APP_PORTポート設定、イメージ再ビルド、完全クリーンアップ）
│   └── [その他最適化ドキュメント]
├── public/              # 公開ディレクトリ (エントリーポイント)
├── resources/           # リソースファイル
│   ├── css/             # スタイルシート
│   ├── js/              # JavaScript/TypeScript
│   └── views/           # Bladeテンプレート
├── routes/              # ルート定義
│   ├── api.php          # API専用ルート
│   │                    # 📊 ヘルスチェックエンドポイント:
│   │                    #   - GET /api/health (HealthController@show, ルート名: health)
│   │                    # 🔐 認証エンドポイント:
│   │                    #   - POST /api/login (LoginController@login)
│   │                    #   - POST /api/logout (LoginController@logout, auth:sanctum)
│   │                    #   - GET /api/me (MeController@show, auth:sanctum)
│   │                    #   - GET /api/tokens (TokenController@index, auth:sanctum)
│   │                    #   - POST /api/tokens/{id}/revoke (TokenController@revoke, auth:sanctum)
│   │                    #   - POST /api/tokens/refresh (TokenController@refresh, auth:sanctum)
│   ├── web.php          # Web画面ルート
│   └── console.php      # コンソールルート
│                        # 🔐 Scheduled Tasks:
│                        #   - tokens:prune (PruneExpiredTokens, 毎日実行)
├── storage/             # ストレージ (ログ、キャッシュ、アップロード)
├── tests/               # 🏗️ テストスイート (Pest 4 + Architecture Tests: 96.1%カバレッジ)
│   ├── Feature/         # 機能テスト（HTTP層統合テスト）
│   │   ├── Api/         # 📊 API基本機能テスト
│   │   │   └── HealthCheckTest.php  # ヘルスチェックエンドポイントテスト（JSON形式、Content-Type、ルート名検証）
│   │   └── Auth/        # 🔐 認証機能テスト
│   │       ├── LoginTest.php          # ログイン・ログアウトテスト（12テスト）
│   │       └── TokenManagementTest.php # トークン管理テスト（一覧取得、無効化、更新）
│   ├── Unit/            # ユニットテスト（ドメインロジックテスト）
│   ├── Arch/            # 🏗️ Architecture Tests（依存方向検証、レイヤー分離チェック）
│   │   ├── DomainLayerTest.php         # Domain層依存チェック
│   │   ├── ApplicationLayerTest.php    # Application層依存チェック
│   │   ├── InfrastructureLayerTest.php # Infrastructure層実装チェック
│   │   └── NamingConventionTest.php    # 命名規約検証
│   ├── Pest.php         # Pest設定・ヘルパー
│   └── TestCase.php     # 基底テストクラス
├── vendor/              # Composer依存関係
├── compose.yaml         # Docker Compose設定
├── composer.json        # PHP依存関係管理
├── package.json         # Node.js依存関係 (Vite用)
├── vite.config.js       # Vite設定
├── pint.json            # Laravel Pint設定 (コードフォーマッター)
├── phpstan.neon         # PHPStan/Larastan設定 (静的解析 Level 8)
├── phpunit.xml          # Pest設定ファイル（Pest用phpunit.xml）
└── .env                 # 環境設定
```

## フロントエンド構造
### Next.js App Router構成 (両アプリ共通)
```
{admin-app|user-app}/
├── src/                 # ソースコード
│   ├── app/             # App Router (Next.js 13+)
│   │   ├── globals.css  # グローバルスタイル
│   │   ├── layout.tsx   # ルートレイアウト
│   │   ├── page.tsx     # ホームページ
│   │   └── actions.ts   # Server Actions
│   ├── components/      # 再利用可能コンポーネント
│   │   └── **/*.test.tsx # コンポーネントテスト
│   ├── lib/             # ユーティリティ・ヘルパー
│   │   └── **/*.test.ts  # ライブラリテスト
│   ├── hooks/           # カスタムReactフック
│   │   └── **/*.test.ts  # フックテスト
│   ├── types/           # TypeScript型定義
│   └── utils/           # 汎用ユーティリティ
├── public/              # 静的ファイル
├── coverage/            # テストカバレッジレポート
├── node_modules/        # Node.js依存関係
├── Dockerfile           # Next.js Dockerイメージ定義（本番ビルド最適化）
├── package.json         # フロントエンド依存関係管理（--port固定設定）
├── tsconfig.json        # TypeScript設定
├── jest.config.js       # Jest設定（プロジェクト固有）
├── tailwind.config.js   # Tailwind CSS設定
├── next.config.ts       # Next.js設定（outputFileTracingRoot設定、モノレポ対応）
└── eslint.config.mjs    # ESLint 9設定 (flat config形式)
```

**Docker最適化ポイント**:
- **outputFileTracingRoot**: モノレポルート指定で依存関係トレース最適化
- **standalone出力**: 最小限ファイルセットによる軽量Dockerイメージ
- **マルチステージビルド**: builder → runner ステージ分離
- **libc6-compat**: Alpine Linux上でのNext.js互換性保証

### モノレポルート構成 (コード品質管理・テスト・Docker)
```
laravel-next-b2c/
├── docker-compose.yml   # Docker Compose統合設定
│                        # - 全サービス定義 (laravel-api, admin-app, user-app, pgsql, redis, etc.)
│                        # - プロジェクト固有イメージ命名 (laravel-next-b2c/app、他プロジェクトとの競合回避)
│                        # - APP_PORTデフォルト値最適化 (Dockerfile: 13000、ランタイム変更可能)
│                        # - ヘルスチェック機能統合 (全サービスの起動状態監視)
│                        # - 依存関係の自動管理 (depends_on: service_healthy)
│                        # - IPv4明示対応 (localhost→127.0.0.1)
│                        # - ネットワーク設定
│                        # - ボリューム管理
│                        # - 環境変数設定
├── .dockerignore        # Dockerビルド除外設定
│                        # - node_modules, .next, .git等の除外
│                        # - モノレポ対応（各サブディレクトリで有効）
├── package.json         # ワークスペース定義、共通スクリプト
│                        # workspaces: ["frontend/admin-app", "frontend/user-app"]
│                        # lint-staged設定を含む
├── jest.base.js         # モノレポ共通Jest設定
├── jest.config.js       # プロジェクト統括Jest設定
├── jest.setup.ts        # グローバルテストセットアップ
├── test-utils/          # 共通テストユーティリティ
│   ├── render.tsx       # カスタムrender関数
│   ├── router.ts        # Next.js Router モック設定
│   └── env.ts           # 環境変数モック
├── coverage/            # 統合カバレッジレポート
├── .husky/              # Gitフック自動化 (husky v9推奨方法: 直接フック配置)
│   ├── pre-commit       # コミット前にlint-staged実行
│   ├── pre-push         # プッシュ前にcomposer quality実行
│   └── _/               # レガシーフック（非推奨、互換性のため残存）
└── node_modules/        # 共通devDependencies
    ├── eslint           # ESLint 9
    ├── prettier         # Prettier 3
    ├── husky            # Gitフック管理
    ├── lint-staged      # ステージファイルlint
    ├── jest             # Jest 29
    └── @testing-library # React Testing Library 16
```

## E2Eテスト構造 (`e2e/`)
### Playwright E2Eテスト構成
```
e2e/
├── fixtures/            # テストフィクスチャ
│   └── global-setup.ts  # グローバルセットアップ（Sanctum認証）
├── helpers/             # テストヘルパー関数
│   └── sanctum.ts       # Laravel Sanctum認証ヘルパー
├── projects/            # プロジェクト別テスト
│   ├── admin/           # Admin Appテスト
│   │   ├── pages/       # Page Object Model (POM)
│   │   │   ├── LoginPage.ts     # ログインページオブジェクト
│   │   │   └── ProductsPage.ts  # 商品ページオブジェクト
│   │   └── tests/       # テストケース
│   │       ├── home.spec.ts          # ホームページテスト
│   │       ├── login.spec.ts         # ログインテスト（未実装スキップ中）
│   │       └── products-crud.spec.ts # 商品CRUD操作テスト（未実装スキップ中）
│   └── user/            # User Appテスト
│       ├── pages/       # Page Object Model
│       └── tests/       # テストケース
│           ├── home.spec.ts              # ホームページテスト
│           └── api-integration.spec.ts   # API統合テスト（未実装スキップ中）
├── storage/             # 認証状態ファイル（自動生成）
│   ├── admin.json       # Admin認証状態
│   └── user.json        # User認証状態
├── reports/             # テストレポート（自動生成）
├── test-results/        # テスト実行結果（自動生成）
├── playwright.config.ts # Playwright設定
├── package.json         # E2E依存関係
├── tsconfig.json        # TypeScript設定
├── .env                 # E2E環境変数（gitignore済み）
├── .env.example         # E2E環境変数テンプレート
└── README.md            # E2Eテストガイド（セットアップ、実行方法、CI/CD統合）
```

### CI/CD E2Eテスト実行フロー
```
GitHub Actions (.github/workflows/e2e-tests.yml):
1. トリガー: Pull Request / mainブランチpush / 手動実行
2. 並列実行: 4 Shard Matrix戦略（約2分完了）
3. セットアップ:
   - PHP 8.4インストール
   - Composerキャッシング（高速化）
   - Node.js 20セットアップ
   - npm依存関係インストール
4. サービス起動:
   - Laravel API: 開発モード（php artisan serve）
   - User App: npm run dev（ポート: 13001）
   - Admin App: npm run dev（ポート: 13002）
5. wait-on: 全サービス起動待機（タイムアウト: 5分）
6. Playwrightテスト実行: 各Shardごとに並列実行
7. レポート保存: Artifacts（HTML/JUnit、スクリーンショット、トレース）
```

## コード構成パターン
### 命名規約
- **ディレクトリ**: kebab-case (`admin-app`, `user-app`)
- **ファイル**: kebab-case (`.tsx`, `.ts`, `.php`)
- **コンポーネント**: PascalCase (`UserProfile.tsx`)
- **関数・変数**: camelCase (`getUserData`)
- **定数**: SCREAMING_SNAKE_CASE (`API_BASE_URL`)
- **型定義**: PascalCase (`UserInterface`, `ApiResponse`)

**🏗️ DDD固有命名規約**:
- **Entity**: PascalCase + `Entity`なし (`User.php`, not `UserEntity.php`)
- **ValueObject**: PascalCase (`Email.php`, `UserId.php`)
- **Repository Interface**: PascalCase + `RepositoryInterface` (`UserRepositoryInterface.php`)
- **Repository実装**: `Eloquent` + 名前 + `Repository` (`EloquentUserRepository.php`)
- **UseCase**: PascalCase + `UseCase` (`RegisterUserUseCase.php`)
- **DTO**: 用途 + 名前 + `Input/Output` (`RegisterUserInput.php`, `RegisterUserOutput.php`)
- **Domain Event**: 過去形 + `Event`なし (`UserRegistered.php`)
- **Query Interface**: PascalCase + `QueryInterface` (`UserQueryInterface.php`)

### ファイル構成原則
#### 🏗️ Laravel DDD/クリーンアーキテクチャ (バックエンド)
**4層構造の責務分離**:
- **Domain層** (`ddd/Domain/`):
  - 1集約1ディレクトリ（例: `ddd/Domain/User/`）
  - Entities、ValueObjects、Repository Interfaces、Events、Services、Exceptionsをサブディレクトリで整理
  - Laravelフレームワークに依存しない（Carbon除く）
- **Application層** (`ddd/Application/`):
  - 1集約1ディレクトリ（例: `ddd/Application/User/`）
  - UseCases、DTOs、Service Interfaces、Queries、Exceptionsをサブディレクトリで整理
  - Infrastructure層に依存しない（依存性逆転）
- **Infrastructure層** (`ddd/Infrastructure/`):
  - Repository実装、Query実装、Service実装をPersistence配下に配置
  - Eloquent依存コードはここに集約
- **HTTP層** (`app/Http/`):
  - Controllers、Requests、Resources、Middlewareを配置
  - Controllerはユースケース呼び出しのみ（薄いレイヤー）

**依存方向ルール**:
- HTTP → Application → Domain ← Infrastructure
- Domain層は他の層に依存しない（中心層）
- Infrastructure層はDomain/Application層のインターフェースを実装

**既存Laravel標準構成**:
- **1クラス1ファイル**: PSR-4標準準拠
- **名前空間**: `App\` をルートとする階層構造
- **Controller**: `App\Http\Controllers\` 配下
- **Model**: `App\Models\` 配下（Infrastructure層で使用）
- **Service**: `App\Services\` 配下 (従来のビジネスロジック、段階的にDDD移行)
- **Request**: `App\Http\Requests\` 配下 (バリデーション)

#### Next.js (フロントエンド)
- **Page Component**: `app/` ディレクトリ内のServer Components
- **Client Component**: `'use client'` ディレクティブ明示
- **共通Component**: `components/` ディレクトリで再利用
- **カスタムHook**: `hooks/` ディレクトリ、`use` プレフィックス
- **型定義**: `types/` ディレクトリ、`.d.ts` 拡張子

## Import構成指針
### バックエンド (Laravel DDD + API専用)
```php
// 🏗️ DDD層のインポート順序
// 1. Domain層（最上位）
use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Domain\User\Repositories\UserRepositoryInterface;
use Ddd\Domain\User\Events\UserRegistered;

// 2. Application層（ユースケース）
use Ddd\Application\User\UseCases\RegisterUserUseCase;
use Ddd\Application\User\DTOs\RegisterUserInput;
use Ddd\Application\User\DTOs\RegisterUserOutput;
use Ddd\Application\User\Services\TransactionManager;
use Ddd\Application\User\Queries\UserQueryInterface;

// 3. Infrastructure層（実装）
use Ddd\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Ddd\Infrastructure\Persistence\Query\EloquentUserQuery;

// 4. Laravel APIコア機能（最小依存関係）
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;  // APIレスポンス専用
use App\Models\User as EloquentUser;  // EloquentモデルはInfrastructureで使用
use App\Http\Requests\Api\RegisterUserRequest;
use App\Http\Resources\UserResource;

// 5. Sanctum認証（コアパッケージ）
use Laravel\Sanctum\HasApiTokens;           // Personal Access Tokens trait
use Laravel\Sanctum\PersonalAccessToken;    // トークンモデル
use Illuminate\Support\Facades\Auth;        // 認証ファサード

// 6. 最小必要パッケージのみ
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
```

**Import原則**:
- Domain層は他の層をimportしない（Laravelフレームワーク除く）
- Application層はDomain層のみimport
- Infrastructure層はDomain/Application層のインターフェースを実装
- HTTP層はApplication層のユースケースを呼び出し

### フロントエンド (Next.js 15.5 + React 19)
```typescript
// React 19最新機能
import React from 'react'
import { useState, useEffect, use } from 'react'  // React 19 'use' hook

// Next.js 15.5 App Router
import Link from 'next/link'
import Image from 'next/image'
import { notFound } from 'next/navigation'

// 内部モジュール (相対パス避ける)
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/useAuth'        // 🔐 Sanctumトークン認証カスタムフック
import type { User, ApiResponse } from '@/types/api'  // APIレスポンス型

// API通信 (Laravel API専用最適化対応 + 🔐 Sanctum認証統合)
import axios from 'axios'
import { apiClient } from '@/lib/api-client'     // Sanctum認証統合
// 🔐 Sanctum認証APIエンドポイント:
// - POST /api/login: { email, password } → { token, user }
// - POST /api/logout: Authorization Bearer token
// - GET /api/me: Authorization Bearer token → user
// - GET /api/tokens: Authorization Bearer token → tokens[]
// - POST /api/tokens/{id}/revoke: Authorization Bearer token
import { clsx } from 'clsx'
```

## 主要アーキテクチャ原則
### 🏗️ DDD/クリーンアーキテクチャ原則
- **依存性逆転原則 (DIP)**: Domain層を中心とした依存方向の制御、インターフェースによる抽象化
- **単一責任原則 (SRP)**: 各レイヤーと各クラスは単一の責務のみを持つ
- **オープン・クローズド原則 (OCP)**: 拡張に対して開いており、変更に対して閉じている
- **リスコフの置換原則 (LSP)**: 派生型はその基本型と置換可能
- **インターフェース分離原則 (ISP)**: クライアントに特化したインターフェース設計
- **4層依存ルール**: HTTP → Application → Domain ← Infrastructure
- **Architecture Testing**: Pestによる依存方向とレイヤー分離の自動検証

### 分離の原則
- **関心の分離**: UI層、ビジネスロジック層、データ層の明確な分離（DDD 4層構造）
- **API境界**: フロントエンドとバックエンドの完全な分離
- **アプリケーション分離**: 管理者用とユーザー用の独立開発
- **環境分離**: Docker Compose統合による開発環境の一貫性保証
- **インフラ信頼性**: Dockerヘルスチェック機能による起動保証と障害検知
- **既存MVCとDDD共存**: 段階的移行戦略による既存機能の保守性維持

### ディレクトリ責任
- **`backend/laravel-api/`**: API機能、データベース操作、ビジネスロジック
  - **`ddd/`**: 🏗️ DDD/クリーンアーキテクチャ実装
    - **`Domain/`**: ビジネスロジック中核（フレームワーク非依存）
    - **`Application/`**: ユースケース実装（Infrastructure非依存）
    - **`Infrastructure/`**: 外部システム実装（Repository、Query、Services）
  - **`app/`**: Laravel標準構成（HTTP層、既存MVC共存）
  - **`tests/`**: テストスイート（Feature、Unit、🏗️ Arch）
- **`frontend/admin-app/`**: 管理者機能UI、管理画面専用コンポーネント
- **`frontend/user-app/`**: ユーザー機能UI、顧客向けインターフェース
- **`.claude/`**: Claude Code設定、コマンド定義
- **`.kiro/`**: 仕様駆動開発、ステアリング文書

### 設定ファイル配置
- **環境設定**: 各アプリケーションルートの `.env`
- **ビルド設定**: 各技術スタック専用 (`package.json`, `composer.json`)
- **Docker設定**:
  - ルート: `docker-compose.yml` - 全サービス統合設定（ヘルスチェック統合、依存関係管理、プロジェクト固有イメージ命名）
  - バックエンド: `backend/laravel-api/compose.yaml` - Laravel Sail設定
  - バックエンド: `backend/laravel-api/docker/8.4/Dockerfile` - Laravel APIイメージ定義（APP_PORT=13000デフォルト最適化済み）
  - フロントエンド: `frontend/{admin-app,user-app}/Dockerfile` - Next.js イメージ定義
  - ルート: `.dockerignore` - ビルド除外設定
  - ドキュメント: `backend/laravel-api/docs/VERIFICATION.md` - Dockerヘルスチェック検証手順
  - ドキュメント: `DOCKER_TROUBLESHOOTING.md` - Dockerトラブルシューティング完全ガイド（APP_PORTポート設定、イメージ再ビルド、完全クリーンアップ）
- **テストインフラ設定**:
  - ルート: `Makefile` - テストDB管理タスク（quick-test, test-pgsql, test-parallel, test-setup等）
  - ルート: `scripts/` - テスト環境切り替え・並列テストスクリプト
  - ドキュメント: `docs/TESTING_DATABASE_WORKFLOW.md` - テストDB運用ワークフローガイド
- **開発ツール設定**: 各ディレクトリに適切な設定ファイル
- **PHP品質管理設定**:
  - `backend/laravel-api/pint.json` - Laravel Pint設定
  - `backend/laravel-api/phpstan.neon` - Larastan/PHPStan設定
- **CI/CD設定**: `.github/workflows/` - GitHub Actionsワークフロー
- **Next.js最適化設定**:
  - `frontend/{admin-app,user-app}/next.config.ts` - outputFileTracingRoot設定（モノレポ対応）

## 開発フロー指針
1. **🏗️ DDD/クリーンアーキテクチャ開発フロー**:
   - **Domain First**: ビジネスロジックをDomain層で先行実装（Entity、ValueObject、Repository Interface）
   - **UseCase実装**: Application層でユースケース実装（DTO、UseCase）
   - **Infrastructure実装**: Repository/Query実装（EloquentベースのConcrete実装）
   - **HTTP統合**: Controller からユースケース呼び出し（薄いHTTP層）
   - **Architecture Testing**: Pestによる依存方向の自動検証
2. **API First**: バックエンドAPIを先行開発
3. **コンポーネント駆動**: フロントエンドの再利用可能設計
4. **型安全性**: TypeScript活用による開発時エラー防止
5. **テスト駆動（96.1%カバレッジ達成）**:
   - バックエンド: Pest 4による包括的テスト
     - Unit Tests: Domain層ロジックテスト（Domain層100%カバレッジ）
     - Feature Tests: Application層統合テスト（Application層98%カバレッジ）
     - 🏗️ Architecture Tests: 依存方向検証、レイヤー分離チェック、命名規約検証
     - テストDB環境: SQLite（高速開発）/PostgreSQL（本番同等）の柔軟な切り替え、並列テスト実行（4 Shard）
   - フロントエンド: Jest 29 + Testing Library 16（カバレッジ94.73%）
   - E2E: Playwright 1.47.2によるエンドツーエンドテスト
   - テストサンプル: Client Component、Server Actions、Custom Hooks、API Fetch
   - Page Object Model: E2Eテストの保守性向上パターン
   - Makefileタスク: テストインフラ管理の標準化（quick-test, test-pgsql, ci-test）
6. **環境分離**: 開発、ステージング、本番環境の明確な分離
7. **品質管理の自動化**:
   - Git Hooks (pre-commit: lint-staged, pre-push: composer quality)
   - CI/CD (GitHub Actions: Pull Request時の自動品質チェック + Architecture Tests)
   - 開発時の継続的品質保証
8. **E2E認証統合**:
   - Laravel Sanctum認証のE2Eテスト対応
   - Global Setup による認証状態の事前生成
   - 環境変数による柔軟なテスト環境設定
9. **既存MVCとDDD共存戦略**:
   - 段階的移行アプローチ（新機能はDDD、既存機能は徐々に移行）
   - 共存期間の明確な責務分離
   - リファクタリング優先順位の設定