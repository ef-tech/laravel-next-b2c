#!/usr/bin/env node

/**
 * 翻訳ファイルキー整合性検証スクリプト
 *
 * REQ-9.3: 翻訳ファイル間のキー整合性検証
 *
 * Usage:
 *   node scripts/validate-i18n-keys.js
 */

const fs = require('fs');
const path = require('path');

/**
 * JSONファイルから全てのキーを抽出する（ドット記法）
 *
 * @param {Object} obj - JSONオブジェクト
 * @param {string} prefix - キーのプレフィックス（再帰用）
 * @returns {string[]} キーの配列（ドット記法）
 */
function extractKeys(obj, prefix = '') {
  const keys = [];

  for (const key in obj) {
    const currentPath = prefix ? `${prefix}.${key}` : key;
    const value = obj[key];

    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
      // オブジェクトの場合、再帰的にキーを抽出
      keys.push(...extractKeys(value, currentPath));
    } else {
      // リーフノード（文字列値）の場合、キーを追加
      keys.push(currentPath);
    }
  }

  return keys.sort();
}

/**
 * 2つの翻訳ファイル間のキー整合性を検証する
 *
 * @param {string} firstFilePath - 最初の翻訳ファイルのパス（例: ja.json）
 * @param {string} secondFilePath - 2番目の翻訳ファイルのパス（例: en.json）
 * @throws {Error} キー整合性エラーが発生した場合
 */
function validateKeyConsistency(firstFilePath, secondFilePath) {
  // ファイル読み込み
  const firstContent = JSON.parse(fs.readFileSync(firstFilePath, 'utf-8'));
  const secondContent = JSON.parse(fs.readFileSync(secondFilePath, 'utf-8'));

  // キーを抽出
  const firstKeys = extractKeys(firstContent);
  const secondKeys = extractKeys(secondContent);

  // 差分を検出
  const onlyInFirst = firstKeys.filter((key) => !secondKeys.includes(key));
  const onlyInSecond = secondKeys.filter((key) => !firstKeys.includes(key));

  // エラーメッセージを構築
  if (onlyInFirst.length > 0 || onlyInSecond.length > 0) {
    const firstName = path.basename(firstFilePath);
    const secondName = path.basename(secondFilePath);
    const errorMessages = [];

    if (onlyInFirst.length > 0) {
      errorMessages.push(`Keys only in ${firstName}:\n  - ${onlyInFirst.join('\n  - ')}`);
    }

    if (onlyInSecond.length > 0) {
      errorMessages.push(`Keys only in ${secondName}:\n  - ${onlyInSecond.join('\n  - ')}`);
    }

    throw new Error(errorMessages.join('\n\n'));
  }
}

/**
 * 2つの翻訳ファイル間のキー数を検証する
 *
 * @param {string} firstFilePath - 最初の翻訳ファイルのパス
 * @param {string} secondFilePath - 2番目の翻訳ファイルのパス
 * @throws {Error} キー数が一致しない場合
 */
function validateKeyCount(firstFilePath, secondFilePath) {
  const firstContent = JSON.parse(fs.readFileSync(firstFilePath, 'utf-8'));
  const secondContent = JSON.parse(fs.readFileSync(secondFilePath, 'utf-8'));

  const firstKeys = extractKeys(firstContent);
  const secondKeys = extractKeys(secondContent);

  if (firstKeys.length !== secondKeys.length) {
    const firstName = path.basename(firstFilePath);
    const secondName = path.basename(secondFilePath);

    throw new Error(
      `Key count mismatch between ${firstName} (${firstKeys.length} keys) and ${secondName} (${secondKeys.length} keys)`
    );
  }
}

/**
 * 2つの翻訳ファイル間の不足キーを取得する
 *
 * @param {string} firstFilePath - 最初の翻訳ファイルのパス
 * @param {string} secondFilePath - 2番目の翻訳ファイルのパス
 * @returns {{onlyInFirst: string[], onlyInSecond: string[]}} 不足キーのリスト
 */
function getMissingKeys(firstFilePath, secondFilePath) {
  const firstContent = JSON.parse(fs.readFileSync(firstFilePath, 'utf-8'));
  const secondContent = JSON.parse(fs.readFileSync(secondFilePath, 'utf-8'));

  const firstKeys = extractKeys(firstContent);
  const secondKeys = extractKeys(secondContent);

  return {
    onlyInFirst: firstKeys.filter((key) => !secondKeys.includes(key)),
    onlyInSecond: secondKeys.filter((key) => !firstKeys.includes(key)),
  };
}

/**
 * 全ての翻訳ファイルのキー整合性を検証する
 *
 * @param {string} frontendDir - frontendディレクトリのパス
 * @throws {Error} いずれかのファイルで整合性エラーが発生した場合
 */
function validateAllKeyConsistency(frontendDir) {
  const apps = ['user-app', 'admin-app'];
  const errors = [];

  for (const app of apps) {
    const jaPath = path.join(frontendDir, app, 'messages', 'ja.json');
    const enPath = path.join(frontendDir, app, 'messages', 'en.json');

    try {
      // キー整合性検証
      validateKeyConsistency(jaPath, enPath);

      // キー数検証
      validateKeyCount(jaPath, enPath);

      console.log(`✓ ${app} - ja.json and en.json keys are consistent`);
    } catch (error) {
      errors.push({
        app,
        error: error.message,
      });
    }
  }

  if (errors.length > 0) {
    const errorMessages = errors
      .map((e) => `  ${e.app}:\n    ${e.error.replace(/\n/g, '\n    ')}`)
      .join('\n\n');
    throw new Error(`Key consistency validation failed:\n\n${errorMessages}`);
  }
}

/**
 * メイン処理
 */
function main() {
  const projectRoot = path.join(__dirname, '..');
  const frontendDir = path.join(projectRoot, 'frontend');

  console.log('Validating translation key consistency...\n');

  try {
    validateAllKeyConsistency(frontendDir);
    console.log('\n✓ All translation files have consistent keys!');
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
  validateKeyConsistency,
  validateKeyCount,
  getMissingKeys,
  validateAllKeyConsistency,
};
