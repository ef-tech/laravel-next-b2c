#!/bin/bash

# エラーコード定義と型定義の同期を検証するスクリプト
# CI/CDパイプラインで使用

set -e

echo "🔍 エラーコード定義と型定義の同期を検証中..."

# 一時ディレクトリを作成
TEMP_DIR=$(mktemp -d)
trap "rm -rf $TEMP_DIR" EXIT

# 現在の生成ファイルをバックアップ
cp frontend/admin-app/src/types/error-codes.ts "$TEMP_DIR/admin-app-error-codes.ts.backup"
cp frontend/user-app/src/types/error-codes.ts "$TEMP_DIR/user-app-error-codes.ts.backup"
cp backend/laravel-api/app/Enums/ErrorCode.php "$TEMP_DIR/ErrorCode.php.backup"
cp backend/laravel-api/app/Enums/ErrorCategory.php "$TEMP_DIR/ErrorCategory.php.backup"

# 型定義を再生成
echo "🔄 型定義を再生成中..."
npm run generate:error-types --silent

# 差分をチェック
echo "🔍 差分をチェック中..."

DIFF_COUNT=0

if ! diff -q frontend/admin-app/src/types/error-codes.ts "$TEMP_DIR/admin-app-error-codes.ts.backup" > /dev/null 2>&1; then
  echo "❌ frontend/admin-app/src/types/error-codes.ts が最新ではありません"
  DIFF_COUNT=$((DIFF_COUNT + 1))
fi

if ! diff -q frontend/user-app/src/types/error-codes.ts "$TEMP_DIR/user-app-error-codes.ts.backup" > /dev/null 2>&1; then
  echo "❌ frontend/user-app/src/types/error-codes.ts が最新ではありません"
  DIFF_COUNT=$((DIFF_COUNT + 1))
fi

if ! diff -q backend/laravel-api/app/Enums/ErrorCode.php "$TEMP_DIR/ErrorCode.php.backup" > /dev/null 2>&1; then
  echo "❌ backend/laravel-api/app/Enums/ErrorCode.php が最新ではありません"
  DIFF_COUNT=$((DIFF_COUNT + 1))
fi

if ! diff -q backend/laravel-api/app/Enums/ErrorCategory.php "$TEMP_DIR/ErrorCategory.php.backup" > /dev/null 2>&1; then
  echo "❌ backend/laravel-api/app/Enums/ErrorCategory.php が最新ではありません"
  DIFF_COUNT=$((DIFF_COUNT + 1))
fi

if [ $DIFF_COUNT -eq 0 ]; then
  echo "✅ エラーコード定義と型定義は同期されています"
  exit 0
else
  echo ""
  echo "⚠️  型定義が最新ではありません！"
  echo ""
  echo "以下のコマンドを実行して型定義を更新してください:"
  echo "  npm run generate:error-types"
  echo ""
  exit 1
fi
