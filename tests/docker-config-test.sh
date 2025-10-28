#!/bin/bash

# Docker設定の検証スクリプト
set -e

echo "📋 Docker設定検証を開始..."

# 1. docker-compose.yml構文チェック
echo "1. docker-compose.yml構文チェック..."
docker compose --profile api --profile infra config > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ docker-compose.yml構文OK"
else
    echo "❌ docker-compose.yml構文エラー"
    exit 1
fi

# 2. Laravel APIサービスのvolume mount設定確認
echo "2. Laravel APIサービスのvolume mount設定確認..."
VOLUME_MOUNT=$(docker compose --profile api --profile infra config | grep -A 70 "laravel-api:" | grep "target: /var/www/html")
if [ -n "$VOLUME_MOUNT" ]; then
    echo "✅ volume mount設定（/var/www/html）あり"
else
    echo "❌ volume mount設定（/var/www/html）なし"
    exit 1
fi

# 3. APP_ENV=local環境変数確認
echo "3. APP_ENV=local環境変数確認..."
APP_ENV=$(docker compose --profile api --profile infra config | grep -A 40 "laravel-api:" | grep "APP_ENV: local")
if [ -n "$APP_ENV" ]; then
    echo "✅ APP_ENV=local環境変数あり"
else
    echo "❌ APP_ENV=local環境変数なし"
    exit 1
fi

echo ""
echo "✅ 全ての検証が成功しました！"
