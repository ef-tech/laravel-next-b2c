#!/bin/bash
#
# Performance Measurement Script for Security Headers
#
# このスクリプトはセキュリティヘッダー追加によるパフォーマンス影響を測定します。
#
# Usage:
#   bash scripts/measure-performance.sh <URL>
#
# Arguments:
#   URL: 測定対象のURL（例: http://localhost:13000/api/health）
#
# Exit codes:
#   0: 測定成功（基準内）
#   1: 測定失敗 or 基準超過（警告のみ）

set -e

# カラー出力設定
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 引数チェック
if [ $# -ne 1 ]; then
  echo -e "${RED}Usage: $0 <URL>${NC}"
  echo "  Example: $0 http://localhost:13000/api/health"
  exit 1
fi

URL="$1"

echo -e "${BLUE}🚀 Performance Measurement${NC}"
echo "================================"
echo "URL: $URL"
echo ""

# Apache Bench 存在確認
if ! command -v ab &> /dev/null; then
  echo -e "${YELLOW}⚠️  Apache Bench (ab) not found. Installing...${NC}"

  # OS判定してインストール
  if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS: 既にインストールされているはず
    echo -e "${RED}❌ Apache Bench not found on macOS (should be pre-installed)${NC}"
    echo "Please check your system or install with: brew install httpd"
    exit 1
  elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux: apache2-utils インストール
    echo "Installing apache2-utils..."
    sudo apt-get update && sudo apt-get install -y apache2-utils
  else
    echo -e "${RED}❌ Unsupported OS: $OSTYPE${NC}"
    exit 1
  fi
fi

# 1. ヘッダーサイズ測定
echo -e "${BLUE}📊 1. Header Size Measurement${NC}"
echo "--------------------------------"

HEADERS=$(curl -sI "$URL")
HEADER_SIZE=$(echo "$HEADERS" | wc -c)
HEADER_SIZE_KB=$(echo "scale=2; $HEADER_SIZE / 1024" | bc)

echo "Header Size: ${HEADER_SIZE} bytes (${HEADER_SIZE_KB} KB)"

# 基準: 1KB (1024 bytes) 以下
HEADER_SIZE_THRESHOLD=1024
if [ "$HEADER_SIZE" -le "$HEADER_SIZE_THRESHOLD" ]; then
  echo -e "${GREEN}✅ Header size is within threshold (<= 1KB)${NC}"
  HEADER_SIZE_PASS=true
else
  echo -e "${YELLOW}⚠️  Header size exceeds threshold (> 1KB)${NC}"
  HEADER_SIZE_PASS=false
fi

echo ""

# 2. レスポンスタイム測定 (Apache Bench)
echo -e "${BLUE}📊 2. Response Time Measurement (Apache Bench)${NC}"
echo "--------------------------------"
echo "Running: ab -n 1000 -c 10 $URL"
echo ""

# Apache Bench 実行（エラー出力を抑制）
AB_OUTPUT=$(ab -n 1000 -c 10 "$URL" 2>&1 || true)

# 結果抽出
REQUESTS_PER_SEC=$(echo "$AB_OUTPUT" | grep "Requests per second" | awk '{print $4}')
TIME_PER_REQUEST=$(echo "$AB_OUTPUT" | grep "Time per request" | head -1 | awk '{print $4}')
MEAN_TIME=$(echo "$AB_OUTPUT" | grep "across all concurrent requests" | awk '{print $4}')

# 出力がない場合のフォールバック
if [ -z "$TIME_PER_REQUEST" ]; then
  echo -e "${YELLOW}⚠️  Apache Bench failed or returned no data${NC}"
  echo "Raw output:"
  echo "$AB_OUTPUT"
  TIME_PER_REQUEST="N/A"
  REQUESTS_PER_SEC="N/A"
  MEAN_TIME="N/A"
  AB_PASS=false
else
  echo "Requests per second: ${REQUESTS_PER_SEC} [#/sec]"
  echo "Time per request: ${TIME_PER_REQUEST} [ms] (mean)"
  echo "Time per request (across all): ${MEAN_TIME} [ms]"

  # 基準: レスポンスタイム増加が 5ms 以下
  # (セキュリティヘッダー追加前のベースラインと比較が必要だが、ここでは絶対値で評価)
  # ベースライン想定: 10ms 以下が目標
  RESPONSE_TIME_THRESHOLD=15.0

  # 小数点比較（bcコマンド使用）
  if (( $(echo "$TIME_PER_REQUEST < $RESPONSE_TIME_THRESHOLD" | bc -l) )); then
    echo -e "${GREEN}✅ Response time is acceptable (< ${RESPONSE_TIME_THRESHOLD}ms)${NC}"
    AB_PASS=true
  else
    echo -e "${YELLOW}⚠️  Response time is high (>= ${RESPONSE_TIME_THRESHOLD}ms)${NC}"
    AB_PASS=false
  fi
fi

echo ""

# 3. パフォーマンスサマリー
echo "================================"
echo -e "${BLUE}📊 Performance Summary${NC}"
echo "================================"
echo ""

echo "Header Size:"
if [ "$HEADER_SIZE_PASS" = true ]; then
  echo -e "  ${GREEN}✅ ${HEADER_SIZE} bytes (${HEADER_SIZE_KB} KB) - OK${NC}"
else
  echo -e "  ${YELLOW}⚠️  ${HEADER_SIZE} bytes (${HEADER_SIZE_KB} KB) - Exceeds 1KB threshold${NC}"
fi

echo ""
echo "Response Time:"
if [ "$AB_PASS" = true ]; then
  echo -e "  ${GREEN}✅ ${TIME_PER_REQUEST} ms - OK${NC}"
elif [ "$AB_PASS" = false ] && [ "$TIME_PER_REQUEST" != "N/A" ]; then
  echo -e "  ${YELLOW}⚠️  ${TIME_PER_REQUEST} ms - High response time${NC}"
else
  echo -e "  ${YELLOW}⚠️  Measurement failed${NC}"
fi

echo ""
echo "Throughput:"
if [ "$REQUESTS_PER_SEC" != "N/A" ]; then
  echo -e "  ${GREEN}ℹ️  ${REQUESTS_PER_SEC} requests/sec${NC}"
else
  echo -e "  ${YELLOW}⚠️  N/A${NC}"
fi

echo ""
echo "================================"

# 4. パフォーマンス基準判定
# 警告のみで失敗させない（仕様通り）
if [ "$HEADER_SIZE_PASS" = false ] || [ "$AB_PASS" = false ]; then
  echo -e "${YELLOW}⚠️  Performance Warning: Some metrics exceed recommended thresholds${NC}"
  echo -e "${YELLOW}   This is informational only - workflow will not fail.${NC}"
  echo ""
  echo "Recommendations:"

  if [ "$HEADER_SIZE_PASS" = false ]; then
    echo "  - Consider optimizing CSP policy to reduce header size"
    echo "  - Remove unnecessary security header directives"
  fi

  if [ "$AB_PASS" = false ] && [ "$TIME_PER_REQUEST" != "N/A" ]; then
    echo "  - Review SecurityHeaders middleware performance"
    echo "  - Consider caching CSP policy strings"
    echo "  - Check database/Redis connection latency"
  fi

  # 警告のみ、exit 0 で成功扱い
  exit 0
else
  echo -e "${GREEN}✅ All performance metrics are within acceptable ranges${NC}"
  exit 0
fi
