#!/bin/bash

# エラーコード定義と型定義の同期を検証するスクリプト
# RFC 7807 type URI統一ルール検証を含む
# CI/CDパイプラインで使用

set -e

echo "🔍 エラーコード定義と型定義の同期を検証中..."
echo ""

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
else
  echo ""
  echo "⚠️  型定義が最新ではありません！"
  echo ""
  echo "以下のコマンドを実行して型定義を更新してください:"
  echo "  npm run generate:error-types"
  echo ""
  exit 1
fi

echo ""
echo "🔍 RFC 7807 type URI統一ルールを検証中..."
echo ""

# RFC 7807 type URI統一検証
VIOLATIONS_FOUND=0

# 1. ddd/Shared/Exceptions/内の直接的なtype URI生成パターン検出
echo "📋 例外クラスの直接的なtype URI生成パターンをチェック中..."

EXCEPTION_DIR="backend/laravel-api/ddd/Shared/Exceptions"

if [ -d "$EXCEPTION_DIR" ]; then
  # ErrorCode::fromString()を使わずにconfig('app.url')でtype URIを生成しているパターンを検出
  # ただし、フォールバック用のnull coalescing operator (??)の右辺にある場合は許可

  # HasProblemDetails.php をチェック（DomainExceptionはトレイト経由で使用）
  for file in "$EXCEPTION_DIR/HasProblemDetails.php"; do
    if [ -f "$file" ]; then
      # ErrorCode::fromString()が存在することを確認
      if ! grep -q "ErrorCode::fromString" "$file"; then
        echo "❌ $file: ErrorCode::fromString()が使用されていません"
        VIOLATIONS_FOUND=$((VIOLATIONS_FOUND + 1))
      fi

      # config('app.url')が存在する場合、ErrorCode::fromString()とセットで使われているか確認
      if grep -q "config('app.url')" "$file"; then
        # フォールバック（??の右辺）として使われている場合のみ許可
        if ! grep -B 1 "config('app.url')" "$file" | grep -q "ErrorCode::fromString"; then
          echo "⚠️  $file: config('app.url')が直接使用されていますが、ErrorCode::fromString()とセットになっているか確認が必要です"
        fi
      fi
    fi
  done
else
  echo "⚠️  $EXCEPTION_DIR が見つかりません"
  VIOLATIONS_FOUND=$((VIOLATIONS_FOUND + 1))
fi

if [ $VIOLATIONS_FOUND -eq 0 ]; then
  echo "✅ 直接的なtype URI生成パターンは検出されませんでした"
else
  echo "❌ $VIOLATIONS_FOUND 件の違反が検出されました"
fi

echo ""

# 2. Architecture Test (ErrorTypeUriTest.php)を実行
echo "🧪 Architecture Test (RFC 7807 type URI統一ルール)を実行中..."
echo ""

cd backend/laravel-api

# Architecture Testのみを実行
if ENV_VALIDATION_SKIP=true RATELIMIT_CACHE_STORE=array ./vendor/bin/pest tests/Architecture/ErrorTypeUriTest.php --no-coverage; then
  echo ""
  echo "✅ Architecture Test (RFC 7807 type URI統一ルール)が成功しました"
else
  echo ""
  echo "❌ Architecture Test (RFC 7807 type URI統一ルール)が失敗しました"
  VIOLATIONS_FOUND=$((VIOLATIONS_FOUND + 1))
fi

cd ../..

echo ""

# 最終結果
if [ $VIOLATIONS_FOUND -eq 0 ]; then
  echo "✅ RFC 7807 type URI統一ルール検証が成功しました"
  echo ""
  exit 0
else
  echo "❌ RFC 7807 type URI統一ルール検証が失敗しました ($VIOLATIONS_FOUND 件の違反)"
  echo ""
  echo "修正方法:"
  echo "  1. ddd/Shared/Exceptions/内の例外クラスでErrorCode::fromString()->getType()を使用してください"
  echo "  2. Architecture Test (ErrorTypeUriTest.php)のエラーメッセージを確認してください"
  echo ""
  exit 1
fi
