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
├── docker-compose.yml   # Docker Compose統合設定（全サービス一括起動）
├── .dockerignore        # Dockerビルド除外設定（モノレポ対応）
├── .gitignore           # 統合ファイル除外設定 (モノレポ対応)
├── package.json         # モノレポルート設定 (ワークスペース管理、共通スクリプト)
├── node_modules/        # 共通依存関係
├── CLAUDE.md            # プロジェクト開発ガイドライン
├── README.md            # プロジェクト概要
└── DOCKER_TROUBLESHOOTING.md  # Dockerトラブルシューティングガイド
```

## バックエンド構造 (`backend/laravel-api/`)
### Laravel標準構成
```
laravel-api/
├── app/                 # アプリケーションコア
│   ├── Console/         # Artisanコマンド
│   ├── Http/            # HTTP層 (Controllers, Middleware, Requests)
│   ├── Models/          # Eloquentモデル
│   └── Providers/       # サービスプロバイダー
├── bootstrap/           # アプリケーション初期化
├── config/              # 設定ファイル
├── database/            # データベース関連
│   ├── factories/       # モデルファクトリー
│   ├── migrations/      # マイグレーション
│   └── seeders/         # シーダー
├── docker/              # Docker設定 (PHP 8.0-8.4対応)
├── docs/                # プロジェクトドキュメント (最適化ガイド、運用手順)
├── public/              # 公開ディレクトリ (エントリーポイント)
├── resources/           # リソースファイル
│   ├── css/             # スタイルシート
│   ├── js/              # JavaScript/TypeScript
│   └── views/           # Bladeテンプレート
├── routes/              # ルート定義
│   ├── api.php          # API専用ルート
│   ├── web.php          # Web画面ルート
│   └── console.php      # コンソールルート
├── storage/             # ストレージ (ログ、キャッシュ、アップロード)
├── tests/               # テストスイート (Pest 4)
│   ├── Feature/         # 機能テスト
│   ├── Unit/            # ユニットテスト
│   ├── Arch/            # アーキテクチャテスト
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

### ファイル構成原則
#### Laravel (バックエンド)
- **1クラス1ファイル**: PSR-4標準準拠
- **名前空間**: `App\` をルートとする階層構造
- **Controller**: `App\Http\Controllers\` 配下
- **Model**: `App\Models\` 配下
- **Service**: `App\Services\` 配下 (ビジネスロジック分離)
- **Request**: `App\Http\Requests\` 配下 (バリデーション)

#### Next.js (フロントエンド)
- **Page Component**: `app/` ディレクトリ内のServer Components
- **Client Component**: `'use client'` ディレクティブ明示
- **共通Component**: `components/` ディレクトリで再利用
- **カスタムHook**: `hooks/` ディレクトリ、`use` プレフィックス
- **型定義**: `types/` ディレクトリ、`.d.ts` 拡張子

## Import構成指針
### バックエンド (Laravel API専用)
```php
// Laravel APIコア機能 (最小依存関係)
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;  // APIレスポンス専用
use App\Models\User;
use App\Services\Api\UserService;  // API専用サービス

// Sanctum認証 (コアパッケージ)
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;

// 最小必要パッケージのみ
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
```

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
import { useAuth } from '@/hooks/useAuth'        // Sanctumトークン認証対応
import type { User, ApiResponse } from '@/types/api'  // APIレスポンス型

// API通信 (Laravel API専用最適化対応)
import axios from 'axios'
import { apiClient } from '@/lib/api-client'     // Sanctum認証統合
import { clsx } from 'clsx'
```

## 主要アーキテクチャ原則
### 分離の原則
- **関心の分離**: UI層、ビジネスロジック層、データ層の明確な分離
- **API境界**: フロントエンドとバックエンドの完全な分離
- **アプリケーション分離**: 管理者用とユーザー用の独立開発
- **環境分離**: Docker Compose統合による開発環境の一貫性保証

### ディレクトリ責任
- **`backend/`**: API機能、データベース操作、ビジネスロジック
- **`frontend/admin-app/`**: 管理者機能UI、管理画面専用コンポーネント
- **`frontend/user-app/`**: ユーザー機能UI、顧客向けインターフェース
- **`.claude/`**: Claude Code設定、コマンド定義
- **`.kiro/`**: 仕様駆動開発、ステアリング文書

### 設定ファイル配置
- **環境設定**: 各アプリケーションルートの `.env`
- **ビルド設定**: 各技術スタック専用 (`package.json`, `composer.json`)
- **Docker設定**:
  - ルート: `docker-compose.yml` - 全サービス統合設定
  - バックエンド: `backend/laravel-api/compose.yaml` - Laravel Sail設定
  - フロントエンド: `frontend/{admin-app,user-app}/Dockerfile` - Next.js イメージ定義
  - ルート: `.dockerignore` - ビルド除外設定
- **開発ツール設定**: 各ディレクトリに適切な設定ファイル
- **PHP品質管理設定**:
  - `backend/laravel-api/pint.json` - Laravel Pint設定
  - `backend/laravel-api/phpstan.neon` - Larastan/PHPStan設定
- **CI/CD設定**: `.github/workflows/` - GitHub Actionsワークフロー
- **Next.js最適化設定**:
  - `frontend/{admin-app,user-app}/next.config.ts` - outputFileTracingRoot設定（モノレポ対応）

## 開発フロー指針
1. **API First**: バックエンドAPIを先行開発
2. **コンポーネント駆動**: フロントエンドの再利用可能設計
3. **型安全性**: TypeScript活用による開発時エラー防止
4. **テスト駆動**:
   - バックエンド: Pest 4による包括的テスト（12+テストケース）
   - フロントエンド: Jest 29 + Testing Library 16（カバレッジ94.73%）
   - E2E: Playwright 1.47.2によるエンドツーエンドテスト
   - テストサンプル: Client Component、Server Actions、Custom Hooks、API Fetch
   - Page Object Model: E2Eテストの保守性向上パターン
5. **環境分離**: 開発、ステージング、本番環境の明確な分離
6. **品質管理の自動化**:
   - Git Hooks (pre-commit: lint-staged, pre-push: composer quality)
   - CI/CD (GitHub Actions: Pull Request時の自動品質チェック)
   - 開発時の継続的品質保証
7. **E2E認証統合**:
   - Laravel Sanctum認証のE2Eテスト対応
   - Global Setup による認証状態の事前生成
   - 環境変数による柔軟なテスト環境設定