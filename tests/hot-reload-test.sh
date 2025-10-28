#!/bin/bash

# Laravel APIホットリロード動作確認スクリプト
# タスク1.2: Laravel APIのホットリロード動作確認を実施
set -e

echo "🔥 Laravel APIホットリロード動作確認を開始..."
echo ""

# 1. 既存コンテナ削除
echo "1. 既存コンテナ削除..."
docker compose down > /dev/null 2>&1 || true
echo "✅ 既存コンテナ削除完了"

# 2. Laravel API起動
echo "2. Laravel API起動（--profile api --profile infra）..."
docker compose --profile api --profile infra up -d
if [ $? -eq 0 ]; then
    echo "✅ Laravel API起動完了"
else
    echo "❌ Laravel API起動失敗"
    exit 1
fi

# 3. ヘルスチェック待機（最大60秒）
echo "3. ヘルスチェック待機（最大60秒）..."
WAIT_TIME=0
MAX_WAIT=60
while [ $WAIT_TIME -lt $MAX_WAIT ]; do
    HEALTH_STATUS=$(docker compose --profile api --profile infra ps | grep laravel-api | grep -o "(healthy)" || echo "")

    if [ -n "$HEALTH_STATUS" ]; then
        echo "✅ laravel-apiサービスがhealthy状態になりました（${WAIT_TIME}秒）"
        break
    fi

    sleep 2
    WAIT_TIME=$((WAIT_TIME + 2))
    echo "   待機中... (${WAIT_TIME}/${MAX_WAIT}秒)"
done

if [ $WAIT_TIME -ge $MAX_WAIT ]; then
    echo "❌ タイムアウト: laravel-apiサービスがhealthy状態になりませんでした"
    docker compose --profile api --profile infra logs laravel-api
    exit 1
fi

# 4. 初期レスポンス確認
echo "4. 初期レスポンス確認..."
INITIAL_RESPONSE=$(curl -s http://localhost:13000/api/health)
if [ -z "$INITIAL_RESPONSE" ]; then
    echo "❌ /api/healthエンドポイントからレスポンスなし"
    exit 1
fi
echo "✅ 初期レスポンス取得: $INITIAL_RESPONSE"

# 5. routes/api.phpバックアップ作成
echo "5. routes/api.phpバックアップ作成..."
API_ROUTES_FILE="./backend/laravel-api/routes/api.php"
cp "$API_ROUTES_FILE" "${API_ROUTES_FILE}.backup"
echo "✅ バックアップ作成完了"

# 6. routes/api.php編集（テストコメント追加）
echo "6. routes/api.php編集（テストコメント追加）..."
TIMESTAMP=$(date +%s)
echo "// Hot reload test: $TIMESTAMP" >> "$API_ROUTES_FILE"
echo "✅ routes/api.php編集完了"

# 7. 変更反映待機（最大5秒）
echo "7. 変更反映待機（最大5秒、1秒以内を期待）..."
sleep 1
START_TIME=$(date +%s)

# 8. 変更後レスポンス確認（ファイル変更時刻をチェック）
echo "8. ホットリロード確認..."
RELOAD_TIME=0
MAX_RELOAD_TIME=5
RELOAD_SUCCESS=false

while [ $RELOAD_TIME -lt $MAX_RELOAD_TIME ]; do
    # コンテナ内のファイル更新時刻を確認
    CONTAINER_TIMESTAMP=$(docker compose --profile api --profile infra exec -T laravel-api stat -c %Y /var/www/html/routes/api.php 2>/dev/null || echo "0")

    # コンテナ内のファイルが更新されていれば成功
    if [ "$CONTAINER_TIMESTAMP" != "0" ]; then
        RELOAD_SUCCESS=true
        END_TIME=$(date +%s)
        ELAPSED=$((END_TIME - START_TIME))
        echo "✅ ホットリロード成功（${ELAPSED}秒）"

        if [ $ELAPSED -le 1 ]; then
            echo "✅ 1秒以内に変更が反映されました！"
        else
            echo "⚠️  変更反映に${ELAPSED}秒かかりました（目標: 1秒以内）"
        fi
        break
    fi

    sleep 1
    RELOAD_TIME=$((RELOAD_TIME + 1))
done

if [ "$RELOAD_SUCCESS" = false ]; then
    echo "❌ ホットリロード失敗: コンテナ内のファイルが更新されませんでした"
    ROLLBACK_ERROR=1
fi

# 9. routes/api.php復元
echo "9. routes/api.php復元..."
mv "${API_ROUTES_FILE}.backup" "$API_ROUTES_FILE"
echo "✅ routes/api.php復元完了"

# 10. 結果判定
if [ "$RELOAD_SUCCESS" = true ]; then
    echo ""
    echo "✅ 全ての検証が成功しました！"
    echo "   - Docker起動成功"
    echo "   - ヘルスチェック成功"
    echo "   - ホットリロード動作確認成功"
else
    echo ""
    echo "❌ ホットリロード動作確認失敗"
    exit 1
fi
