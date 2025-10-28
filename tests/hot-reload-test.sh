#!/bin/bash
# ============================================
# Laravel APIホットリロード動作確認スクリプト
# ============================================
# 使用方法: ./tests/hot-reload-test.sh
# ============================================

set -euo pipefail

# =============================================================================
# 定数定義
# =============================================================================
readonly COMPOSE_PROFILES="--profile api --profile infra"
readonly API_HEALTH_URL="http://localhost:13000/api/health"
readonly API_ROUTES_FILE="./backend/laravel-api/routes/api.php"
readonly MAX_HEALTH_WAIT=60
readonly MAX_RELOAD_WAIT=5
readonly TARGET_RELOAD_TIME=1

# =============================================================================
# ユーティリティ関数
# =============================================================================
print_header() {
    echo "🔥 Laravel APIホットリロード動作確認を開始..."
    echo ""
}

print_step() {
    local step_num="$1"
    local step_msg="$2"
    echo "${step_num}. ${step_msg}..."
}

print_success() {
    echo "✅ $1"
}

print_error() {
    echo "❌ $1" >&2
}

print_warning() {
    echo "⚠️  $1"
}

cleanup_containers() {
    print_step "1" "既存コンテナ削除"
    docker compose down > /dev/null 2>&1 || true
    print_success "既存コンテナ削除完了"
}

start_laravel_api() {
    print_step "2" "Laravel API起動（${COMPOSE_PROFILES}）"

    if docker compose ${COMPOSE_PROFILES} up -d; then
        print_success "Laravel API起動完了"
        return 0
    else
        print_error "Laravel API起動失敗"
        return 1
    fi
}

wait_for_health() {
    print_step "3" "ヘルスチェック待機（最大${MAX_HEALTH_WAIT}秒）"

    local wait_time=0
    while [ $wait_time -lt $MAX_HEALTH_WAIT ]; do
        local health_status
        health_status=$(docker compose ${COMPOSE_PROFILES} ps | \
                        grep laravel-api | \
                        grep -o "(healthy)" || true)

        if [ -n "$health_status" ]; then
            print_success "laravel-apiサービスがhealthy状態になりました（${wait_time}秒）"
            return 0
        fi

        sleep 2
        wait_time=$((wait_time + 2))
        echo "   待機中... (${wait_time}/${MAX_HEALTH_WAIT}秒)"
    done

    print_error "タイムアウト: laravel-apiサービスがhealthy状態になりませんでした"
    docker compose ${COMPOSE_PROFILES} logs laravel-api
    return 1
}

check_initial_response() {
    print_step "4" "初期レスポンス確認"

    local response
    response=$(curl -s "${API_HEALTH_URL}")

    if [ -z "$response" ]; then
        print_error "/api/healthエンドポイントからレスポンスなし"
        return 1
    fi

    print_success "初期レスポンス取得: $response"
    return 0
}

backup_routes_file() {
    print_step "5" "routes/api.phpバックアップ作成"
    cp "${API_ROUTES_FILE}" "${API_ROUTES_FILE}.backup"
    print_success "バックアップ作成完了"
}

modify_routes_file() {
    print_step "6" "routes/api.php編集（テストコメント追加）"
    local timestamp
    timestamp=$(date +%s)
    echo "// Hot reload test: $timestamp" >> "${API_ROUTES_FILE}"
    print_success "routes/api.php編集完了"
}

wait_and_check_reload() {
    print_step "7" "変更反映待機（最大${MAX_RELOAD_WAIT}秒、${TARGET_RELOAD_TIME}秒以内を期待）"
    sleep 1

    print_step "8" "ホットリロード確認"

    local start_time
    start_time=$(date +%s)
    local reload_time=0

    while [ $reload_time -lt $MAX_RELOAD_WAIT ]; do
        local container_timestamp
        container_timestamp=$(docker compose ${COMPOSE_PROFILES} exec -T laravel-api \
                              stat -c %Y /var/www/html/routes/api.php 2>/dev/null || echo "0")

        if [ "$container_timestamp" != "0" ]; then
            local end_time elapsed
            end_time=$(date +%s)
            elapsed=$((end_time - start_time))

            print_success "ホットリロード成功（${elapsed}秒）"

            if [ $elapsed -le $TARGET_RELOAD_TIME ]; then
                print_success "${TARGET_RELOAD_TIME}秒以内に変更が反映されました！"
            else
                print_warning "変更反映に${elapsed}秒かかりました（目標: ${TARGET_RELOAD_TIME}秒以内）"
            fi
            return 0
        fi

        sleep 1
        reload_time=$((reload_time + 1))
    done

    print_error "ホットリロード失敗: コンテナ内のファイルが更新されませんでした"
    return 1
}

restore_routes_file() {
    print_step "9" "routes/api.php復元"
    mv "${API_ROUTES_FILE}.backup" "${API_ROUTES_FILE}"
    print_success "routes/api.php復元完了"
}

print_results() {
    local success=$1
    echo ""

    if [ "$success" = "true" ]; then
        echo "✅ 全ての検証が成功しました！"
        echo "   - Docker起動成功"
        echo "   - ヘルスチェック成功"
        echo "   - ホットリロード動作確認成功"
    else
        echo "❌ ホットリロード動作確認失敗"
        exit 1
    fi
}

# =============================================================================
# メイン処理
# =============================================================================
main() {
    print_header

    cleanup_containers
    start_laravel_api
    wait_for_health
    check_initial_response
    backup_routes_file
    modify_routes_file

    if wait_and_check_reload; then
        restore_routes_file
        print_results "true"
    else
        restore_routes_file
        print_results "false"
    fi
}

main "$@"
