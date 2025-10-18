#!/bin/bash
#
# CSP Violation Log Analysis Script
#
# このスクリプトはセキュリティログから CSP 違反レポートを分析します。
#
# Usage:
#   bash scripts/analyze-csp-violations.sh [log-file]
#
# Arguments:
#   log-file: ログファイルパス（デフォルト: backend/laravel-api/storage/logs/security.log）
#
# Output:
#   - 総違反件数
#   - 違反ディレクティブTop 10
#   - ブロックされたURITop 10
#   - 違反率計算（総リクエスト数が必要な場合）
#
# Exit codes:
#   0: 分析成功
#   1: ログファイルが存在しない

set -e

# カラー出力設定
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# デフォルトログファイルパス
DEFAULT_LOG_FILE="backend/laravel-api/storage/logs/security.log"
LOG_FILE="${1:-$DEFAULT_LOG_FILE}"

# ログファイル存在確認
if [ ! -f "$LOG_FILE" ]; then
  echo -e "${RED}❌ Log file not found: $LOG_FILE${NC}"
  echo "Please check the log file path or ensure CSP violations have been logged."
  exit 1
fi

echo -e "${BLUE}🔍 CSP Violation Analysis${NC}"
echo "========================================"
echo "Log file: $LOG_FILE"
echo ""

# 1. 総違反件数
echo -e "${BLUE}📊 1. Total CSP Violations${NC}"
echo "----------------------------------------"

TOTAL_VIOLATIONS=$(grep -c "CSP Violation" "$LOG_FILE" 2>/dev/null || echo "0")

if [ "$TOTAL_VIOLATIONS" -eq 0 ]; then
  echo -e "${GREEN}✅ No CSP violations found!${NC}"
  echo ""
  echo "This is excellent! No CSP violations detected in the log file."
  echo "You can proceed to Enforce mode if the Report-Only period is complete."
  exit 0
fi

echo -e "Total violations: ${YELLOW}$TOTAL_VIOLATIONS${NC}"
echo ""

# 2. 違反ディレクティブTop 10
echo -e "${BLUE}📊 2. Top 10 Violated Directives${NC}"
echo "----------------------------------------"

# JSONログから violated-directive を抽出（Laravel Log JSONフォーマット想定）
if grep -q '"violated_directive"' "$LOG_FILE" 2>/dev/null; then
  # JSON形式のログの場合
  grep "violated_directive" "$LOG_FILE" | \
    grep -oP '"violated_directive":"[^"]*"' | \
    cut -d: -f2 | tr -d '"' | \
    sort | uniq -c | sort -rn | head -10 | \
    awk '{printf "  %5d  %s\n", $1, $2}'
else
  # プレーンテキストログの場合
  grep "violated-directive:" "$LOG_FILE" 2>/dev/null | \
    sed 's/.*violated-directive: *//' | \
    cut -d',' -f1 | \
    sort | uniq -c | sort -rn | head -10 | \
    awk '{printf "  %5d  %s\n", $1, $2}' || echo "  No directive data found"
fi

echo ""

# 3. ブロックされたURITop 10
echo -e "${BLUE}📊 3. Top 10 Blocked URIs${NC}"
echo "----------------------------------------"

if grep -q '"blocked_uri"' "$LOG_FILE" 2>/dev/null; then
  # JSON形式のログの場合
  grep "blocked_uri" "$LOG_FILE" | \
    grep -oP '"blocked_uri":"[^"]*"' | \
    cut -d: -f2- | tr -d '"' | \
    sort | uniq -c | sort -rn | head -10 | \
    awk '{count=$1; $1=""; uri=$0; gsub(/^ /, "", uri); printf "  %5d  %s\n", count, uri}'
else
  # プレーンテキストログの場合
  grep "blocked-uri:" "$LOG_FILE" 2>/dev/null | \
    sed 's/.*blocked-uri: *//' | \
    cut -d',' -f1 | \
    sort | uniq -c | sort -rn | head -10 | \
    awk '{printf "  %5d  %s\n", $1, $2}' || echo "  No blocked URI data found"
fi

echo ""

# 4. 違反発生元ページTop 10
echo -e "${BLUE}📊 4. Top 10 Document URIs (Where violations occurred)${NC}"
echo "----------------------------------------"

if grep -q '"document_uri"' "$LOG_FILE" 2>/dev/null; then
  # JSON形式のログの場合
  grep "document_uri" "$LOG_FILE" | \
    grep -oP '"document_uri":"[^"]*"' | \
    cut -d: -f2- | tr -d '"' | \
    sort | uniq -c | sort -rn | head -10 | \
    awk '{count=$1; $1=""; uri=$0; gsub(/^ /, "", uri); printf "  %5d  %s\n", count, uri}'
else
  # プレーンテキストログの場合
  grep "document-uri:" "$LOG_FILE" 2>/dev/null | \
    sed 's/.*document-uri: *//' | \
    cut -d',' -f1 | \
    sort | uniq -c | sort -rn | head -10 | \
    awk '{printf "  %5d  %s\n", $1, $2}' || echo "  No document URI data found"
fi

echo ""

# 5. 違反率計算（推定）
echo -e "${BLUE}📊 5. Violation Rate Estimation${NC}"
echo "----------------------------------------"

# Laravelアクセスログから総リクエスト数を推定（laravel.logの行数）
LARAVEL_LOG="backend/laravel-api/storage/logs/laravel.log"

if [ -f "$LARAVEL_LOG" ]; then
  # 同期間のリクエスト数を推定（簡易版: laravel.logの行数）
  TOTAL_REQUESTS=$(wc -l < "$LARAVEL_LOG" 2>/dev/null || echo "0")

  if [ "$TOTAL_REQUESTS" -gt 0 ]; then
    VIOLATION_RATE=$(echo "scale=4; ($TOTAL_VIOLATIONS / $TOTAL_REQUESTS) * 100" | bc)

    echo "Total requests (estimated): $TOTAL_REQUESTS"
    echo -e "Violation rate: ${YELLOW}${VIOLATION_RATE}%${NC}"
    echo ""

    # 基準: 0.1% 以下
    THRESHOLD=0.1
    if (( $(echo "$VIOLATION_RATE < $THRESHOLD" | bc -l) )); then
      echo -e "${GREEN}✅ Violation rate is below 0.1% threshold${NC}"
      echo "You can proceed to Enforce mode if the Report-Only period is complete."
    else
      echo -e "${YELLOW}⚠️  Violation rate exceeds 0.1% threshold${NC}"
      echo "Please investigate and fix violations before switching to Enforce mode."
    fi
  else
    echo -e "${YELLOW}⚠️  Cannot calculate violation rate (no requests found in laravel.log)${NC}"
  fi
else
  echo -e "${YELLOW}⚠️  Cannot calculate violation rate (laravel.log not found)${NC}"
  echo "Manual calculation required. Use application metrics to determine total requests."
fi

echo ""

# 6. 推奨アクション
echo "========================================"
echo -e "${BLUE}📋 Recommended Actions${NC}"
echo "========================================"
echo ""

if [ "$TOTAL_VIOLATIONS" -gt 0 ]; then
  echo "1. Review Top Violated Directives:"
  echo "   - Check if violations are legitimate (external resources needed)"
  echo "   - Update CSP policy to allow required resources"
  echo "   - Or block malicious content if violations indicate XSS attempts"
  echo ""

  echo "2. Investigate Blocked URIs:"
  echo "   - Identify patterns (CDN domains, analytics, fonts, etc.)"
  echo "   - Add trusted domains to appropriate CSP directives"
  echo "   - Example: Add 'https://cdn.example.com' to script-src"
  echo ""

  echo "3. Check Document URIs:"
  echo "   - Identify pages with most violations"
  echo "   - Review these pages for inline scripts/styles"
  echo "   - Refactor to use external resources or nonces"
  echo ""

  echo "4. Update CSP Policy:"
  echo "   - Edit config/security.php (Laravel)"
  echo "   - Edit frontend/security-config.ts (Next.js)"
  echo "   - Test changes in staging environment"
  echo ""

  echo "5. Continue Report-Only Mode:"
  echo "   - Collect violations for at least 1 week"
  echo "   - Ensure violation rate < 0.1%"
  echo "   - Get security team approval before Enforce mode"
fi

echo ""
echo -e "${GREEN}✅ Analysis complete${NC}"
echo ""
echo "For detailed violation logs, use:"
echo "  grep 'CSP Violation' $LOG_FILE | tail -20"
echo ""
echo "To monitor real-time violations:"
echo "  tail -f $LOG_FILE | grep 'CSP Violation'"
