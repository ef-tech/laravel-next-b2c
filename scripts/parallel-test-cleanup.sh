#!/bin/bash
#
# Laravel PostgreSQL並列テストクリーンアップスクリプト
# 使用方法: ./scripts/parallel-test-cleanup.sh [processes]
#

set -e

LARAVEL_DIR="./backend/laravel-api"
PROCESSES=${1:-4}

echo "🧹 PostgreSQL並列テスト環境をクリーンアップします"
echo "   削除対象プロセス数: $PROCESSES"
echo ""

# Laravel APIディレクトリの存在確認
if [ ! -d "$LARAVEL_DIR" ]; then
    echo "❌ Laravel APIディレクトリが見つかりません: $LARAVEL_DIR"
    exit 1
fi

cd "$LARAVEL_DIR"

# Docker環境確認
if ! docker compose ps pgsql | grep -q "Up"; then
    echo "⚠️  PostgreSQLコンテナが起動していません"
    echo "クリーンアップを継続しますか？ (y/N)"
    read -r response
    if [[ ! "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        echo "❌ クリーンアップを中止しました"
        exit 1
    fi
fi

# 並列テスト用データベース削除
echo "🗑️  並列テスト用データベースを削除します..."
for i in $(seq 1 $PROCESSES); do
    DB_NAME="testing_$i"
    echo "   データベース削除中: $DB_NAME"
    
    # データベース削除
    if docker compose exec -T pgsql dropdb -U sail --if-exists "$DB_NAME" 2>/dev/null; then
        echo "   ✅ $DB_NAME 削除完了"
    else
        echo "   ⚠️  $DB_NAME が見つかりません（既に削除済み）"
    fi
done

# 一時ファイル削除
echo ""
echo "📁 一時ファイルを削除します..."
TEMP_FILES=(
    "storage/logs/laravel-*.log"
    "storage/framework/cache/data/*"
    "storage/framework/sessions/*"
    "storage/framework/views/*"
    ".phpunit.result.cache"
)

for pattern in "${TEMP_FILES[@]}"; do
    if compgen -G "$pattern" > /dev/null; then
        rm -rf $pattern 2>/dev/null || true
        echo "   ✅ 削除: $pattern"
    fi
done

echo ""
echo "🎉 クリーンアップが完了しました！"
echo ""
echo "📋 残存確認:"
echo "   docker compose exec pgsql psql -U sail -l | grep testing"