#!/usr/bin/env node

/**
 * エラーコード定義ファイルのJSON Schemaバリデーションスクリプト
 *
 * Usage:
 *   node scripts/validate-error-codes.js
 *
 * Exit codes:
 *   0: バリデーション成功
 *   1: バリデーションエラー
 */

const Ajv = require('ajv');
const fs = require('fs');
const path = require('path');

// ファイルパス
const SCHEMA_PATH = path.join(__dirname, '../shared/error-codes.schema.json');
const ERROR_CODES_PATH = path.join(__dirname, '../shared/error-codes.json');

// スキーマとエラーコード定義を読み込み
let schema, errorCodes;

try {
  schema = JSON.parse(fs.readFileSync(SCHEMA_PATH, 'utf8'));
  errorCodes = JSON.parse(fs.readFileSync(ERROR_CODES_PATH, 'utf8'));
} catch (error) {
  console.error('❌ ファイル読み込みエラー:', error.message);
  process.exit(1);
}

// Ajvインスタンス作成
const ajv = new Ajv({ allErrors: true });

// バリデーション実行
const validate = ajv.compile(schema);
const valid = validate(errorCodes);

if (valid) {
  console.log('✅ エラーコード定義のバリデーション成功');

  // 統計情報を表示
  const errorCodeKeys = Object.keys(errorCodes);
  const categories = {};

  errorCodeKeys.forEach(key => {
    const category = errorCodes[key].category;
    categories[category] = (categories[category] || 0) + 1;
  });

  console.log(`\n📊 統計情報:`);
  console.log(`  総エラーコード数: ${errorCodeKeys.length}`);
  console.log(`  カテゴリー別内訳:`);
  Object.keys(categories).sort().forEach(category => {
    console.log(`    - ${category}: ${categories[category]}件`);
  });

  process.exit(0);
} else {
  console.error('❌ エラーコード定義のバリデーション失敗\n');
  console.error('エラー詳細:');
  validate.errors.forEach((error, index) => {
    console.error(`  ${index + 1}. ${error.instancePath || 'root'}: ${error.message}`);
    if (error.params) {
      console.error(`     パラメータ: ${JSON.stringify(error.params)}`);
    }
  });

  process.exit(1);
}
