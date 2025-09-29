# Project Structure

## ルートディレクトリ構成
```
laravel-next-b2c/
├── backend/             # バックエンドAPI層
│   └── laravel-api/     # Laravel APIアプリケーション
├── frontend/            # フロントエンド層
│   ├── admin-app/       # 管理者向けアプリケーション
│   └── user-app/        # エンドユーザー向けアプリケーション
├── .claude/             # Claude Code設定・コマンド
├── .kiro/               # Kiro仕様駆動開発設定
├── .idea/               # IntelliJ IDEA設定 (IDE固有、gitignore済み)
├── .git/                # Gitリポジトリ
├── .gitignore           # 統合ファイル除外設定 (モノレポ対応)
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
├── next.config.js       # Next.js設定
└── eslint.config.js     # ESLint設定
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
### バックエンド (Laravel)
```php
// Laravel標準
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\UserService;

// 外部パッケージ
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
```

### フロントエンド (Next.js)
```typescript
// React関連
import React from 'react'
import { useState, useEffect } from 'react'

// Next.js関連
import Link from 'next/link'
import Image from 'next/image'

// 内部モジュール (相対パス避ける)
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/useAuth'
import type { User } from '@/types/user'

// 外部ライブラリ
import axios from 'axios'
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

## 開発フロー指針
1. **API First**: バックエンドAPIを先行開発
2. **コンポーネント駆動**: フロントエンドの再利用可能設計
3. **型安全性**: TypeScript活用による開発時エラー防止
4. **テスト駆動**: PHPUnit、Jest/Testing Libraryによる品質保証
5. **環境分離**: 開発、ステージング、本番環境の明確な分離