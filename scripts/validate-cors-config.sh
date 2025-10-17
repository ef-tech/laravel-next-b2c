#!/bin/bash
#
# CORS Configuration Validation Script
#
# このスクリプトはLaravel CORS設定とフロントエンドアプリ間の整合性を検証します。
#
# Usage:
#   bash scripts/validate-cors-config.sh
#
# Exit codes:
#   0: すべての検証成功
#   1: 検証失敗

set -e

# カラー出力設定
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🌐 CORS Configuration Validation"
echo "================================"
echo ""

# 検証カウンター
PASSED=0
FAILED=0
WARNINGS=0

# Laravel .env.example から CORS 設定を抽出
LARAVEL_ENV_EXAMPLE="backend/laravel-api/.env.example"

if [ ! -f "$LARAVEL_ENV_EXAMPLE" ]; then
  echo -e "${RED}❌ $LARAVEL_ENV_EXAMPLE not found${NC}"
  exit 1
fi

echo "📋 Checking Laravel .env.example CORS configuration..."

# CORS_ALLOWED_ORIGINS の存在確認
if grep -q "^CORS_ALLOWED_ORIGINS=" "$LARAVEL_ENV_EXAMPLE"; then
  CORS_ALLOWED_ORIGINS=$(grep "^CORS_ALLOWED_ORIGINS=" "$LARAVEL_ENV_EXAMPLE" | cut -d= -f2- | tr -d '"' | tr -d "'")
  echo -e "${GREEN}✅ CORS_ALLOWED_ORIGINS found: $CORS_ALLOWED_ORIGINS${NC}"
  ((PASSED++))

  # カンマ区切りで分解してオリジンリストを取得
  IFS=',' read -ra ORIGINS <<< "$CORS_ALLOWED_ORIGINS"

  # 期待されるオリジン
  EXPECTED_USER_APP="http://localhost:13001"
  EXPECTED_ADMIN_APP="http://localhost:13002"

  USER_APP_FOUND=false
  ADMIN_APP_FOUND=false

  for origin in "${ORIGINS[@]}"; do
    # トリム
    origin=$(echo "$origin" | xargs)

    if [ "$origin" = "$EXPECTED_USER_APP" ]; then
      USER_APP_FOUND=true
    fi

    if [ "$origin" = "$EXPECTED_ADMIN_APP" ]; then
      ADMIN_APP_FOUND=true
    fi
  done

  if [ "$USER_APP_FOUND" = true ]; then
    echo -e "${GREEN}✅ User App origin ($EXPECTED_USER_APP) is configured${NC}"
    ((PASSED++))
  else
    echo -e "${RED}❌ User App origin ($EXPECTED_USER_APP) is NOT configured${NC}"
    ((FAILED++))
  fi

  if [ "$ADMIN_APP_FOUND" = true ]; then
    echo -e "${GREEN}✅ Admin App origin ($EXPECTED_ADMIN_APP) is configured${NC}"
    ((PASSED++))
  else
    echo -e "${RED}❌ Admin App origin ($EXPECTED_ADMIN_APP) is NOT configured${NC}"
    ((FAILED++))
  fi
else
  echo -e "${RED}❌ CORS_ALLOWED_ORIGINS not found in $LARAVEL_ENV_EXAMPLE${NC}"
  ((FAILED++))
fi

# CORS_SUPPORTS_CREDENTIALS の検証
echo ""
echo "📋 Checking CORS_SUPPORTS_CREDENTIALS..."

if grep -q "^CORS_SUPPORTS_CREDENTIALS=" "$LARAVEL_ENV_EXAMPLE"; then
  CORS_SUPPORTS_CREDENTIALS=$(grep "^CORS_SUPPORTS_CREDENTIALS=" "$LARAVEL_ENV_EXAMPLE" | cut -d= -f2- | tr -d '"' | tr -d "'")

  if [ "$CORS_SUPPORTS_CREDENTIALS" = "true" ]; then
    echo -e "${GREEN}✅ CORS_SUPPORTS_CREDENTIALS is set to true${NC}"
    ((PASSED++))

    # credentials=true の場合、 CORS_ALLOWED_ORIGINS に * が含まれていないことを確認
    if echo "$CORS_ALLOWED_ORIGINS" | grep -q '\*'; then
      echo -e "${RED}❌ CORS_ALLOWED_ORIGINS contains wildcard (*) with credentials=true (security risk)${NC}"
      ((FAILED++))
    else
      echo -e "${GREEN}✅ CORS_ALLOWED_ORIGINS does not contain wildcard (secure with credentials=true)${NC}"
      ((PASSED++))
    fi
  elif [ "$CORS_SUPPORTS_CREDENTIALS" = "false" ]; then
    echo -e "${YELLOW}⚠️  CORS_SUPPORTS_CREDENTIALS is set to false (consider enabling for authenticated requests)${NC}"
    ((WARNINGS++))
  else
    echo -e "${RED}❌ CORS_SUPPORTS_CREDENTIALS has invalid value: $CORS_SUPPORTS_CREDENTIALS${NC}"
    ((FAILED++))
  fi
else
  echo -e "${RED}❌ CORS_SUPPORTS_CREDENTIALS not found in $LARAVEL_ENV_EXAMPLE${NC}"
  ((FAILED++))
fi

# config/cors.php の検証
echo ""
echo "📋 Checking config/cors.php..."

CORS_CONFIG="backend/laravel-api/config/cors.php"

if [ ! -f "$CORS_CONFIG" ]; then
  echo -e "${RED}❌ $CORS_CONFIG not found${NC}"
  ((FAILED++))
else
  echo -e "${GREEN}✅ $CORS_CONFIG found${NC}"
  ((PASSED++))

  # 'allowed_origins' が環境変数から読み込まれることを確認
  if grep -q "env('CORS_ALLOWED_ORIGINS'" "$CORS_CONFIG"; then
    echo -e "${GREEN}✅ allowed_origins uses env('CORS_ALLOWED_ORIGINS')${NC}"
    ((PASSED++))
  else
    echo -e "${RED}❌ allowed_origins does not use env('CORS_ALLOWED_ORIGINS')${NC}"
    ((FAILED++))
  fi

  # 'supports_credentials' が環境変数から読み込まれることを確認
  if grep -q "env('CORS_SUPPORTS_CREDENTIALS'" "$CORS_CONFIG"; then
    echo -e "${GREEN}✅ supports_credentials uses env('CORS_SUPPORTS_CREDENTIALS')${NC}"
    ((PASSED++))
  else
    echo -e "${RED}❌ supports_credentials does not use env('CORS_SUPPORTS_CREDENTIALS')${NC}"
    ((FAILED++))
  fi
fi

# フロントエンドポート設定の整合性確認
echo ""
echo "📋 Checking frontend port configuration consistency..."

# User App package.json
USER_APP_PACKAGE_JSON="frontend/user-app/package.json"
if [ -f "$USER_APP_PACKAGE_JSON" ]; then
  if grep -q '"dev".*--port 13001' "$USER_APP_PACKAGE_JSON"; then
    echo -e "${GREEN}✅ User App uses port 13001 (matches CORS config)${NC}"
    ((PASSED++))
  else
    echo -e "${YELLOW}⚠️  User App port may not match CORS config (expected 13001)${NC}"
    ((WARNINGS++))
  fi
else
  echo -e "${YELLOW}⚠️  $USER_APP_PACKAGE_JSON not found${NC}"
  ((WARNINGS++))
fi

# Admin App package.json
ADMIN_APP_PACKAGE_JSON="frontend/admin-app/package.json"
if [ -f "$ADMIN_APP_PACKAGE_JSON" ]; then
  if grep -q '"dev".*--port 13002' "$ADMIN_APP_PACKAGE_JSON"; then
    echo -e "${GREEN}✅ Admin App uses port 13002 (matches CORS config)${NC}"
    ((PASSED++))
  else
    echo -e "${YELLOW}⚠️  Admin App port may not match CORS config (expected 13002)${NC}"
    ((WARNINGS++))
  fi
else
  echo -e "${YELLOW}⚠️  $ADMIN_APP_PACKAGE_JSON not found${NC}"
  ((WARNINGS++))
fi

# 結果サマリー
echo ""
echo "================================"
echo "📊 Validation Summary:"
echo -e "  ${GREEN}Passed: $PASSED${NC}"
echo -e "  ${YELLOW}Warnings: $WARNINGS${NC}"
echo -e "  ${RED}Failed: $FAILED${NC}"

if [ $FAILED -gt 0 ]; then
  echo ""
  echo -e "${RED}❌ CORS configuration validation FAILED${NC}"
  exit 1
else
  if [ $WARNINGS -gt 0 ]; then
    echo ""
    echo -e "${YELLOW}⚠️  CORS configuration validation PASSED with warnings${NC}"
  else
    echo ""
    echo -e "${GREEN}✅ All CORS configuration validation PASSED${NC}"
  fi
  exit 0
fi
