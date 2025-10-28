#!/bin/bash
# ============================================
# Docker設定の検証スクリプト
# ============================================
# 使用方法: ./tests/docker-config-test.sh
# ============================================

set -euo pipefail

# =============================================================================
# 定数定義
# =============================================================================
readonly COMPOSE_PROFILES="--profile api --profile infra"
readonly VOLUME_TARGET="/var/www/html"
readonly EXPECTED_APP_ENV="APP_ENV: local"

# =============================================================================
# ユーティリティ関数
# =============================================================================
print_header() {
    echo "📋 Docker設定検証を開始..."
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

print_footer() {
    echo ""
    echo "✅ 全ての検証が成功しました！"
}

# =============================================================================
# 検証関数
# =============================================================================
check_compose_syntax() {
    print_step "1" "docker-compose.yml構文チェック"

    if docker compose ${COMPOSE_PROFILES} config > /dev/null 2>&1; then
        print_success "docker-compose.yml構文OK"
        return 0
    else
        print_error "docker-compose.yml構文エラー"
        return 1
    fi
}

check_volume_mount() {
    print_step "2" "Laravel APIサービスのvolume mount設定確認"

    local volume_mount
    volume_mount=$(docker compose ${COMPOSE_PROFILES} config | \
                   grep -A 70 "laravel-api:" | \
                   grep "target: ${VOLUME_TARGET}" || true)

    if [ -n "$volume_mount" ]; then
        print_success "volume mount設定（${VOLUME_TARGET}）あり"
        return 0
    else
        print_error "volume mount設定（${VOLUME_TARGET}）なし"
        return 1
    fi
}

check_app_env() {
    print_step "3" "APP_ENV=local環境変数確認"

    local app_env
    app_env=$(docker compose ${COMPOSE_PROFILES} config | \
              grep -A 40 "laravel-api:" | \
              grep "${EXPECTED_APP_ENV}" || true)

    if [ -n "$app_env" ]; then
        print_success "APP_ENV=local環境変数あり"
        return 0
    else
        print_error "APP_ENV=local環境変数なし"
        return 1
    fi
}

# =============================================================================
# メイン処理
# =============================================================================
main() {
    print_header

    check_compose_syntax
    check_volume_mount
    check_app_env

    print_footer
}

main "$@"
