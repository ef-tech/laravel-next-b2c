#!/bin/bash
#
# Security Headers Validation Script
#
# このスクリプトは指定されたURL のセキュリティヘッダーを検証します。
#
# Usage:
#   bash scripts/validate-security-headers.sh <URL> <app-type>
#
# Arguments:
#   URL: 検証対象のURL（例: http://localhost:13000/api/health）
#   app-type: アプリケーションタイプ（laravel, user-app, admin-app）
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

# 引数チェック
if [ $# -ne 2 ]; then
  echo -e "${RED}Usage: $0 <URL> <app-type>${NC}"
  echo "  app-type: laravel | user-app | admin-app"
  exit 1
fi

URL="$1"
APP_TYPE="$2"

echo "🔒 Security Headers Validation"
echo "================================"
echo "URL: $URL"
echo "App Type: $APP_TYPE"
echo ""

# ヘッダー取得
HEADERS=$(curl -sI "$URL")

# 検証カウンター
PASSED=0
FAILED=0

# ヘッダー検証関数
check_header() {
  local header_name="$1"
  local expected_pattern="$2"
  local is_required="${3:-true}"

  if echo "$HEADERS" | grep -qi "^${header_name}:"; then
    local header_value=$(echo "$HEADERS" | grep -i "^${header_name}:" | head -1 | cut -d: -f2- | tr -d '\r' | sed 's/^ *//')

    if [ -n "$expected_pattern" ]; then
      if echo "$header_value" | grep -qE "$expected_pattern"; then
        echo -e "${GREEN}✅ $header_name: $header_value${NC}"
        ((PASSED++))
      else
        echo -e "${RED}❌ $header_name: $header_value (expected pattern: $expected_pattern)${NC}"
        ((FAILED++))
      fi
    else
      echo -e "${GREEN}✅ $header_name: $header_value${NC}"
      ((PASSED++))
    fi
  else
    if [ "$is_required" = "true" ]; then
      echo -e "${RED}❌ $header_name: NOT FOUND${NC}"
      ((FAILED++))
    else
      echo -e "${YELLOW}⚠️  $header_name: NOT FOUND (optional)${NC}"
    fi
  fi
}

# 共通セキュリティヘッダー検証
echo "📋 Common Security Headers:"
check_header "X-Content-Type-Options" "nosniff"

# アプリケーション種別ごとの検証
case "$APP_TYPE" in
  laravel)
    echo ""
    echo "📋 Laravel API Security Headers:"
    check_header "X-Frame-Options" "SAMEORIGIN|DENY"
    check_header "Referrer-Policy" "strict-origin-when-cross-origin|no-referrer"

    # CSP は環境変数次第でオプショナル
    if echo "$HEADERS" | grep -qi "^Content-Security-Policy:"; then
      check_header "Content-Security-Policy" "" false
    elif echo "$HEADERS" | grep -qi "^Content-Security-Policy-Report-Only:"; then
      check_header "Content-Security-Policy-Report-Only" "" false
    else
      echo -e "${YELLOW}⚠️  Content-Security-Policy: NOT FOUND (may be disabled via env)${NC}"
    fi

    # CORS ヘッダー（OPTIONS リクエストで検証が必要）
    check_header "Access-Control-Allow-Origin" "" false
    ;;

  user-app)
    echo ""
    echo "📋 User App Security Headers:"
    check_header "X-Frame-Options" "SAMEORIGIN"
    check_header "Referrer-Policy" "strict-origin-when-cross-origin"
    check_header "Content-Security-Policy" ""
    check_header "Permissions-Policy" "" false
    ;;

  admin-app)
    echo ""
    echo "📋 Admin App Security Headers:"
    check_header "X-Frame-Options" "DENY"
    check_header "Referrer-Policy" "no-referrer"
    check_header "Content-Security-Policy" ""
    check_header "Permissions-Policy" "" false
    check_header "X-Permitted-Cross-Domain-Policies" "none" false
    check_header "Cross-Origin-Embedder-Policy" "require-corp" false
    check_header "Cross-Origin-Opener-Policy" "same-origin" false
    ;;

  *)
    echo -e "${RED}Unknown app type: $APP_TYPE${NC}"
    exit 1
    ;;
esac

# 結果サマリー
echo ""
echo "================================"
echo "📊 Validation Summary:"
echo -e "  ${GREEN}Passed: $PASSED${NC}"
echo -e "  ${RED}Failed: $FAILED${NC}"

if [ $FAILED -gt 0 ]; then
  echo ""
  echo -e "${RED}❌ Security headers validation FAILED${NC}"
  exit 1
else
  echo ""
  echo -e "${GREEN}✅ All security headers validation PASSED${NC}"
  exit 0
fi
