#!/usr/bin/env node

/**
 * 翻訳ファイル構造検証スクリプト
 *
 * REQ-9.1: 翻訳ファイル構造の検証
 * REQ-9.2: 必須キーの存在確認
 *
 * Usage:
 *   node scripts/validate-i18n-messages.js
 */

const fs = require('fs');
const path = require('path');

/**
 * 必須キーのリスト
 */
const REQUIRED_KEYS = [
  'errors.network.timeout',
  'errors.network.connection',
  'errors.network.unknown',
  'errors.boundary.title',
  'errors.boundary.retry',
  'errors.boundary.home',
  'errors.boundary.status',
  'errors.boundary.requestId',
  'errors.boundary.networkError',
  'errors.boundary.timeout',
  'errors.boundary.connectionError',
  'errors.boundary.retryableMessage',
  'errors.validation.title',
  'errors.global.title',
  'errors.global.retry',
  'errors.global.errorId',
  'errors.global.contactMessage',
];

/**
 * 最大ネストレベル
 */
const MAX_NESTING_LEVEL = 3;

/**
 * 翻訳ファイルの構造を検証する
 *
 * @param {string} filePath - 翻訳ファイルのパス
 * @throws {Error} ファイルが存在しない、JSONパースエラー、構造エラーの場合
 */
function validateMessageStructure(filePath) {
  // ファイル存在確認
  if (!fs.existsSync(filePath)) {
    throw new Error(`File not found: ${filePath}`);
  }

  // JSONファイル読み込み
  let content;
  try {
    const fileContent = fs.readFileSync(filePath, 'utf-8');
    content = JSON.parse(fileContent);
  } catch (error) {
    throw new Error(`JSON parse error in ${filePath}: ${error.message}`);
  }

  // ネストレベル検証
  validateNestingLevel(content, filePath, 0);

  // 翻訳値が文字列であることを検証
  validateTranslationValues(content, filePath, []);
}

/**
 * ネストレベルを検証する
 *
 * @param {Object} obj - 検証対象のオブジェクト
 * @param {string} filePath - ファイルパス（エラーメッセージ用）
 * @param {number} level - 現在のネストレベル
 * @throws {Error} ネストレベルが最大値を超えた場合
 */
function validateNestingLevel(obj, filePath, level) {
  if (level > MAX_NESTING_LEVEL) {
    throw new Error(
      `Nesting level exceeds maximum (${MAX_NESTING_LEVEL}) in ${filePath} at level ${level}`
    );
  }

  for (const key in obj) {
    if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
      validateNestingLevel(obj[key], filePath, level + 1);
    }
  }
}

/**
 * 翻訳値が文字列であることを検証する
 *
 * @param {Object} obj - 検証対象のオブジェクト
 * @param {string} filePath - ファイルパス（エラーメッセージ用）
 * @param {string[]} path - 現在のパス（ドット記法）
 * @throws {Error} 翻訳値が文字列でない場合
 */
function validateTranslationValues(obj, filePath, path) {
  for (const key in obj) {
    const currentPath = [...path, key];
    const value = obj[key];

    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
      // オブジェクトの場合、再帰的に検証
      validateTranslationValues(value, filePath, currentPath);
    } else if (typeof value !== 'string') {
      // 文字列でない場合はエラー
      throw new Error(
        `Translation value at "${currentPath.join('.')}" must be a string in ${filePath}, got ${typeof value}`
      );
    }
  }
}

/**
 * 必須キーが存在することを検証する
 *
 * @param {string} filePath - 翻訳ファイルのパス
 * @param {string[]} requiredKeys - 必須キーのリスト（ドット記法）
 * @throws {Error} 必須キーが不足している場合
 */
function validateRequiredKeys(filePath, requiredKeys) {
  // JSONファイル読み込み
  let content;
  try {
    const fileContent = fs.readFileSync(filePath, 'utf-8');
    content = JSON.parse(fileContent);
  } catch (error) {
    throw new Error(`JSON parse error in ${filePath}: ${error.message}`);
  }

  // 必須キーの存在確認
  const missingKeys = [];
  for (const key of requiredKeys) {
    if (!hasKey(content, key)) {
      missingKeys.push(key);
    }
  }

  if (missingKeys.length > 0) {
    throw new Error(
      `Missing required keys in ${filePath}:\n  - ${missingKeys.join('\n  - ')}`
    );
  }
}

/**
 * オブジェクトがドット記法のキーを持つか確認する
 *
 * @param {Object} obj - 検証対象のオブジェクト
 * @param {string} keyPath - ドット記法のキーパス（例: "errors.network.timeout"）
 * @returns {boolean} キーが存在する場合true
 */
function hasKey(obj, keyPath) {
  const keys = keyPath.split('.');
  let current = obj;

  for (const key of keys) {
    if (current === null || typeof current !== 'object' || !(key in current)) {
      return false;
    }
    current = current[key];
  }

  return true;
}

/**
 * 複数の翻訳ファイルを検証する
 *
 * @param {string} frontendDir - frontendディレクトリのパス
 * @throws {Error} いずれかのファイルで検証エラーが発生した場合
 */
function validateAllMessageFiles(frontendDir) {
  const apps = ['user-app', 'admin-app'];
  const locales = ['ja', 'en'];
  const errors = [];

  for (const app of apps) {
    for (const locale of locales) {
      const filePath = path.join(frontendDir, app, 'messages', `${locale}.json`);

      try {
        // 構造検証
        validateMessageStructure(filePath);

        // 必須キー検証
        validateRequiredKeys(filePath, REQUIRED_KEYS);

        console.log(`✓ ${app}/messages/${locale}.json - OK`);
      } catch (error) {
        errors.push({
          file: `${app}/messages/${locale}.json`,
          error: error.message,
        });
      }
    }
  }

  if (errors.length > 0) {
    const errorMessages = errors
      .map((e) => `  ${e.file}:\n    ${e.error.replace(/\n/g, '\n    ')}`)
      .join('\n\n');
    throw new Error(`Translation file validation failed:\n\n${errorMessages}`);
  }
}

/**
 * メイン処理
 */
function main() {
  const projectRoot = path.join(__dirname, '..');
  const frontendDir = path.join(projectRoot, 'frontend');

  console.log('Validating translation files...\n');

  try {
    validateAllMessageFiles(frontendDir);
    console.log('\n✓ All translation files are valid!');
    process.exit(0);
  } catch (error) {
    console.error('\n✗ Validation failed:\n');
    console.error(error.message);
    process.exit(1);
  }
}

// スクリプト直接実行時のみmain()を実行
if (require.main === module) {
  main();
}

// テスト用にエクスポート
module.exports = {
  validateMessageStructure,
  validateRequiredKeys,
  validateAllMessageFiles,
};
