#!/usr/bin/env bash
#
# Setup Main Script
# Laravel + Next.js モノレポ環境の一括セットアップスクリプト
#
# Usage:
#   ./scripts/setup/main.sh [OPTIONS]
#
# Options:
#   --ci          CI/CDモードで実行（対話的プロンプトなし）
#   --from STEP   指定されたステップから部分的に再実行
#   --help        ヘルプを表示
#

set -e

# ==============================================================================
# 初期化
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# 共通ライブラリを読み込み
source "$PROJECT_ROOT/scripts/lib/setup-lib.sh"

# プロジェクトルートに移動
cd "$PROJECT_ROOT"

# ==============================================================================
# 引数解析
# ==============================================================================

FROM_STEP=""
SHOW_HELP=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --ci)
            export CI=true
            CI_MODE=true
            shift
            ;;
        --from)
            FROM_STEP="$2"
            shift 2
            ;;
        --help)
            SHOW_HELP=true
            shift
            ;;
        *)
            log_error "不明なオプション: $1"
            SHOW_HELP=true
            shift
            ;;
    esac
done

if [ "$SHOW_HELP" = "true" ]; then
    cat <<EOF
Usage: $0 [OPTIONS]

一括セットアップスクリプト - Laravel + Next.js モノレポ環境を15分以内に構築

Options:
  --ci          CI/CDモードで実行（対話的プロンプトなし）
  --from STEP   指定されたステップから部分的に再実行
  --help        このヘルプを表示

Examples:
  # 通常のセットアップ
  $0

  # CI/CDモード
  $0 --ci

  # 部分的再実行（install_dependenciesステップから）
  $0 --from install_dependencies

Available steps:
  - check_prerequisites
  - setup_env
  - install_dependencies
  - start_services
  - verify_setup

EOF
    exit 0
fi

# ==============================================================================
# セットアップ開始
# ==============================================================================

log_info "======================================"
log_info "🚀 Laravel + Next.js セットアップ開始"
log_info "======================================"
echo ""

# OS検出
detect_os
log_info "検出されたOS: $DETECTED_OS"
echo ""

# 進捗読み込み
load_progress

# 開始時刻記録
SETUP_START_TIME=$(date +%s)

# ==============================================================================
# セットアップステップ定義
# ==============================================================================

# ステップ実行関数
execute_step() {
    local step_name=$1
    local step_title=$2
    local step_function=$3

    # FROM_STEPが指定されている場合、そのステップまでスキップ
    if [ -n "$FROM_STEP" ] && [ "$step_name" != "$FROM_STEP" ] && ! is_step_completed "$FROM_STEP"; then
        log_info "⏭️  $step_title をスキップ"
        return 0
    fi

    # 既に完了している場合はスキップ（FROM_STEP指定時を除く）
    if [ "$step_name" != "$FROM_STEP" ] && is_step_completed "$step_name"; then
        log_info "✅ $step_title は完了済み（スキップ）"
        return 0
    fi

    show_progress "${STEP_CURRENT}" "${STEP_TOTAL}" "$step_title"

    if measure_step_time "$step_title" $step_function; then
        save_progress "$step_name"
        log_info "✅ $step_title 完了"
    else
        log_error "❌ $step_title 失敗"
        log_error "詳細なログは .setup.log を確認してください"
        exit 1
    fi

    echo ""
}

# ==============================================================================
# Phase 1: 前提条件チェック（簡易版）
# ==============================================================================

check_prerequisites() {
    log_info "前提条件をチェックしています..."

    # Docker確認
    if ! command -v docker &>/dev/null; then
        log_error "Dockerがインストールされていません"
        log_info "インストール方法: $(get_install_guide docker)"
        return 1
    fi
    log_info "  Docker: $(docker --version 2>&1 | head -1)"

    # Docker Compose確認
    if ! docker compose version &>/dev/null; then
        log_error "Docker Composeがインストールされていません"
        return 1
    fi
    log_info "  Docker Compose: $(docker compose version 2>&1 | head -1)"

    # Node.js確認
    if ! command -v node &>/dev/null; then
        log_warn "Node.jsがインストールされていません"
        log_info "インストール方法: $(get_install_guide node)"
        # 警告のみで続行（Dockerで実行可能）
    else
        log_info "  Node.js: $(node --version 2>&1)"
    fi

    # PHP確認
    if ! command -v php &>/dev/null; then
        log_warn "PHPがインストールされていません"
        log_info "インストール方法: $(get_install_guide php)"
        # 警告のみで続行（Dockerで実行可能）
    else
        log_info "  PHP: $(php --version 2>&1 | head -1)"
    fi

    log_info "前提条件チェック完了"
}

# ==============================================================================
# Phase 2: 環境変数セットアップ（簡易版）
# ==============================================================================

setup_env() {
    log_info "環境変数をセットアップしています..."

    # Laravel API
    if [ ! -f "backend/laravel-api/.env" ]; then
        cp "backend/laravel-api/.env.example" "backend/laravel-api/.env"
        log_info "  Laravel API .env を作成しました"
    else
        log_warn "  Laravel API .env は既に存在します（スキップ）"
    fi

    # User App
    if [ ! -f "frontend/user-app/.env.local" ]; then
        if [ -f "frontend/user-app/.env.example" ]; then
            cp "frontend/user-app/.env.example" "frontend/user-app/.env.local"
            log_info "  User App .env.local を作成しました"
        fi
    else
        log_warn "  User App .env.local は既に存在します（スキップ）"
    fi

    # Admin App
    if [ ! -f "frontend/admin-app/.env.local" ]; then
        if [ -f "frontend/admin-app/.env.example" ]; then
            cp "frontend/admin-app/.env.example" "frontend/admin-app/.env.local"
            log_info "  Admin App .env.local を作成しました"
        fi
    else
        log_warn "  Admin App .env.local は既に存在します（スキップ）"
    fi

    log_info "環境変数セットアップ完了"
}

# ==============================================================================
# Phase 3: 依存関係インストール（簡易版）
# ==============================================================================

install_dependencies() {
    log_info "依存関係をインストールしています..."

    # Composer install
    log_info "  Composer依存関係をインストール中..."
    if retry_with_exponential_backoff bash -c "cd backend/laravel-api && composer install --no-interaction --prefer-dist --quiet"; then
        log_info "  ✅ Composer install 完了"
    else
        log_error "  ❌ Composer install 失敗"
        return 1
    fi

    # npm install
    log_info "  npm依存関係をインストール中..."
    if retry_with_exponential_backoff npm install --silent; then
        log_info "  ✅ npm install 完了"
    else
        log_error "  ❌ npm install 失敗"
        return 1
    fi

    # Docker images pull
    log_info "  Dockerイメージをプル中..."
    if retry_with_exponential_backoff docker compose pull --quiet; then
        log_info "  ✅ Docker images pull 完了"
    else
        log_error "  ❌ Docker images pull 失敗"
        return 1
    fi

    log_info "依存関係インストール完了"
}

# ==============================================================================
# Phase 4: サービス起動（簡易版）
# ==============================================================================

start_services() {
    log_info "サービスを起動しています..."

    # Docker Compose up
    log_info "  Docker Composeでサービスを起動中..."
    docker compose up -d

    # ヘルスチェック待機
    log_info "  サービスの起動を待機中..."
    sleep 10

    log_info "サービス起動完了"
}

# ==============================================================================
# Phase 5: セットアップ検証（簡易版）
# ==============================================================================

verify_setup() {
    log_info "セットアップを検証しています..."

    # Docker Composeサービス確認
    log_info "  サービスステータス:"
    docker compose ps

    log_info "セットアップ検証完了"
}

# ==============================================================================
# メイン実行
# ==============================================================================

# ステップカウンタ
STEP_TOTAL=5
STEP_CURRENT=1

# 各ステップ実行
execute_step "check_prerequisites" "前提条件チェック" check_prerequisites
STEP_CURRENT=$((STEP_CURRENT + 1))

execute_step "setup_env" "環境変数セットアップ" setup_env
STEP_CURRENT=$((STEP_CURRENT + 1))

execute_step "install_dependencies" "依存関係インストール" install_dependencies
STEP_CURRENT=$((STEP_CURRENT + 1))

execute_step "start_services" "サービス起動" start_services
STEP_CURRENT=$((STEP_CURRENT + 1))

execute_step "verify_setup" "セットアップ検証" verify_setup

# ==============================================================================
# セットアップ完了
# ==============================================================================

SETUP_END_TIME=$(date +%s)
SETUP_DURATION=$((SETUP_END_TIME - SETUP_START_TIME))

echo ""
log_info "======================================"
log_info "✅ セットアップ完了！"
log_info "======================================"
log_info "所要時間: ${SETUP_DURATION}秒"
echo ""
log_info "🌐 アクセスURL:"
log_info "  Laravel API:  http://localhost:13000/api/health"
log_info "  User App:     http://localhost:13001"
log_info "  Admin App:    http://localhost:13002"
echo ""
log_info "📝 次のステップ:"
log_info "  1. Laravel APIのマイグレーション: cd backend/laravel-api && php artisan migrate"
log_info "  2. シーディング: cd backend/laravel-api && php artisan db:seed"
log_info "  3. フロントエンドアプリにアクセス: http://localhost:13001"
echo ""

# 進捗マーカー削除
cleanup_progress

exit 0
