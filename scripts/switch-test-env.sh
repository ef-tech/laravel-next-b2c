#!/bin/bash
#
# Laravel テスト環境切り替えスクリプト
# 使用方法: ./scripts/switch-test-env.sh {sqlite|pgsql}
#

set -e

LARAVEL_DIR="./backend/laravel-api"

# 引数チェック
if [ $# -ne 1 ]; then
    echo "❌ 引数が不正です"
    echo "使用方法: $0 {sqlite|pgsql}"
    exit 1
fi

# Laravel APIディレクトリの存在確認
if [ ! -d "$LARAVEL_DIR" ]; then
    echo "❌ Laravel APIディレクトリが見つかりません: $LARAVEL_DIR"
    exit 1
fi

case "$1" in
    "sqlite")
        if [ -f "$LARAVEL_DIR/.env.testing.sqlite" ]; then
            cp "$LARAVEL_DIR/.env.testing.sqlite" "$LARAVEL_DIR/.env.testing"
            echo "✅ SQLiteテスト環境に切り替えました"
            echo "   - データベース: SQLite (in-memory)"
            echo "   - 実行コマンド: ./vendor/bin/pest"
        else
            echo "❌ SQLite設定ファイルが見つかりません: $LARAVEL_DIR/.env.testing.sqlite"
            exit 1
        fi
        ;;
    "pgsql"|"postgresql")
        if [ -f "$LARAVEL_DIR/.env.testing.pgsql" ]; then
            cp "$LARAVEL_DIR/.env.testing.pgsql" "$LARAVEL_DIR/.env.testing"
            echo "✅ PostgreSQLテスト環境に切り替えました"
            echo "   - データベース: PostgreSQL"
            echo "   - ホスト: 127.0.0.1:13432"
            echo "   - 実行コマンド: ./vendor/bin/pest --env=testing"
            echo ""
            echo "📋 Docker環境の起動が必要です:"
            echo "   docker compose up -d pgsql"
        else
            echo "❌ PostgreSQL設定ファイルが見つかりません: $LARAVEL_DIR/.env.testing.pgsql"
            exit 1
        fi
        ;;
    *)
        echo "❌ 不正な環境名です: $1"
        echo "使用方法: $0 {sqlite|pgsql}"
        exit 1
        ;;
esac

echo ""
echo "🔄 設定を確認してください:"
echo "   cat $LARAVEL_DIR/.env.testing"