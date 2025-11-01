#!/usr/bin/env node

/**
 * エラーコード定義からTypeScript型定義とPHP Enumを自動生成するスクリプト
 *
 * Usage:
 *   node scripts/generate-error-types.js
 *
 * Generates:
 *   - frontend/admin-app/src/types/error-codes.ts
 *   - frontend/user-app/src/types/error-codes.ts
 *   - backend/laravel-api/app/Enums/ErrorCode.php
 */

const fs = require('fs');
const path = require('path');

// ファイルパス
const ERROR_CODES_PATH = path.join(__dirname, '../shared/error-codes.json');
const ADMIN_APP_OUTPUT = path.join(__dirname, '../frontend/admin-app/src/types/error-codes.ts');
const USER_APP_OUTPUT = path.join(__dirname, '../frontend/user-app/src/types/error-codes.ts');
const PHP_ENUM_OUTPUT = path.join(__dirname, '../backend/laravel-api/app/Enums/ErrorCode.php');

// エラーコード定義を読み込み
let errorCodes;
try {
  errorCodes = JSON.parse(fs.readFileSync(ERROR_CODES_PATH, 'utf8'));
} catch (error) {
  console.error('❌ エラーコード定義ファイルの読み込みに失敗:', error.message);
  process.exit(1);
}

/**
 * TypeScript型定義を生成
 */
function generateTypeScriptTypes(errorCodes) {
  const codes = Object.keys(errorCodes);
  const categories = [...new Set(Object.values(errorCodes).map(e => e.category))];

  const typeScript = `/**
 * エラーコード型定義
 *
 * このファイルは自動生成されます。手動で編集しないでください。
 * 生成元: shared/error-codes.json
 * 生成コマンド: npm run generate:error-types
 *
 * @generated
 */

/**
 * エラーカテゴリー
 */
export type ErrorCategory = ${categories.map(c => `'${c}'`).join(' | ')};

/**
 * エラーコード
 */
export type ErrorCode = ${codes.map(c => `'${c}'`).join('\n  | ')};

/**
 * RFC 7807 Problem Details 型定義
 */
export interface RFC7807Problem {
  /** RFC 7807 type URI */
  type: string;
  /** 人間が読めるエラータイトル */
  title: string;
  /** HTTPステータスコード */
  status: number;
  /** エラーの詳細説明 */
  detail: string;
  /** エラーコード (DOMAIN-SUBDOMAIN-CODE形式) */
  error_code: ErrorCode;
  /** Request ID (トレーサビリティ用) */
  trace_id: string;
  /** エラーが発生したリソースのURI (オプション) */
  instance?: string;
  /** エラー発生時刻のタイムスタンプ (オプション) */
  timestamp?: string;
  /** バリデーションエラーの詳細 (オプション) */
  errors?: Record<string, string[]>;
}

/**
 * エラーコード定義
 */
export interface ErrorCodeDefinition {
  code: ErrorCode;
  http_status: number;
  type: string;
  default_message: string;
  translation_key: string;
  category: ErrorCategory;
  description?: string;
  resolution?: string;
}

/**
 * エラーコード定義マップ
 */
export const ERROR_CODE_DEFINITIONS: Record<ErrorCode, ErrorCodeDefinition> = ${JSON.stringify(errorCodes, null, 2)} as const;

/**
 * カテゴリー別エラーコード
 */
export const ERROR_CODES_BY_CATEGORY: Record<ErrorCategory, ErrorCode[]> = {
${categories.map(category => {
  const categoryErrorCodes = codes.filter(code => errorCodes[code].category === category);
  return `  ${category}: [${categoryErrorCodes.map(c => `'${c}'`).join(', ')}],`;
}).join('\n')}
};

/**
 * HTTPステータスコードからエラーコードを取得
 */
export function getErrorCodesByStatus(status: number): ErrorCode[] {
  return Object.entries(ERROR_CODE_DEFINITIONS)
    .filter(([_, def]) => def.http_status === status)
    .map(([code]) => code as ErrorCode);
}

/**
 * エラーコードからエラーコード定義を取得
 */
export function getErrorCodeDefinition(code: ErrorCode): ErrorCodeDefinition | undefined {
  return ERROR_CODE_DEFINITIONS[code];
}
`;

  return typeScript;
}

/**
 * PHP Enumを生成
 */
function generatePHPEnum(errorCodes) {
  const codes = Object.keys(errorCodes);
  const categories = [...new Set(Object.values(errorCodes).map(e => e.category))];

  const phpEnum = `<?php

declare(strict_types=1);

namespace App\\Enums;

/**
 * エラーコードEnum
 *
 * このファイルは自動生成されます。手動で編集しないでください。
 * 生成元: shared/error-codes.json
 * 生成コマンド: npm run generate:error-types
 *
 * @generated
 */
enum ErrorCode: string
{
${codes.map(code => {
  const def = errorCodes[code];
  return `    /** ${def.description || def.default_message} */
    case ${code.replace(/-/g, '_')} = '${code}';`;
}).join('\n\n')}

    /**
     * HTTPステータスコードを取得
     */
    public function getHttpStatus(): int
    {
        return match ($this) {
${codes.map(code => {
  const def = errorCodes[code];
  return `            self::${code.replace(/-/g, '_')} => ${def.http_status},`;
}).join('\n')}
        };
    }

    /**
     * RFC 7807 type URIを取得
     */
    public function getType(): string
    {
        return match ($this) {
${codes.map(code => {
  const def = errorCodes[code];
  return `            self::${code.replace(/-/g, '_')} => '${def.type}',`;
}).join('\n')}
        };
    }

    /**
     * デフォルトメッセージを取得
     */
    public function getDefaultMessage(): string
    {
        return match ($this) {
${codes.map(code => {
  const def = errorCodes[code];
  return `            self::${code.replace(/-/g, '_')} => '${def.default_message.replace(/'/g, "\\'")}',`;
}).join('\n')}
        };
    }

    /**
     * 翻訳キーを取得
     */
    public function getTranslationKey(): string
    {
        return match ($this) {
${codes.map(code => {
  const def = errorCodes[code];
  return `            self::${code.replace(/-/g, '_')} => '${def.translation_key}',`;
}).join('\n')}
        };
    }

    /**
     * カテゴリーを取得
     */
    public function getCategory(): ErrorCategory
    {
        return match ($this) {
${codes.map(code => {
  const def = errorCodes[code];
  return `            self::${code.replace(/-/g, '_')} => ErrorCategory::${def.category},`;
}).join('\n')}
        };
    }

    /**
     * エラーコード文字列から対応するEnumケースを取得
     */
    public static function fromString(string $code): ?self
    {
        return match ($code) {
${codes.map(code => {
  return `            '${code}' => self::${code.replace(/-/g, '_')},`;
}).join('\n')}
            default => null,
        };
    }
}
`;

  // ErrorCategory Enumも生成
  const phpCategoryEnum = `<?php

declare(strict_types=1);

namespace App\\Enums;

/**
 * エラーカテゴリーEnum
 *
 * @generated
 */
enum ErrorCategory: string
{
    case AUTH = 'AUTH';
    case VAL = 'VAL';
    case BIZ = 'BIZ';
    case INFRA = 'INFRA';
}
`;

  return { phpEnum, phpCategoryEnum };
}

/**
 * ファイルを書き込み
 */
function writeFile(filePath, content) {
  const dir = path.dirname(filePath);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  fs.writeFileSync(filePath, content, 'utf8');
}

// TypeScript型定義を生成
console.log('🔄 TypeScript型定義を生成中...');
const tsTypes = generateTypeScriptTypes(errorCodes);

writeFile(ADMIN_APP_OUTPUT, tsTypes);
console.log(`✅ ${path.relative(process.cwd(), ADMIN_APP_OUTPUT)}`);

writeFile(USER_APP_OUTPUT, tsTypes);
console.log(`✅ ${path.relative(process.cwd(), USER_APP_OUTPUT)}`);

// PHP Enumを生成
console.log('\n🔄 PHP Enumを生成中...');
const { phpEnum, phpCategoryEnum } = generatePHPEnum(errorCodes);

writeFile(PHP_ENUM_OUTPUT, phpEnum);
console.log(`✅ ${path.relative(process.cwd(), PHP_ENUM_OUTPUT)}`);

const phpCategoryOutput = path.join(__dirname, '../backend/laravel-api/app/Enums/ErrorCategory.php');
writeFile(phpCategoryOutput, phpCategoryEnum);
console.log(`✅ ${path.relative(process.cwd(), phpCategoryOutput)}`);

console.log('\n✨ 型定義の生成が完了しました！');
console.log('\n📊 統計:');
console.log(`  - エラーコード数: ${Object.keys(errorCodes).length}`);
console.log(`  - 生成ファイル数: 4`);
