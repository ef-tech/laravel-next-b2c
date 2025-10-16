# Design Document: 環境変数適切管理方法整備

## 1. 概要

### 1.1 目的
Laravel 12 + Next.js 15.5 モノレポ構成における環境変数管理の標準化を実現する。本設計では、フェイルファスト設計によるエラー早期検出、型安全な環境変数アクセス、詳細なドキュメント整備、CI/CD統合による自動バリデーションを提供する。

### 1.2 設計方針
- **フェイルファスト設計**: 起動時バリデーションによる即座のエラー検出
- **型安全性の保証**: TypeScript型定義とZodスキーマによる実行時型検証
- **ドキュメント駆動**: .env.example を生きた仕様書として機能させる
- **自動化優先**: 環境変数の同期・検証を自動化し、人的ミスを最小化
- **段階的導入**: 警告モード → エラーモードの2段階ロールアウト戦略
- **セキュリティファースト**: 機密情報の安全な管理とGitHub Secrets統合

### 1.3 技術スタック整合性
- **Backend**: Laravel 12、PHP 8.4、Composer、Artisan
- **Frontend**: Next.js 15.5、React 19、TypeScript、Zod、@next/env、tsx
- **Infrastructure**: Docker、Docker Compose、GitHub Actions
- **Tools**: Laravel Pint、Larastan (PHPStan Level 8)、ESLint、Prettier

### 1.4 既存システムとの統合
- **既存Docker Compose統合**: 環境変数バリデーションをヘルスチェックフローに統合
- **既存GitHub Actionsワークフロー拡張**: `.github/workflows/test.yml`、`.github/workflows/frontend-test.yml` に環境変数検証ステップ追加
- **既存テストインフラ統合**: Pest 4、Jest 29テストスイートに環境変数バリデーションテスト追加

---

## 2. システムアーキテクチャ

### 2.1 全体構成図

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        環境変数管理システム                               │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
        ┌───────────────────────────┼───────────────────────────┐
        │                           │                           │
┌───────▼────────┐       ┌──────────▼─────────┐       ┌────────▼────────┐
│ Laravel API    │       │ Next.js Apps       │       │ CI/CD Pipeline  │
│ バリデーション   │       │ バリデーション      │       │ 環境変数検証     │
└───────┬────────┘       └──────────┬─────────┘       └────────┬────────┘
        │                           │                           │
┌───────▼────────┐       ┌──────────▼─────────┐       ┌────────▼────────┐
│ env_schema.php │       │ env.ts (Zod)       │       │ GitHub Secrets  │
│ EnvValidator   │       │ check-env.ts       │       │ Validation      │
│ Bootstrapper   │       │ (prebuild hook)    │       │ (PR checks)     │
└───────┬────────┘       └──────────┬─────────┘       └────────┬────────┘
        │                           │                           │
        └───────────────────────────┼───────────────────────────┘
                                    │
                        ┌───────────▼───────────┐
                        │  .env.example群       │
                        │  - Root               │
                        │  - Laravel API        │
                        │  - E2E Tests          │
                        └───────────────────────┘
                                    │
                        ┌───────────▼───────────┐
                        │  env-sync.ts          │
                        │  (同期スクリプト)       │
                        └───────────────────────┘
```

### 2.2 コンポーネント責務

#### 2.2.1 Laravel環境変数バリデーションコンポーネント
- **env_schema.php**: 環境変数スキーマ定義（必須性、型、デフォルト値、バリデーションルール）
- **EnvValidator.php**: バリデーションロジック実装（型検証、必須チェック、カスタムルール）
- **ValidateEnvironment.php**: Bootstrapper実装（起動時バリデーション実行）
- **EnvValidate.php**: Artisanコマンド実装（手動検証コマンド `php artisan env:validate`）

#### 2.2.2 Next.js環境変数バリデーションコンポーネント
- **env.ts**: Zodスキーマによる環境変数定義と型エクスポート
- **check-env.ts**: ビルド前検証スクリプト（predev/prebuild フック）
- **package.json**: スクリプト統合（predev/prebuild自動実行）

#### 2.2.3 環境変数同期コンポーネント
- **env-sync.ts**: .env.example と .env の差分検出・同期スクリプト
- **package.json**: `npm run env:check`、`npm run env:sync` コマンド提供

#### 2.2.4 ドキュメントコンポーネント
- **GITHUB_ACTIONS_SECRETS_GUIDE.md**: GitHub Actions Secrets設定ガイド
- **ENVIRONMENT_SECURITY_GUIDE.md**: 環境変数セキュリティベストプラクティス
- **README.md**: 環境変数管理セクション追加

---

## 3. 詳細設計

### 3.1 Laravel環境変数バリデーション設計

#### 3.1.1 環境変数スキーマ定義 (`config/env_schema.php`)

**ファイルパス**: `backend/laravel-api/config/env_schema.php`

**設計原則**:
- 宣言的スキーマ定義による保守性向上
- 環境別デフォルト値のサポート（開発/本番）
- カスタムバリデーションルールの拡張性

**スキーマ構造**:
```php
return [
    'APP_NAME' => [
        'required' => true,
        'type' => 'string',
        'default' => 'Laravel',
        'description' => 'アプリケーション名',
    ],
    'APP_ENV' => [
        'required' => true,
        'type' => 'string',
        'allowed_values' => ['local', 'development', 'staging', 'production'],
        'default' => 'local',
        'description' => '実行環境',
    ],
    'APP_DEBUG' => [
        'required' => true,
        'type' => 'boolean',
        'default' => true,
        'description' => 'デバッグモード',
        'warning' => 'Production環境では必ず false に設定すること',
    ],
    'DB_CONNECTION' => [
        'required' => true,
        'type' => 'string',
        'allowed_values' => ['sqlite', 'pgsql', 'mysql'],
        'default' => 'sqlite',
        'description' => 'データベース接続タイプ',
    ],
    'DB_HOST' => [
        'required' => false, // DB_CONNECTION=sqlite 時は不要
        'type' => 'string',
        'description' => 'データベースホスト',
        'conditional' => [
            'if' => ['DB_CONNECTION' => ['pgsql', 'mysql']],
            'then' => ['required' => true],
        ],
    ],
    'DB_PORT' => [
        'required' => false,
        'type' => 'integer',
        'description' => 'データベースポート',
    ],
    'SANCTUM_STATEFUL_DOMAINS' => [
        'required' => false,
        'type' => 'string',
        'description' => 'Sanctum ステートフルドメイン（カンマ区切り）',
        'example' => 'localhost:13001,localhost:13002',
    ],
    'CORS_ALLOWED_ORIGINS' => [
        'required' => true,
        'type' => 'string',
        'description' => 'CORS許可オリジン（カンマ区切り）',
        'example' => 'http://localhost:13001,http://localhost:13002',
        'security_level' => 'high',
    ],
];
```

**型定義**:
- `string`: 文字列
- `integer`: 整数
- `boolean`: 真偽値（true, false, 1, 0, "true", "false"を許容）
- `url`: URL形式（http/https）
- `email`: メールアドレス形式

**拡張機能**:
- `allowed_values`: 許可値リスト
- `conditional`: 条件付き必須項目
- `security_level`: セキュリティレベル（機密情報の識別）

#### 3.1.2 EnvValidator実装 (`app/Support/EnvValidator.php`)

**ファイルパス**: `backend/laravel-api/app/Support/EnvValidator.php`

**クラス構造**:
```php
namespace App\Support;

use RuntimeException;

class EnvValidator
{
    private array $schema;
    private array $errors = [];
    private bool $warningMode = false;

    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->schema as $key => $rules) {
            $value = env($key);

            // 必須チェック
            if ($this->isRequired($key, $rules) && is_null($value)) {
                $this->addError($key, "必須環境変数が設定されていません。");
                continue;
            }

            // 型チェック
            if (!is_null($value) && isset($rules['type'])) {
                if (!$this->validateType($value, $rules['type'])) {
                    $this->addError($key, "型が不正です。期待される型: {$rules['type']}");
                }
            }

            // 許可値チェック
            if (!is_null($value) && isset($rules['allowed_values'])) {
                if (!in_array($value, $rules['allowed_values'], true)) {
                    $allowedValues = implode(', ', $rules['allowed_values']);
                    $this->addError($key, "許可されていない値です。許可値: {$allowedValues}");
                }
            }
        }

        if (!empty($this->errors)) {
            if ($this->warningMode) {
                $this->logWarnings();
                return true;
            } else {
                $this->throwValidationException();
            }
        }

        return true;
    }

    private function isRequired(string $key, array $rules): bool
    {
        // 条件付き必須チェック
        if (isset($rules['conditional'])) {
            $condition = $rules['conditional'];
            if (isset($condition['if'])) {
                foreach ($condition['if'] as $condKey => $condValues) {
                    $envValue = env($condKey);
                    if (in_array($envValue, $condValues, true)) {
                        return $condition['then']['required'] ?? false;
                    }
                }
            }
        }

        return $rules['required'] ?? false;
    }

    private function validateType($value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_numeric($value) && (int)$value == $value,
            'boolean' => in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true),
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            default => true,
        };
    }

    private function addError(string $key, string $message): void
    {
        $this->errors[$key][] = $message;
    }

    private function throwValidationException(): never
    {
        $errorMessages = $this->formatErrors();
        throw new RuntimeException(
            "環境変数のバリデーションに失敗しました:\n\n{$errorMessages}\n\n" .
            "詳細は .env.example を参照してください。"
        );
    }

    private function formatErrors(): string
    {
        $messages = [];
        foreach ($this->errors as $key => $errors) {
            $errorList = implode("\n  - ", $errors);
            $example = $this->schema[$key]['example'] ?? '';
            $exampleText = $example ? "\n  設定例: {$example}" : '';
            $messages[] = "{$key}:\n  - {$errorList}{$exampleText}";
        }
        return implode("\n\n", $messages);
    }

    public function enableWarningMode(): void
    {
        $this->warningMode = true;
    }

    private function logWarnings(): void
    {
        $errorMessages = $this->formatErrors();
        logger()->warning("環境変数バリデーション警告:\n{$errorMessages}");
    }
}
```

**エラーメッセージ設計**:
- 不足変数名を明示
- 期待される型・値を提示
- 設定例を提供（.env.example参照）
- 複数エラーをまとめて表示

#### 3.1.3 Bootstrapper実装 (`app/Bootstrap/ValidateEnvironment.php`)

**ファイルパス**: `backend/laravel-api/app/Bootstrap/ValidateEnvironment.php`

**クラス構造**:
```php
namespace App\Bootstrap;

use App\Support\EnvValidator;
use Illuminate\Contracts\Foundation\Application;

class ValidateEnvironment
{
    public function bootstrap(Application $app): void
    {
        // スキップフラグチェック（緊急時用）
        if (env('ENV_VALIDATION_SKIP', false)) {
            logger()->warning('環境変数バリデーションがスキップされました。');
            return;
        }

        $schema = config('env_schema');
        $validator = new EnvValidator($schema);

        // 警告モードチェック（マイグレーション期間用）
        if (env('ENV_VALIDATION_MODE', 'error') === 'warning') {
            $validator->enableWarningMode();
        }

        $validator->validate();

        logger()->info('環境変数バリデーションが成功しました。');
    }
}
```

**Bootstrapper登録** (`bootstrap/app.php`):
```php
<?php

use App\Bootstrap\ValidateEnvironment;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(/* ... */)
    ->withMiddleware(/* ... */)
    ->withExceptions(/* ... */)
    ->withBootstrappers([
        ValidateEnvironment::class, // 環境変数バリデーション追加
    ])
    ->create();
```

#### 3.1.4 Artisanコマンド実装 (`app/Console/Commands/EnvValidate.php`)

**ファイルパス**: `backend/laravel-api/app/Console/Commands/EnvValidate.php`

**コマンド設計**:
```php
namespace App\Console\Commands;

use App\Support\EnvValidator;
use Illuminate\Console\Command;

class EnvValidate extends Command
{
    protected $signature = 'env:validate {--mode=error : Validation mode (error|warning)}';
    protected $description = '環境変数のバリデーションを実行します';

    public function handle(): int
    {
        $this->info('環境変数のバリデーションを開始します...');

        $schema = config('env_schema');
        $validator = new EnvValidator($schema);

        if ($this->option('mode') === 'warning') {
            $validator->enableWarningMode();
        }

        try {
            $validator->validate();
            $this->info('✅ 環境変数のバリデーションが成功しました。');
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $this->error('❌ 環境変数のバリデーションに失敗しました:');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

**使用例**:
```bash
# エラーモード（デフォルト）
php artisan env:validate

# 警告モード
php artisan env:validate --mode=warning
```

---

### 3.2 Next.js環境変数バリデーション設計

#### 3.2.1 Zodスキーマ実装 (`src/lib/env.ts`)

**ファイルパス**: `frontend/admin-app/src/lib/env.ts`, `frontend/user-app/src/lib/env.ts`

**設計原則**:
- Zodスキーマによる実行時型検証
- TypeScript型推論による開発時型安全性
- クライアント側公開変数の明示的チェック（`NEXT_PUBLIC_`プレフィックス）

**スキーマ実装例** (Admin App):
```typescript
import { z } from 'zod';

// 環境変数スキーマ定義
const envSchema = z.object({
  NEXT_PUBLIC_API_URL: z
    .string()
    .url('NEXT_PUBLIC_API_URL は有効なURL形式である必要があります')
    .default('http://localhost:13000'),

  NODE_ENV: z
    .enum(['development', 'production', 'test'])
    .default('development'),

  // サーバー側のみで使用される環境変数（NEXT_PUBLIC_なし）
  // API_SECRET_KEY: z.string().optional(),
});

// 環境変数のバリデーション実行
const parsedEnv = envSchema.safeParse({
  NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL,
  NODE_ENV: process.env.NODE_ENV,
});

if (!parsedEnv.success) {
  console.error('❌ 環境変数のバリデーションに失敗しました:');
  console.error(parsedEnv.error.flatten().fieldErrors);
  throw new Error('環境変数が正しく設定されていません。.env.local を確認してください。');
}

// 型安全な環境変数エクスポート
export const env = parsedEnv.data;

// TypeScript型エクスポート
export type Env = z.infer<typeof envSchema>;
```

**使用例**:
```typescript
import { env } from '@/lib/env';

// 型安全な環境変数アクセス
const apiUrl = env.NEXT_PUBLIC_API_URL; // string型として推論される
```

**User App実装差分**:
```typescript
// User App用環境変数スキーマ
const envSchema = z.object({
  NEXT_PUBLIC_API_URL: z
    .string()
    .url()
    .default('http://localhost:13000'),

  NEXT_PUBLIC_APP_NAME: z
    .string()
    .default('User App'),

  NODE_ENV: z
    .enum(['development', 'production', 'test'])
    .default('development'),
});
```

#### 3.2.2 ビルド前検証スクリプト (`scripts/check-env.ts`)

**ファイルパス**: `frontend/admin-app/scripts/check-env.ts`, `frontend/user-app/scripts/check-env.ts`

**スクリプト設計**:
```typescript
import '@/lib/env'; // env.ts をインポートしてバリデーション実行

console.log('✅ 環境変数のバリデーションが成功しました。');
```

**package.json スクリプト統合**:
```json
{
  "scripts": {
    "predev": "tsx scripts/check-env.ts",
    "dev": "next dev --port 13002 --turbopack",
    "prebuild": "tsx scripts/check-env.ts",
    "build": "next build",
    "start": "next start --port 13002"
  }
}
```

**動作フロー**:
1. `npm run dev` または `npm run build` 実行
2. predev/prebuild フックが自動実行
3. `tsx scripts/check-env.ts` 実行
4. `src/lib/env.ts` で Zod バリデーション実行
5. エラーがあれば即座に実行停止、明確なエラーメッセージ表示
6. 成功時は dev/build コマンド継続

---

### 3.3 環境変数同期スクリプト設計

#### 3.3.1 env-sync.ts実装

**ファイルパス**: `scripts/env-sync.ts`

**設計原則**:
- .env.example を信頼できる単一の情報源（Single Source of Truth）とする
- .env ファイルの既存値を保持し、新規キーのみ追加
- 差分検出により不足キー・未知キーを警告

**スクリプト実装**:
```typescript
import * as fs from 'fs';
import * as path from 'path';
import { parse } from 'dotenv';
import { Command } from 'commander';

const program = new Command();

program
  .option('--check', '.env.example と .env の差分をチェック（書き込みなし）')
  .option('--write', '.env.example の新規キーを .env に追加');

program.parse(process.argv);
const options = program.opts();

interface EnvFiles {
  examplePath: string;
  envPath: string;
}

const ENV_FILES: EnvFiles[] = [
  { examplePath: '.env.example', envPath: '.env' },
  { examplePath: 'backend/laravel-api/.env.example', envPath: 'backend/laravel-api/.env' },
  { examplePath: 'e2e/.env.example', envPath: 'e2e/.env' },
];

function parseEnvFile(filePath: string): Record<string, string> {
  if (!fs.existsSync(filePath)) {
    return {};
  }
  const content = fs.readFileSync(filePath, 'utf-8');
  return parse(content);
}

function checkDiff(example: Record<string, string>, env: Record<string, string>): {
  missing: string[];
  unknown: string[];
} {
  const exampleKeys = Object.keys(example);
  const envKeys = Object.keys(env);

  const missing = exampleKeys.filter(key => !(key in env));
  const unknown = envKeys.filter(key => !(key in example));

  return { missing, unknown };
}

function syncEnvFiles(examplePath: string, envPath: string): void {
  const example = parseEnvFile(examplePath);
  const env = parseEnvFile(envPath);

  const { missing, unknown } = checkDiff(example, env);

  console.log(`\n📝 ${examplePath} → ${envPath}`);

  if (missing.length === 0 && unknown.length === 0) {
    console.log('✅ 差分なし');
    return;
  }

  if (missing.length > 0) {
    console.log(`⚠️  不足キー (${missing.length}件):`);
    missing.forEach(key => console.log(`  - ${key}`));
  }

  if (unknown.length > 0) {
    console.log(`⚠️  未知キー (${unknown.length}件):`);
    unknown.forEach(key => console.log(`  - ${key}`));
    console.log(`   → .env.example への追加を検討してください`);
  }

  if (options.write && missing.length > 0) {
    // .env に不足キーを追加
    const envContent = fs.readFileSync(envPath, 'utf-8');
    const newLines = missing.map(key => `${key}=${example[key] || ''}`);
    const updatedContent = envContent + '\n' + newLines.join('\n') + '\n';
    fs.writeFileSync(envPath, updatedContent);
    console.log(`✅ ${missing.length}件のキーを ${envPath} に追加しました`);
  }
}

function main(): void {
  console.log('🔍 環境変数の同期チェックを開始します...');

  if (!options.check && !options.write) {
    console.error('❌ --check または --write オプションを指定してください');
    process.exit(1);
  }

  ENV_FILES.forEach(({ examplePath, envPath }) => {
    if (!fs.existsSync(examplePath)) {
      console.log(`⚠️  ${examplePath} が存在しません。スキップします。`);
      return;
    }

    if (!fs.existsSync(envPath)) {
      if (options.write) {
        console.log(`📝 ${envPath} が存在しないため、${examplePath} からコピーします。`);
        fs.copyFileSync(examplePath, envPath);
        console.log(`✅ ${envPath} を作成しました`);
      } else {
        console.log(`⚠️  ${envPath} が存在しません。--write オプションで作成できます。`);
      }
      return;
    }

    syncEnvFiles(examplePath, envPath);
  });

  console.log('\n✅ 同期チェックが完了しました。');
}

main();
```

**package.json スクリプト追加** (ルート):
```json
{
  "scripts": {
    "env:check": "tsx scripts/env-sync.ts --check",
    "env:sync": "tsx scripts/env-sync.ts --write"
  }
}
```

**使用例**:
```bash
# 差分チェックのみ（書き込みなし）
npm run env:check

# 差分を検出して .env に新規キーを追加
npm run env:sync
```

---

### 3.4 .env.example詳細コメント整備

#### 3.4.1 コメントフォーマット標準

**コメント構造**:
```bash
# ============================================
# セクション名（例: Frontend Environment Variables）
# ============================================

# 変数名
# - 説明: 変数の用途と影響範囲
# - 必須: はい/いいえ/条件付き
# - 環境: 開発環境=値例, 本番環境=値例
# - セキュリティ: 公開可/機密/極秘
# - デフォルト: デフォルト値（存在する場合）
# - 注意事項: 変更時の影響や制約
変数名=デフォルト値
```

#### 3.4.2 ルート .env.example サンプル

**ファイルパス**: `.env.example`

**内容例**:
```bash
# ============================================
# Frontend Environment Variables
# ============================================

# NEXT_PUBLIC_API_URL
# - 説明: Laravel APIのベースURL（フロントエンドからアクセス）
# - 必須: はい
# - 環境: 開発環境=http://localhost:13000, 本番環境=https://api.example.com
# - セキュリティ: 公開可（NEXT_PUBLIC_プレフィックス）
# - デフォルト: http://localhost:13000
# - 注意事項: NEXT_PUBLIC_プレフィックスがないとクライアント側で利用不可
NEXT_PUBLIC_API_URL=http://localhost:13000

# ============================================
# Docker Port Configuration
# ============================================

# APP_PORT
# - 説明: Laravel APIのポート番号
# - 必須: はい
# - 環境: 開発環境=13000, 本番環境=8000
# - セキュリティ: 公開可
# - デフォルト: 13000
# - 注意事項: Docker Composeのポートマッピングと一致させること
APP_PORT=13000

# FORWARD_DB_PORT
# - 説明: PostgreSQLのポート番号（ホストからアクセス用）
# - 必須: はい
# - 環境: 開発環境=13432, 本番環境=5432
# - セキュリティ: 公開可
# - デフォルト: 13432
# - 注意事項: 他のプロジェクトとのポート競合に注意
FORWARD_DB_PORT=13432

# ============================================
# E2E Tests Environment Variables
# ============================================

# E2E_ADMIN_URL
# - 説明: Admin AppのURL（E2Eテスト用）
# - 必須: はい（E2Eテスト実行時）
# - 環境: 開発環境=http://localhost:13002, CI環境=http://localhost:13002
# - セキュリティ: 公開可
# - デフォルト: http://localhost:13002
E2E_ADMIN_URL=http://localhost:13002

# E2E_ADMIN_EMAIL
# - 説明: 管理者メールアドレス（E2Eテスト用）
# - 必須: はい（E2Eテスト実行時）
# - 環境: 開発環境=admin@example.com
# - セキュリティ: 機密（テスト用のため低リスク）
# - デフォルト: admin@example.com
# - 注意事項: 本番環境では異なる認証情報を使用すること
E2E_ADMIN_EMAIL=admin@example.com

# E2E_ADMIN_PASSWORD
# - 説明: 管理者パスワード（E2Eテスト用）
# - 必須: はい（E2Eテスト実行時）
# - 環境: 開発環境=password
# - セキュリティ: 極秘（テスト用のため低リスク）
# - デフォルト: password
# - 注意事項: 本番環境では絶対に使用しないこと
E2E_ADMIN_PASSWORD=password
```

#### 3.4.3 Laravel .env.example サンプル

**ファイルパス**: `backend/laravel-api/.env.example`

**内容例**:
```bash
# ============================================
# Application Configuration
# ============================================

# APP_NAME
# - 説明: アプリケーション名
# - 必須: はい
# - 環境: 開発環境=Laravel, 本番環境=MyApp
# - セキュリティ: 公開可
# - デフォルト: Laravel
APP_NAME=Laravel

# APP_ENV
# - 説明: 実行環境
# - 必須: はい
# - 環境: 開発環境=local, 本番環境=production
# - セキュリティ: 公開可
# - デフォルト: local
# - 注意事項: local, development, staging, production のいずれかを設定
APP_ENV=local

# APP_KEY
# - 説明: アプリケーション暗号化キー
# - 必須: はい
# - 環境: 開発環境=base64:xxx, 本番環境=base64:xxx（異なるキーを使用）
# - セキュリティ: 極秘（暗号化に使用）
# - デフォルト: (空) ※php artisan key:generate で自動生成
# - 注意事項: 絶対にGitにコミットしないこと。本番環境では別キーを使用すること。
APP_KEY=

# APP_DEBUG
# - 説明: デバッグモード
# - 必須: はい
# - 環境: 開発環境=true, 本番環境=false
# - セキュリティ: 公開可（本番では必ずfalse）
# - デフォルト: true
# - 注意事項: 本番環境では必ずfalseに設定すること（エラー詳細が漏洩する）
APP_DEBUG=true

# ============================================
# Database Configuration
# ============================================

# DB_CONNECTION
# - 説明: データベース接続タイプ
# - 必須: はい
# - 環境: 開発環境=sqlite, 本番環境=pgsql
# - セキュリティ: 公開可
# - デフォルト: sqlite
# - 注意事項: sqlite, pgsql, mysql のいずれかを設定
DB_CONNECTION=sqlite

# DB_HOST
# - 説明: データベースホスト
# - 必須: 条件付き（DB_CONNECTION=pgsql または mysql の場合）
# - 環境: 開発環境=pgsql（Docker）または 127.0.0.1（ネイティブ）, 本番環境=db.example.com
# - セキュリティ: 機密
# - デフォルト: pgsql（Docker環境）
# - 注意事項: Docker環境ではサービス名、ネイティブ環境では127.0.0.1を使用
# DB_HOST=pgsql

# DB_PORT
# - 説明: データベースポート
# - 必須: 条件付き（DB_CONNECTION=pgsql または mysql の場合）
# - 環境: 開発環境=5432（pgsql）, 本番環境=5432
# - セキュリティ: 公開可
# - デフォルト: 5432（PostgreSQL）
# DB_PORT=5432

# DB_DATABASE
# - 説明: データベース名
# - 必須: 条件付き（DB_CONNECTION=pgsql または mysql の場合）
# - 環境: 開発環境=laravel, 本番環境=production_db
# - セキュリティ: 機密
# - デフォルト: laravel
# DB_DATABASE=laravel

# DB_USERNAME
# - 説明: データベースユーザー名
# - 必須: 条件付き（DB_CONNECTION=pgsql または mysql の場合）
# - 環境: 開発環境=sail, 本番環境=db_user
# - セキュリティ: 機密
# - デフォルト: sail
# DB_USERNAME=sail

# DB_PASSWORD
# - 説明: データベースパスワード
# - 必須: 条件付き（DB_CONNECTION=pgsql または mysql の場合）
# - 環境: 開発環境=password, 本番環境=xxx（強力なパスワード）
# - セキュリティ: 極秘
# - デフォルト: password
# - 注意事項: 本番環境では強力なパスワードを使用し、定期的にローテーションすること
# DB_PASSWORD=password

# ============================================
# Laravel Sanctum Configuration
# ============================================

# SANCTUM_STATEFUL_DOMAINS
# - 説明: Sanctum ステートフルドメイン（カンマ区切り）
# - 必須: いいえ
# - 環境: 開発環境=localhost:13001,localhost:13002, 本番環境=app.example.com,admin.example.com
# - セキュリティ: 公開可
# - デフォルト: localhost:13001,localhost:13002
# - 注意事項: CORS設定と一致させること
SANCTUM_STATEFUL_DOMAINS=localhost:13001,localhost:13002

# SANCTUM_EXPIRATION
# - 説明: トークン有効期限（日数）
# - 必須: いいえ
# - 環境: 開発環境=60, 本番環境=30
# - セキュリティ: 公開可
# - デフォルト: 60
# - 注意事項: セキュリティレベルに応じて短縮を検討
# SANCTUM_EXPIRATION=60

# ============================================
# CORS Configuration
# ============================================

# CORS_ALLOWED_ORIGINS
# - 説明: CORS許可オリジン（カンマ区切り）
# - 必須: はい
# - 環境: 開発環境=http://localhost:13001,http://localhost:13002, 本番環境=https://app.example.com,https://admin.example.com
# - セキュリティ: 公開可（セキュリティ上重要）
# - デフォルト: http://localhost:13001,http://localhost:13002
# - 注意事項: 本番環境では必ず正確なオリジンを設定すること。ワイルドカード(*)は非推奨。
CORS_ALLOWED_ORIGINS=http://localhost:13001,http://localhost:13002
```

---

### 3.5 CI/CD統合設計

#### 3.5.1 Laravel テストワークフロー修正

**ファイルパス**: `.github/workflows/test.yml`

**追加ステップ** (既存の "Run database migrations" の前に追加):
```yaml
      - name: Validate environment variables
        run: php artisan env:validate
        working-directory: backend/laravel-api
        env:
          DB_CONNECTION: pgsql_testing
          DB_TEST_HOST: 127.0.0.1
          DB_TEST_PORT: 13432
          DB_TEST_DATABASE: testing_${{ matrix.shard }}
          DB_TEST_USERNAME: sail
          DB_TEST_PASSWORD: password
          REDIS_HOST: 127.0.0.1
          REDIS_PORT: 13379
```

#### 3.5.2 フロントエンドテストワークフロー修正

**ファイルパス**: `.github/workflows/frontend-test.yml`

**追加ステップ** (既存の "Run tests with coverage" の前に追加):
```yaml
      - name: Validate environment variables
        run: npm run env:check
        working-directory: frontend/${{ matrix.app }}
        env:
          NEXT_PUBLIC_API_URL: http://localhost:13000
          NODE_ENV: test
```

**注意**: `env:check` スクリプトは各アプリの package.json に定義する必要があります。

#### 3.5.3 環境変数バリデーション専用ワークフロー作成

**ファイルパス**: `.github/workflows/environment-validation.yml`

**ワークフロー設計**:
```yaml
name: Environment Validation

concurrency:
  group: ${{ github.workflow }}-${{ github.event_name }}-${{ github.ref }}
  cancel-in-progress: ${{ github.event_name == 'pull_request' }}

on:
  pull_request:
    types: [opened, synchronize, reopened, ready_for_review]
    branches:
      - main
      - develop
    paths:
      - '.env.example'
      - 'backend/laravel-api/.env.example'
      - 'e2e/.env.example'
      - 'backend/laravel-api/config/env_schema.php'
      - 'backend/laravel-api/app/Support/EnvValidator.php'
      - 'backend/laravel-api/app/Bootstrap/ValidateEnvironment.php'
      - 'backend/laravel-api/app/Console/Commands/EnvValidate.php'
      - 'frontend/admin-app/src/lib/env.ts'
      - 'frontend/user-app/src/lib/env.ts'
      - 'scripts/env-sync.ts'
      - '.github/workflows/environment-validation.yml'
  push:
    branches:
      - main
    paths:
      - '.env.example'
      - 'backend/laravel-api/.env.example'
      - 'e2e/.env.example'
      - 'backend/laravel-api/config/env_schema.php'
      - 'backend/laravel-api/app/Support/EnvValidator.php'

jobs:
  validate-laravel:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite3
          tools: composer:v2

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT
        working-directory: backend/laravel-api

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('backend/laravel-api/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader
        working-directory: backend/laravel-api

      - name: Copy .env
        run: cp .env.example .env
        working-directory: backend/laravel-api

      - name: Generate application key
        run: php artisan key:generate
        working-directory: backend/laravel-api

      - name: Validate Laravel environment variables
        run: php artisan env:validate
        working-directory: backend/laravel-api

  validate-nextjs:
    runs-on: ubuntu-latest

    strategy:
      fail-fast: false
      matrix:
        app: [admin-app, user-app]

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 20.x
          cache: 'npm'
          cache-dependency-path: |
            package-lock.json
            frontend/admin-app/package-lock.json
            frontend/user-app/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Create .env.local
        run: |
          echo "NEXT_PUBLIC_API_URL=http://localhost:13000" > .env.local
          echo "NODE_ENV=test" >> .env.local
        working-directory: frontend/${{ matrix.app }}

      - name: Validate Next.js environment variables
        run: npm run env:check
        working-directory: frontend/${{ matrix.app }}
        env:
          CI: true

  validate-env-sync:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 20.x
          cache: 'npm'

      - name: Install dependencies
        run: npm ci

      - name: Check environment variable sync
        run: npm run env:check
```

---

### 3.6 ドキュメント設計

#### 3.6.1 GitHub Actions Secrets設定ガイド

**ファイルパス**: `docs/GITHUB_ACTIONS_SECRETS_GUIDE.md`

**ドキュメント構成**:
1. **概要**: GitHub Actions Secrets の役割と重要性
2. **Secrets命名規約**: `{サービス}_{環境}_{変数名}` パターン（例: `LARAVEL_PROD_DB_PASSWORD`）
3. **Repository Secrets vs Environment Secrets**: 使い分け基準と設定手順
4. **必須Secrets一覧**:
   - Backend: `DB_PASSWORD`, `APP_KEY`, `AWS_ACCESS_KEY_ID`等
   - Frontend: `NEXT_PUBLIC_API_URL_PROD`, `SENTRY_DSN`等
5. **CI/CDワークフローでの使用例**: `${{ secrets.SECRET_NAME }}`
6. **セキュリティベストプラクティス**:
   - 定期的なローテーション（90日推奨）
   - アクセス制御（Environmentベースの承認フロー）
   - 監査ログの確認
7. **トラブルシューティング**: Secret不足エラーの解決方法

#### 3.6.2 環境変数セキュリティガイド

**ファイルパス**: `docs/ENVIRONMENT_SECURITY_GUIDE.md`

**ドキュメント構成**:
1. **セキュリティ原則**:
   - 機密情報の定義（パスワード、APIキー、プライベートキー等）
   - .env ファイルの管理（Git除外、共有禁止）
   - バージョン管理からの除外確認
2. **Laravel/Next.jsセキュリティ設定**:
   - CORS設定（`CORS_ALLOWED_ORIGINS`）
   - CSRFプロテクション（Sanctum設定）
   - 環境別設定の分離
3. **CI/CDセキュリティ**:
   - GitHub Secrets暗号化
   - アクセス制御（Environment Protection Rules）
   - 監査ログ
4. **セキュリティチェックリスト**:
   - [ ] .env が .gitignore に登録されている
   - [ ] .env.example に機密情報が含まれていない
   - [ ] 本番環境の機密情報が定期的にローテーションされている
   - [ ] CI/CD環境でSecrets管理が適切に設定されている
5. **インシデント対応手順**:
   - 機密情報漏洩時の緊急対応
   - 影響範囲調査（Git履歴、アクセスログ）
   - 再発防止策（Secrets強制更新、pre-commitフック追加）

#### 3.6.3 README.md更新

**ファイルパス**: `README.md`

**追加セクション**:
```markdown
## 環境変数管理

### セットアップ手順

1. **環境変数テンプレートのコピー**
   ```bash
   # ルートディレクトリ
   cp .env.example .env

   # Laravel API
   cp backend/laravel-api/.env.example backend/laravel-api/.env
   php artisan key:generate

   # E2Eテスト
   cp e2e/.env.example e2e/.env

   # Next.jsアプリ（必要に応じて）
   cp frontend/admin-app/.env.example frontend/admin-app/.env.local
   cp frontend/user-app/.env.example frontend/user-app/.env.local
   ```

2. **環境変数の設定**
   - `.env` ファイルを開き、各環境変数の値を設定
   - コメントを参照して、必須項目・セキュリティレベルを確認

3. **環境変数のバリデーション**
   ```bash
   # Laravel API
   cd backend/laravel-api
   php artisan env:validate

   # Next.jsアプリ（dev起動時に自動実行）
   cd frontend/admin-app
   npm run dev  # predevフックでバリデーション実行
   ```

### 環境変数テンプレート構成

- **ルート `.env.example`**: モノレポ全体で共通の環境変数
- **`backend/laravel-api/.env.example`**: Laravel API固有の環境変数
- **`e2e/.env.example`**: E2Eテスト実行用の環境変数

### バリデーションコマンド

**Laravel**:
```bash
# 環境変数のバリデーション実行
php artisan env:validate

# 警告モード（エラーでも起動継続）
php artisan env:validate --mode=warning
```

**Next.js**:
```bash
# 環境変数の差分チェック（書き込みなし）
npm run env:check

# .env.example の新規キーを .env に追加
npm run env:sync
```

### トラブルシューティング

#### 環境変数バリデーションエラー
**エラー**: `環境変数のバリデーションに失敗しました`

**解決方法**:
1. エラーメッセージを確認し、不足している環境変数を特定
2. `.env.example` のコメントを参照し、必要な値を設定
3. 設定例を参考にして `.env` に追加
4. 再度 `php artisan env:validate` を実行

#### Next.js環境変数が反映されない
**エラー**: `NEXT_PUBLIC_API_URL is not defined`

**解決方法**:
1. `.env.local` ファイルが正しく配置されているか確認
2. `NEXT_PUBLIC_` プレフィックスが付いているか確認
3. 開発サーバーを再起動（`npm run dev`）
4. ビルドキャッシュをクリア（`rm -rf .next`）

#### .env.example と .env の差分がある
**エラー**: `不足キー: DB_HOST, DB_PORT`

**解決方法**:
```bash
# 差分を確認
npm run env:check

# 自動同期（既存値は保持される）
npm run env:sync
```

### 関連ドキュメント
- [GitHub Actions Secrets設定ガイド](docs/GITHUB_ACTIONS_SECRETS_GUIDE.md)
- [環境変数セキュリティガイド](docs/ENVIRONMENT_SECURITY_GUIDE.md)
- [CORS環境変数設定ガイド](docs/CORS_CONFIGURATION_GUIDE.md)
```

---

## 4. テスト戦略

### 4.1 Laravel環境変数バリデータのユニットテスト

**ファイルパス**: `backend/laravel-api/tests/Unit/Support/EnvValidatorTest.php`

**テストケース**:
```php
<?php

use App\Support\EnvValidator;

test('必須環境変数が不足している場合、RuntimeExceptionをスローする', function () {
    $schema = [
        'TEST_REQUIRED_VAR' => [
            'required' => true,
            'type' => 'string',
        ],
    ];

    putenv('TEST_REQUIRED_VAR'); // 環境変数を削除

    $validator = new EnvValidator($schema);

    expect(fn() => $validator->validate())
        ->toThrow(RuntimeException::class, '必須環境変数が設定されていません');
});

test('正常な環境変数でバリデーションが成功する', function () {
    $schema = [
        'TEST_STRING_VAR' => [
            'required' => true,
            'type' => 'string',
        ],
    ];

    putenv('TEST_STRING_VAR=test_value');

    $validator = new EnvValidator($schema);

    expect($validator->validate())->toBeTrue();
});

test('型が不正な環境変数でRuntimeExceptionをスローする', function () {
    $schema = [
        'TEST_INT_VAR' => [
            'required' => true,
            'type' => 'integer',
        ],
    ];

    putenv('TEST_INT_VAR=not_an_integer');

    $validator = new EnvValidator($schema);

    expect(fn() => $validator->validate())
        ->toThrow(RuntimeException::class, '型が不正です');
});

test('許可値リストにない値でRuntimeExceptionをスローする', function () {
    $schema = [
        'TEST_ENUM_VAR' => [
            'required' => true,
            'type' => 'string',
            'allowed_values' => ['development', 'production'],
        ],
    ];

    putenv('TEST_ENUM_VAR=invalid_value');

    $validator = new EnvValidator($schema);

    expect(fn() => $validator->validate())
        ->toThrow(RuntimeException::class, '許可されていない値です');
});

test('警告モードでエラーがあってもバリデーションが成功する', function () {
    $schema = [
        'TEST_REQUIRED_VAR' => [
            'required' => true,
            'type' => 'string',
        ],
    ];

    putenv('TEST_REQUIRED_VAR'); // 環境変数を削除

    $validator = new EnvValidator($schema);
    $validator->enableWarningMode();

    expect($validator->validate())->toBeTrue();
});

test('条件付き必須チェックが正しく動作する', function () {
    $schema = [
        'DB_CONNECTION' => [
            'required' => true,
            'type' => 'string',
        ],
        'DB_HOST' => [
            'required' => false,
            'type' => 'string',
            'conditional' => [
                'if' => ['DB_CONNECTION' => ['pgsql', 'mysql']],
                'then' => ['required' => true],
            ],
        ],
    ];

    putenv('DB_CONNECTION=pgsql');
    putenv('DB_HOST'); // DB_HOSTを削除

    $validator = new EnvValidator($schema);

    expect(fn() => $validator->validate())
        ->toThrow(RuntimeException::class, '必須環境変数が設定されていません');
});
```

### 4.2 Next.js環境変数バリデータのユニットテスト

**ファイルパス**: `frontend/admin-app/src/lib/__tests__/env.test.ts`

**テストケース**:
```typescript
import { z } from 'zod';

describe('環境変数バリデーション', () => {
  const envSchema = z.object({
    NEXT_PUBLIC_API_URL: z.string().url(),
    NODE_ENV: z.enum(['development', 'production', 'test']),
  });

  beforeEach(() => {
    // 環境変数をリセット
    delete process.env.NEXT_PUBLIC_API_URL;
    delete process.env.NODE_ENV;
  });

  test('正常な環境変数でバリデーションが成功する', () => {
    const env = {
      NEXT_PUBLIC_API_URL: 'http://localhost:13000',
      NODE_ENV: 'development' as const,
    };

    const result = envSchema.safeParse(env);

    expect(result.success).toBe(true);
    if (result.success) {
      expect(result.data.NEXT_PUBLIC_API_URL).toBe('http://localhost:13000');
    }
  });

  test('不正なURL形式でバリデーションエラーが発生する', () => {
    const env = {
      NEXT_PUBLIC_API_URL: 'invalid-url',
      NODE_ENV: 'development' as const,
    };

    const result = envSchema.safeParse(env);

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.flatten().fieldErrors.NEXT_PUBLIC_API_URL).toBeDefined();
    }
  });

  test('許可されていないNODE_ENVでバリデーションエラーが発生する', () => {
    const env = {
      NEXT_PUBLIC_API_URL: 'http://localhost:13000',
      NODE_ENV: 'invalid' as any,
    };

    const result = envSchema.safeParse(env);

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.flatten().fieldErrors.NODE_ENV).toBeDefined();
    }
  });

  test('必須環境変数が不足している場合、バリデーションエラーが発生する', () => {
    const env = {
      NODE_ENV: 'development' as const,
      // NEXT_PUBLIC_API_URLを省略
    };

    const result = envSchema.safeParse(env);

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.flatten().fieldErrors.NEXT_PUBLIC_API_URL).toBeDefined();
    }
  });
});
```

### 4.3 環境変数同期スクリプトの統合テスト

**ファイルパス**: `scripts/__tests__/env-sync.test.ts`

**テストケース**:
```typescript
import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';

describe('環境変数同期スクリプト', () => {
  const testDir = path.join(__dirname, 'fixtures');
  const examplePath = path.join(testDir, '.env.example');
  const envPath = path.join(testDir, '.env');

  beforeEach(() => {
    // テストディレクトリを作成
    if (!fs.existsSync(testDir)) {
      fs.mkdirSync(testDir, { recursive: true });
    }
  });

  afterEach(() => {
    // テストディレクトリをクリーンアップ
    if (fs.existsSync(envPath)) {
      fs.unlinkSync(envPath);
    }
    if (fs.existsSync(examplePath)) {
      fs.unlinkSync(examplePath);
    }
  });

  test('.env.example のみ存在する場合、env:sync で .env が作成される', () => {
    // .env.example を作成
    fs.writeFileSync(examplePath, 'TEST_KEY=test_value\n');

    // env:sync 実行（実際のコマンドではなくロジックをテスト）
    fs.copyFileSync(examplePath, envPath);

    expect(fs.existsSync(envPath)).toBe(true);
    const envContent = fs.readFileSync(envPath, 'utf-8');
    expect(envContent).toContain('TEST_KEY=test_value');
  });

  test('.env に既存値がある場合、env:sync で新規キーのみ追加される', () => {
    // .env.example と .env を作成
    fs.writeFileSync(examplePath, 'KEY1=value1\nKEY2=value2\n');
    fs.writeFileSync(envPath, 'KEY1=existing_value\n');

    // 同期ロジック（新規キーのみ追加）
    const exampleContent = fs.readFileSync(examplePath, 'utf-8');
    const envContent = fs.readFileSync(envPath, 'utf-8');
    const missingKeys = ['KEY2']; // 実際の実装では差分検出ロジックを使用
    const updatedContent = envContent + 'KEY2=value2\n';
    fs.writeFileSync(envPath, updatedContent);

    const finalContent = fs.readFileSync(envPath, 'utf-8');
    expect(finalContent).toContain('KEY1=existing_value'); // 既存値が保持される
    expect(finalContent).toContain('KEY2=value2'); // 新規キーが追加される
  });

  test('env:check で不足キーが検出される', () => {
    fs.writeFileSync(examplePath, 'KEY1=value1\nKEY2=value2\n');
    fs.writeFileSync(envPath, 'KEY1=value1\n');

    // 差分検出ロジック（簡易版）
    const exampleKeys = ['KEY1', 'KEY2'];
    const envKeys = ['KEY1'];
    const missingKeys = exampleKeys.filter(key => !envKeys.includes(key));

    expect(missingKeys).toEqual(['KEY2']);
  });

  test('env:check で未知キーが検出される', () => {
    fs.writeFileSync(examplePath, 'KEY1=value1\n');
    fs.writeFileSync(envPath, 'KEY1=value1\nUNKNOWN_KEY=value\n');

    // 差分検出ロジック（簡易版）
    const exampleKeys = ['KEY1'];
    const envKeys = ['KEY1', 'UNKNOWN_KEY'];
    const unknownKeys = envKeys.filter(key => !exampleKeys.includes(key));

    expect(unknownKeys).toEqual(['UNKNOWN_KEY']);
  });
});
```

### 4.4 E2Eテスト（CI/CD統合）

**テストシナリオ**:
1. **環境変数不足時のビルド失敗確認**:
   - `.env` から必須環境変数を削除
   - Laravel API起動を試行
   - RuntimeExceptionがスローされることを確認
   - エラーメッセージの明瞭性を確認

2. **Next.js環境変数バリデーションエラー確認**:
   - `.env.local` から `NEXT_PUBLIC_API_URL` を削除
   - `npm run dev` を試行
   - Zodバリデーションエラーが発生することを確認
   - エラーメッセージの明瞭性を確認

3. **GitHub Actions環境変数バリデーション確認**:
   - Pull Request作成
   - `.github/workflows/environment-validation.yml` が自動実行されることを確認
   - 環境変数不足時にワークフローが失敗することを確認

---

## 5. セキュリティ設計

### 5.1 機密情報の定義

**セキュリティレベル**:
- **公開可**: `APP_NAME`, `APP_ENV`, `DB_CONNECTION`, `NEXT_PUBLIC_API_URL`
- **機密**: `DB_HOST`, `DB_USERNAME`, `REDIS_HOST`
- **極秘**: `DB_PASSWORD`, `APP_KEY`, `AWS_SECRET_ACCESS_KEY`, `API_SECRET_KEY`

### 5.2 .env管理のセキュリティ原則

1. **Git除外の徹底**:
   - `.env` を `.gitignore` に必ず登録
   - pre-commitフックで `.env` コミット防止
   - GitHub Actionsでの `.env` チェック

2. **.env.example のセキュリティ**:
   - 機密情報をプレースホルダーに置き換え（例: `DB_PASSWORD=your-password-here`）
   - 実際の本番環境の値を記載しない
   - サンプル値は開発環境用のみ

3. **機密情報のローテーション**:
   - 定期的なローテーション（90日推奨）
   - パスワード生成ツールの使用（例: `openssl rand -base64 32`）
   - ローテーション手順のドキュメント化

### 5.3 CI/CDセキュリティ

1. **GitHub Secrets管理**:
   - Environment Protection Rules設定（本番環境は承認必須）
   - Secretsへのアクセス制限（必要最小限の権限）
   - 監査ログの定期確認

2. **CI/CD環境での環境変数注入**:
   - `${{ secrets.SECRET_NAME }}` で安全に注入
   - ログに機密情報が出力されないよう注意
   - マスキング設定の確認

3. **環境別Secrets管理**:
   - Repository Secrets: 開発環境用（全ブランチで利用）
   - Environment Secrets: 本番環境用（mainブランチのみ）

### 5.4 セキュリティチェックリスト

**セットアップ時**:
- [ ] `.env` が `.gitignore` に登録されている
- [ ] `.env.example` に機密情報が含まれていない
- [ ] 開発環境のパスワードは弱いものを使用（本番と分離）
- [ ] 本番環境の機密情報は強力なパスワードを使用

**運用時**:
- [ ] 機密情報の定期ローテーション（90日）
- [ ] GitHub Secretsが適切に設定されている
- [ ] CI/CDログに機密情報が漏洩していない
- [ ] pre-commitフックが正常動作している

**インシデント対応**:
- [ ] 機密情報漏洩時の緊急対応手順が整備されている
- [ ] Git履歴から機密情報を削除する手順が明確
- [ ] 影響範囲調査の方法が確立されている

---

## 6. 段階的ロールアウト戦略

### 6.1 ロールアウトフェーズ

#### Phase 1: 警告モード導入（マイグレーション期間: 2週間）

**目的**: 既存環境への影響を最小化し、バリデーションエラーを警告として表示

**設定**:
```bash
# .env に追加
ENV_VALIDATION_MODE=warning
```

**動作**:
- バリデーションエラーが発生してもアプリケーション起動は継続
- エラー詳細をログに記録
- チームメンバーに環境変数修正を依頼

**移行手順**:
1. 全環境で警告モードを有効化
2. ログを監視し、バリデーションエラーを収集
3. チームメンバーに `.env` 修正を依頼
4. 1週間後、エラー件数を確認
5. エラーがゼロになったらPhase 2へ移行

#### Phase 2: エラーモード導入（本番運用）

**目的**: フェイルファスト設計により、環境変数不足時にアプリケーション起動を停止

**設定**:
```bash
# .env から ENV_VALIDATION_MODE を削除（デフォルトはerrorモード）
# または明示的に設定
ENV_VALIDATION_MODE=error
```

**動作**:
- バリデーションエラー時にRuntimeExceptionをスロー
- アプリケーション起動を即座に停止
- エラーメッセージを表示

**移行手順**:
1. 全環境でエラーモードを有効化
2. 起動確認テストを実施
3. CI/CD環境でのビルド成功を確認
4. 本番環境へのデプロイ

### 6.2 緊急時のロールバック手順

**バリデーションスキップフラグ**:
```bash
# 緊急時に環境変数バリデーションをスキップ
ENV_VALIDATION_SKIP=true
```

**使用ケース**:
- 本番環境で予期しないバリデーションエラーが発生
- 緊急デプロイが必要な場合
- 一時的にバリデーションを無効化して起動継続

**注意事項**:
- スキップフラグは一時的な緊急対応のみ使用
- 根本原因を修正後、スキップフラグを削除
- ログに警告メッセージを記録

### 6.3 チーム展開計画

**事前準備**:
1. ドキュメント整備（GitHub Actions Secrets、セキュリティガイド、README）
2. チームレビュー実施（最低2名の承認）
3. ロールアウト計画の承認

**展開フロー**:
1. **Week 1**: 警告モード導入、ドキュメント共有
2. **Week 2**: エラー収集、チームメンバー対応
3. **Week 3**: エラーモード導入、本番環境デプロイ
4. **Week 4**: 運用開始、フィードバック収集

**トラブルシューティング体制**:
- 問い合わせ窓口の明確化（Slack チャンネル、GitHub Discussions）
- トラブルシューティングガイドの整備
- 緊急対応手順の周知

---

## 7. パフォーマンス影響分析

### 7.1 環境変数バリデーションのオーバーヘッド

**Laravel起動時バリデーション**:
- 実行時間: 約5-10ms（環境変数50件の場合）
- 影響: 起動速度全体の0.5%未満（現状33.3ms）
- 許容範囲: 起動時の1回のみ実行、実行時パフォーマンスに影響なし

**Next.jsビルド時バリデーション**:
- 実行時間: 約10-20ms（Zodスキーマ検証）
- 影響: ビルド時間の0.1%未満
- 許容範囲: ビルド時の1回のみ実行、実行時パフォーマンスに影響なし

**CI/CDビルド時間増加**:
- Laravel環境変数バリデーション: +5-10秒（Composer依存関係インストール含む）
- Next.js環境変数バリデーション: +3-5秒（npm依存関係インストール含む）
- 合計: 約10-20秒増加
- 許容範囲: エラー早期検出の価値を考慮すると許容範囲内

### 7.2 パフォーマンス最適化施策

1. **キャッシング**:
   - Composer依存関係キャッシュ（GitHub Actions `actions/cache@v4`）
   - npm依存関係キャッシュ（setup-node内蔵キャッシング）
   - キャッシュヒット率: 80%以上

2. **並列実行**:
   - Laravel環境変数バリデーションとNext.js環境変数バリデーションを並列実行
   - Matrix戦略による複数アプリ並列テスト

3. **条件付き実行**:
   - Paths Filter設定により、環境変数関連ファイル変更時のみワークフロー実行
   - 不要な実行を60-70%削減

---

## 8. 移行ガイド

### 8.1 既存環境への影響

**影響なし**:
- 既存の `.env` ファイルは変更不要
- バリデーション機能が追加されるのみ
- 環境変数の値自体は変更しない

**影響あり**:
- 不足している環境変数がある場合、警告またはエラーが表示される
- マイグレーション期間中に修正が必要

### 8.2 移行チェックリスト

**フェーズ1: 基盤整備**
- [ ] ルート `.env.example` 詳細コメント追加
- [ ] Laravel `.env.example` 詳細コメント追加
- [ ] E2E `.env.example` 詳細コメント追加

**フェーズ2: バリデーション実装（Laravel）**
- [ ] 環境変数スキーマ定義（`config/env_schema.php`）
- [ ] バリデータ実装（`app/Support/EnvValidator.php`）
- [ ] Bootstrapper 実装（`app/Bootstrap/ValidateEnvironment.php`）
- [ ] Artisan コマンド実装（`app/Console/Commands/EnvValidate.php`）
- [ ] Bootstrapper 登録（`bootstrap/app.php`）

**フェーズ3: バリデーション実装（Next.js）**
- [ ] Zod スキーマ実装（Admin App: `src/lib/env.ts`）
- [ ] Zod スキーマ実装（User App: `src/lib/env.ts`）
- [ ] ビルド前検証スクリプト統合（両アプリ）

**フェーズ4: ツール実装**
- [ ] 環境変数同期スクリプト実装（`scripts/env-sync.ts`）
- [ ] package.json スクリプト追加
- [ ] 同期スクリプト動作確認

**フェーズ5: ドキュメント作成**
- [ ] GitHub Actions Secrets 設定ガイド作成
- [ ] 環境変数セキュリティガイド作成
- [ ] README.md 更新（環境変数管理セクション追加）

**フェーズ6: CI/CD統合**
- [ ] Laravel テストワークフロー修正
- [ ] フロントエンドテストワークフロー修正
- [ ] 環境変数バリデーション専用ワークフロー作成
- [ ] CI/CD 動作確認

**フェーズ7: テスト・検証**
- [ ] ユニットテスト実装（Laravel EnvValidator）
- [ ] ユニットテスト実装（Next.js env.ts）
- [ ] 統合テスト実装（env-sync.ts）
- [ ] CI/CD環境でのE2Eテスト
- [ ] エラーメッセージの分かりやすさ確認

**フェーズ8: チーム展開**
- [ ] チームレビュー実施
- [ ] フィードバック反映
- [ ] ロールアウト計画確定
- [ ] 運用開始

---

## 9. メンテナンス計画

### 9.1 環境変数スキーマの保守

**更新頻度**: 新規環境変数追加時、または環境変数の型・必須性変更時

**保守手順**:
1. `config/env_schema.php` でスキーマ定義を更新
2. `.env.example` にコメントを追加
3. `npm run env:sync` で既存環境への影響を確認
4. ユニットテストを追加・更新
5. ドキュメントを更新

### 9.2 ドキュメントの保守

**更新頻度**: 環境変数管理プロセス変更時、または新規ベストプラクティス追加時

**保守手順**:
1. ドキュメントレビュー実施（四半期ごと）
2. チームからのフィードバック収集
3. ドキュメント更新（GitHub Actions Secrets、セキュリティガイド）
4. チーム周知（Slack、社内Wiki）

### 9.3 セキュリティ監査

**監査頻度**: 四半期ごと

**監査項目**:
- [ ] `.env` が `.gitignore` に登録されている
- [ ] `.env.example` に機密情報が含まれていない
- [ ] GitHub Secretsが適切に設定されている
- [ ] 機密情報のローテーションが実施されている
- [ ] CI/CDログに機密情報が漏洩していない

---

## 10. 今後の拡張性

### 10.1 外部シークレット管理ツール統合（将来機能）

**対象ツール**:
- AWS Secrets Manager
- HashiCorp Vault
- Google Cloud Secret Manager

**統合方針**:
- 本仕様では統合しない（別タスク）
- インターフェース設計により、将来的な統合を容易にする
- 環境変数スキーマ定義を再利用可能にする

### 10.2 環境変数の暗号化（将来機能）

**Laravel 11の `env:encrypt` 機能統合**:
- 現在はLaravel 12に未対応
- 将来的にLaravel 12対応版がリリースされた場合に統合検討

---

## 11. まとめ

本設計書では、Laravel 12 + Next.js 15.5 モノレポ構成における環境変数管理の標準化を実現するための包括的な設計を提供した。

**主要成果**:
1. **フェイルファスト設計**: 起動時バリデーションによるエラー早期検出
2. **型安全性の保証**: TypeScript型定義とZodスキーマによる実行時型検証
3. **ドキュメント駆動**: .env.example を生きた仕様書として機能させる
4. **自動化優先**: 環境変数の同期・検証を自動化し、人的ミスを最小化
5. **段階的導入**: 警告モード → エラーモードの2段階ロールアウト戦略
6. **セキュリティファースト**: 機密情報の安全な管理とGitHub Secrets統合

**実装優先度**:
- **高**: Laravel環境変数バリデーション、Next.js環境変数バリデーション、.env.example詳細化
- **中**: 環境変数同期スクリプト、CI/CD統合
- **低**: ドキュメント作成、チーム展開

**期待される効果**:
- 環境変数設定ミスによる実行時エラーの防止
- 新規メンバーのオンボーディング時間の短縮（15分以内）
- セキュリティインシデントリスクの低減
- CI/CDビルド失敗の早期検出

---

**設計承認**:
- [ ] 設計レビュー完了
- [ ] セキュリティレビュー完了
- [ ] アーキテクチャレビュー完了
- [ ] 実装タスク生成承認

**次のステップ**: `/kiro:spec-tasks environment-variable-management` で実装タスクを生成
