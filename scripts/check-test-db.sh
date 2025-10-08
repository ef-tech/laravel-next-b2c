#!/bin/bash
#
# テスト用データベース存在確認スクリプト
# 使用方法: ./scripts/check-test-db.sh
#

set -e

# Docker環境確認
if ! docker compose ps pgsql | grep -q "Up"; then
    echo "❌ PostgreSQLコンテナが起動していません"
    echo "   起動コマンド: docker compose up -d pgsql"
    exit 1
fi

echo "🔍 テスト用データベースを確認しています..."
echo ""

# app_test データベース確認
if docker compose exec -T pgsql psql -U sail -h localhost -p 13432 -d postgres -lqt 2>/dev/null | cut -d \| -f 1 | grep -qw "app_test"; then
    echo "✅ app_test: 存在します"
else
    echo "❌ app_test: 存在しません"
    echo "   作成コマンド: docker compose exec -T pgsql psql -U sail -h localhost -p 13432 -d postgres -c \"CREATE DATABASE app_test OWNER sail;\""
fi

# 並列テスト用データベース確認（testing_1〜testing_4）
for i in 1 2 3 4; do
    DB_NAME="testing_$i"
    if docker compose exec -T pgsql psql -U sail -h localhost -p 13432 -d postgres -lqt 2>/dev/null | cut -d \| -f 1 | grep -qw "$DB_NAME"; then
        echo "✅ $DB_NAME: 存在します"
    else
        echo "❌ $DB_NAME: 存在しません"
    fi
done

echo ""
echo "💡 並列テスト環境セットアップ: ./scripts/parallel-test-setup.sh"
