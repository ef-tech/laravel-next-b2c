# Project Structure

## ルートディレクトリ構成
```
laravel-next-b2c/
├── backend/             # バックエンドAPI層
│   └── laravel-api/     # Laravel APIアプリケーション
├── frontend/            # フロントエンド層
│   ├── admin-app/       # 管理者向けアプリケーション
│   └── user-app/        # エンドユーザー向けアプリケーション
├── .github/             # GitHub設定
│   └── workflows/       # GitHub Actionsワークフロー (CI/CD)
├── .claude/             # Claude Code設定・コマンド
├── .kiro/               # Kiro仕様駆動開発設定
├── .husky/              # Gitフック管理 (husky設定)
├── .idea/               # IntelliJ IDEA設定 (IDE固有、gitignore済み)
├── .git/                # Gitリポジトリ
├── .gitignore           # 統合ファイル除外設定 (モノレポ対応)
├── package.json         # モノレポルート設定 (ワークスペース管理、共通スクリプト)
├── node_modules/        # 共通依存関係
├── CLAUDE.md            # プロジェクト開発ガイドライン
└── README.md            # プロジェクト概要
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
├── tests/               # テストスイート
│   ├── Feature/         # 機能テスト
│   └── Unit/            # ユニットテスト
├── vendor/              # Composer依存関係
├── compose.yaml         # Docker Compose設定
├── composer.json        # PHP依存関係管理
├── package.json         # Node.js依存関係 (Vite用)
├── vite.config.js       # Vite設定
├── pint.json            # Laravel Pint設定 (コードフォーマッター)
├── phpstan.neon         # PHPStan/Larastan設定 (静的解析 Level 8)
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
│   │   └── page.tsx     # ホームページ
│   ├── components/      # 再利用可能コンポーネント
│   ├── lib/             # ユーティリティ・ヘルパー
│   ├── hooks/           # カスタムReactフック
│   ├── types/           # TypeScript型定義
│   └── utils/           # 汎用ユーティリティ
├── public/              # 静的ファイル
├── node_modules/        # Node.js依存関係
├── package.json         # フロントエンド依存関係管理
├── tsconfig.json        # TypeScript設定
├── tailwind.config.js   # Tailwind CSS設定
├── next.config.ts       # Next.js設定
└── eslint.config.mjs    # ESLint 9設定 (flat config形式)
```

### モノレポルート構成 (コード品質管理)
```
laravel-next-b2c/
├── package.json         # ワークスペース定義、共通スクリプト
│                        # workspaces: ["frontend/admin-app", "frontend/user-app"]
│                        # lint-staged設定を含む
├── .husky/              # Gitフック自動化
│   └── pre-commit       # コミット前にlint-staged実行
└── node_modules/        # 共通devDependencies
    ├── eslint           # ESLint 9
    ├── prettier         # Prettier 3
    ├── husky            # Gitフック管理
    └── lint-staged      # ステージファイルlint
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

### ディレクトリ責任
- **`backend/`**: API機能、データベース操作、ビジネスロジック
- **`frontend/admin-app/`**: 管理者機能UI、管理画面専用コンポーネント
- **`frontend/user-app/`**: ユーザー機能UI、顧客向けインターフェース
- **`.claude/`**: Claude Code設定、コマンド定義
- **`.kiro/`**: 仕様駆動開発、ステアリング文書

### 設定ファイル配置
- **環境設定**: 各アプリケーションルートの `.env`
- **ビルド設定**: 各技術スタック専用 (`package.json`, `composer.json`)
- **Docker設定**: バックエンドに統合 (`compose.yaml`)
- **開発ツール設定**: 各ディレクトリに適切な設定ファイル
- **PHP品質管理設定**:
  - `backend/laravel-api/pint.json` - Laravel Pint設定
  - `backend/laravel-api/phpstan.neon` - Larastan/PHPStan設定
- **CI/CD設定**: `.github/workflows/` - GitHub Actionsワークフロー

## 開発フロー指針
1. **API First**: バックエンドAPIを先行開発
2. **コンポーネント駆動**: フロントエンドの再利用可能設計
3. **型安全性**: TypeScript活用による開発時エラー防止
4. **テスト駆動**: PHPUnit、Jest/Testing Libraryによる品質保証
5. **環境分離**: 開発、ステージング、本番環境の明確な分離
6. **品質管理の自動化**:
   - Git Hooks (pre-commit: lint-staged, pre-push: composer quality)
   - CI/CD (GitHub Actions: Pull Request時の自動品質チェック)
   - 開発時の継続的品質保証