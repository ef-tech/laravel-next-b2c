#!/bin/bash
#
# Laravel PostgreSQL並列テストセットアップスクリプト
# 使用方法: ./scripts/parallel-test-setup.sh [processes]
#

set -e

LARAVEL_DIR="./backend/laravel-api"
PROCESSES=${1:-4}

echo "🚀 PostgreSQL並列テスト環境をセットアップします"
echo "   プロセス数: $PROCESSES"
echo ""

# Laravel APIディレクトリの存在確認
if [ ! -d "$LARAVEL_DIR" ]; then
    echo "❌ Laravel APIディレクトリが見つかりません: $LARAVEL_DIR"
    exit 1
fi

cd "$LARAVEL_DIR"

# Docker環境確認
echo "🐳 Docker環境を確認します..."
if ! docker compose ps pgsql | grep -q "Up"; then
    echo "⚠️  PostgreSQLコンテナが起動していません"
    echo "   起動しますか？ (y/N)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        echo "   Docker Composeを起動中..."
        docker compose up -d pgsql
        echo "   PostgreSQLの準備完了を待機中..."
        sleep 10
    else
        echo "❌ PostgreSQLコンテナを起動してください: docker compose up -d pgsql"
        exit 1
    fi
fi

# 並列テスト用データベース作成
echo ""
echo "📋 並列テスト用データベースを作成します..."
for i in $(seq 1 $PROCESSES); do
    DB_NAME="testing_$i"

    # データベース存在確認
    if docker compose exec -T pgsql psql -U sail -h localhost -p 13432 -d postgres -lqt 2>/dev/null | cut -d \| -f 1 | grep -qw "$DB_NAME"; then
        echo "   ♻️  データベース再利用: $DB_NAME (既に存在します)"
        # 既存のデータベースを削除して再作成
        docker compose exec -T pgsql psql -U sail -h localhost -p 13432 -d postgres -c "DROP DATABASE $DB_NAME;" >/dev/null 2>&1
    fi

    echo "   データベース作成中: $DB_NAME"

    # データベース作成
    docker compose exec -T pgsql psql -U sail -h localhost -p 13432 -d postgres -c "CREATE DATABASE $DB_NAME OWNER sail;" >/dev/null 2>&1

    if [ $? -ne 0 ]; then
        echo "   ❌ データベース作成に失敗しました: $DB_NAME"
        echo "   💡 PostgreSQLログを確認してください: docker compose logs pgsql"
        exit 1
    fi

    # マイグレーション実行
    DB_CONNECTION=pgsql_testing \
    DB_TEST_HOST=pgsql \
    DB_TEST_PORT=13432 \
    DB_TEST_DATABASE="$DB_NAME" \
    DB_TEST_USERNAME=sail \
    DB_TEST_PASSWORD=password \
    php artisan migrate --force --quiet

    echo "   ✅ $DB_NAME セットアップ完了"
done

echo ""
echo "🎉 並列テスト環境のセットアップが完了しました！"
echo ""
echo "🔧 並列テスト実行方法:"
echo "   # 自動並列実行"
echo "   ./vendor/bin/pest --parallel --processes=$PROCESSES"
echo ""
echo "   # 手動でプロセス指定"
echo "   for i in {1..$PROCESSES}; do"
echo "     TEST_TOKEN=\$i DB_DATABASE=testing_\$i ./vendor/bin/pest &"
echo "   done"
echo "   wait"
echo ""
echo "🧹 クリーンアップ方法:"
echo "   ./scripts/parallel-test-cleanup.sh $PROCESSES"