#!/usr/bin/env bash
#
# Setup Library
# 一括セットアップスクリプトの共通ライブラリ
#
# このライブラリは以下の機能を提供します：
# - ログ機能（info/warn/error）
# - 進捗表示
# - 機密情報マスキング
# - CI/CDモード検出
#

set -e

# ==============================================================================
# グローバル変数
# ==============================================================================

# ログファイルパス（デフォルト）
LOG_FILE="${LOG_FILE:-.setup.log}"

# CI/CDモード検出
CI_MODE=false
if [ "${CI:-false}" = "true" ] || [ "${GITHUB_ACTIONS:-false}" = "true" ]; then
    CI_MODE=true
fi

# OS検出結果
DETECTED_OS=""
PACKAGE_MANAGER=""

# 進捗マーカーファイル
PROGRESS_FILE=".setup-progress"

# カラーコード（CI/CDモードでは無効化）
if [ "$CI_MODE" = "true" ]; then
    COLOR_RESET=""
    COLOR_GREEN=""
    COLOR_YELLOW=""
    COLOR_RED=""
    COLOR_BLUE=""
else
    COLOR_RESET="\033[0m"
    COLOR_GREEN="\033[0;32m"
    COLOR_YELLOW="\033[0;33m"
    COLOR_RED="\033[0;31m"
    COLOR_BLUE="\033[0;34m"
fi

# ==============================================================================
# ログ機能
# ==============================================================================

# 情報ログ
# Usage: log_info "message"
log_info() {
    local message="$1"
    echo -e "${COLOR_GREEN}✅ ${message}${COLOR_RESET}"
    echo "[INFO] $(date '+%Y-%m-%d %H:%M:%S') $message" >> "$LOG_FILE"
}

# 警告ログ
# Usage: log_warn "message"
log_warn() {
    local message="$1"
    echo -e "${COLOR_YELLOW}⚠️  ${message}${COLOR_RESET}"
    echo "[WARN] $(date '+%Y-%m-%d %H:%M:%S') $message" >> "$LOG_FILE"
}

# エラーログ
# Usage: log_error "message"
log_error() {
    local message="$1"
    echo -e "${COLOR_RED}❌ ${message}${COLOR_RESET}" >&2
    echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') $message" >> "$LOG_FILE"
}

# デバッグログ（CI/CDモードまたはDEBUG=true時のみ表示）
# Usage: log_debug "message"
log_debug() {
    local message="$1"
    if [ "$CI_MODE" = "true" ] || [ "${DEBUG:-false}" = "true" ]; then
        echo -e "${COLOR_BLUE}🔍 ${message}${COLOR_RESET}"
    fi
    echo "[DEBUG] $(date '+%Y-%m-%d %H:%M:%S') $message" >> "$LOG_FILE"
}

# ==============================================================================
# 進捗表示
# ==============================================================================

# 進捗表示
# Usage: show_progress <current> <total> <step_name>
show_progress() {
    local current=$1
    local total=$2
    local step_name=$3

    echo -e "\n${COLOR_BLUE}🚀 [$current/$total] $step_name${COLOR_RESET}"
}

# ==============================================================================
# セキュリティ機能
# ==============================================================================

# 機密情報マスキング
# Usage: mask_sensitive "text"
mask_sensitive() {
    local text="$1"

    # パスワード、トークン、APIキーをマスキング（複数パターンを一度に処理）
    echo "$text" | sed -e 's/password=[^ ]*/password=***/g' \
                       -e 's/token=[^ ]*/token=***/g' \
                       -e 's/api_key=[^ ]*/api_key=***/g' \
                       -e 's/APP_KEY=[^ ]*/APP_KEY=***/g' \
                       -e 's/DB_PASSWORD=[^ ]*/DB_PASSWORD=***/g'
}

# ==============================================================================
# GitHub Actions Annotations（CI/CDモード時のみ）
# ==============================================================================

# GitHub Actions エラーannotation
# Usage: gh_error "message"
gh_error() {
    local message="$1"
    if [ "$CI_MODE" = "true" ] && [ "${GITHUB_ACTIONS:-false}" = "true" ]; then
        echo "::error::$message"
    fi
}

# GitHub Actions 警告annotation
# Usage: gh_warning "message"
gh_warning() {
    local message="$1"
    if [ "$CI_MODE" = "true" ] && [ "${GITHUB_ACTIONS:-false}" = "true" ]; then
        echo "::warning::$message"
    fi
}

# GitHub Actions 通知annotation
# Usage: gh_notice "message"
gh_notice() {
    local message="$1"
    if [ "$CI_MODE" = "true" ] && [ "${GITHUB_ACTIONS:-false}" = "true" ]; then
        echo "::notice::$message"
    fi
}

# ==============================================================================
# OS検出と環境差異対応
# ==============================================================================

# パッケージマネージャー検出（Linux/WSL2用）
# Usage: _detect_package_manager
# Sets: PACKAGE_MANAGER
_detect_package_manager() {
    if command -v apt-get &>/dev/null; then
        PACKAGE_MANAGER="apt"
    elif command -v yum &>/dev/null; then
        PACKAGE_MANAGER="yum"
    else
        PACKAGE_MANAGER="apt"  # デフォルト
    fi
}

# OS検出
# Usage: detect_os
# Sets: DETECTED_OS, PACKAGE_MANAGER
detect_os() {
    local os=$(uname -s)

    case "$os" in
        Darwin*)
            DETECTED_OS="macos"
            PACKAGE_MANAGER="brew"
            ;;
        Linux*)
            if grep -qi microsoft /proc/version 2>/dev/null; then
                DETECTED_OS="wsl2"
            else
                DETECTED_OS="linux"
            fi
            _detect_package_manager
            ;;
        *)
            log_error "サポートされていないOS: $os"
            exit 1
            ;;
    esac

    log_debug "検出されたOS: $DETECTED_OS"
    log_debug "パッケージマネージャー: $PACKAGE_MANAGER"
}

# macOS用インストールガイド取得
# Usage: _get_install_guide_macos <tool_name>
_get_install_guide_macos() {
    local tool=$1
    case "$tool" in
        docker) echo "brew install --cask docker" ;;
        node|nodejs) echo "brew install node" ;;
        php) echo "brew install php" ;;
        make) echo "xcode-select --install" ;;
        *) echo "brew install $tool" ;;
    esac
}

# apt用インストールガイド取得
# Usage: _get_install_guide_apt <tool_name>
_get_install_guide_apt() {
    local tool=$1
    case "$tool" in
        docker) echo "curl -fsSL https://get.docker.com -o get-docker.sh && sudo sh get-docker.sh" ;;
        node|nodejs) echo "curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash - && sudo apt-get install -y nodejs" ;;
        php) echo "sudo apt-get update && sudo apt-get install -y php php-cli php-common" ;;
        make) echo "sudo apt-get install -y build-essential" ;;
        *) echo "sudo apt-get install -y $tool" ;;
    esac
}

# yum用インストールガイド取得
# Usage: _get_install_guide_yum <tool_name>
_get_install_guide_yum() {
    local tool=$1
    case "$tool" in
        docker) echo "curl -fsSL https://get.docker.com -o get-docker.sh && sudo sh get-docker.sh" ;;
        node|nodejs) echo "curl -fsSL https://rpm.nodesource.com/setup_lts.x | sudo bash - && sudo yum install -y nodejs" ;;
        php) echo "sudo yum install -y php php-cli php-common" ;;
        make) echo "sudo yum groupinstall -y 'Development Tools'" ;;
        *) echo "sudo yum install -y $tool" ;;
    esac
}

# インストールガイド取得
# Usage: get_install_guide <tool_name>
# Returns: Install command for the tool
get_install_guide() {
    local tool=$1

    case "$DETECTED_OS" in
        macos)
            _get_install_guide_macos "$tool"
            ;;
        linux|wsl2)
            case "$PACKAGE_MANAGER" in
                apt) _get_install_guide_apt "$tool" ;;
                yum) _get_install_guide_yum "$tool" ;;
            esac
            ;;
    esac
}

# ==============================================================================
# 進捗管理
# ==============================================================================

# 進捗マーカー読み込み
# Usage: load_progress
load_progress() {
    if [ -f "$PROGRESS_FILE" ]; then
        log_debug "進捗マーカーを読み込み中..."
        # JSON解析は簡易実装（jqなしで動作）
        export COMPLETED_STEPS=$(cat "$PROGRESS_FILE" | grep -o '"completed_steps":\[[^]]*\]' | sed 's/"completed_steps":\[//;s/\]//;s/"//g;s/,/ /g')
        log_debug "完了済みステップ: $COMPLETED_STEPS"
    else
        log_debug "進捗マーカーファイルが見つかりません"
        export COMPLETED_STEPS=""
    fi
}

# 進捗保存
# Usage: save_progress <step_name>
save_progress() {
    local step_name=$1

    # 既存の完了ステップを読み込み
    load_progress

    # ステップを追加（重複チェック）
    if ! echo "$COMPLETED_STEPS" | grep -qw "$step_name"; then
        if [ -z "$COMPLETED_STEPS" ]; then
            COMPLETED_STEPS="$step_name"
        else
            COMPLETED_STEPS="$COMPLETED_STEPS $step_name"
        fi
    fi

    # JSON形式で保存
    cat > "$PROGRESS_FILE" <<EOF
{
  "version": "1.0",
  "timestamp": "$(date -u '+%Y-%m-%dT%H:%M:%SZ')",
  "completed_steps": [$(echo "$COMPLETED_STEPS" | sed 's/ /", "/g;s/^/"/;s/$/"/')],
  "current_step": "$step_name"
}
EOF

    log_debug "進捗を保存しました: $step_name"
}

# ステップ完了チェック
# Usage: is_step_completed <step_name>
# Returns: 0 if completed, 1 if not
is_step_completed() {
    local step_name=$1

    if echo "$COMPLETED_STEPS" | grep -qw "$step_name"; then
        return 0
    else
        return 1
    fi
}

# マーカーファイル削除
# Usage: cleanup_progress
cleanup_progress() {
    if [ -f "$PROGRESS_FILE" ]; then
        rm -f "$PROGRESS_FILE"
        log_debug "進捗マーカーファイルを削除しました"
    fi
}

# ==============================================================================
# リトライロジック
# ==============================================================================

# 指数バックオフリトライ
# Usage: retry_with_exponential_backoff <command> [args...]
# Returns: 0 if success, 1 if failed after max attempts
retry_with_exponential_backoff() {
    local max_attempts=3
    local timeout=1
    local attempt=1

    while [ $attempt -le $max_attempts ]; do
        log_debug "試行 $attempt/$max_attempts..."

        if "$@"; then
            return 0
        fi

        if [ $attempt -lt $max_attempts ]; then
            log_warn "リトライ $attempt/$max_attempts (${timeout}秒後)"
            sleep $timeout
            timeout=$((timeout * 2))
        fi
        attempt=$((attempt + 1))
    done

    log_error "最大リトライ回数に達しました"
    return 1
}

# ==============================================================================
# パフォーマンス測定
# ==============================================================================

# ステップ実行時間測定
# Usage: measure_step_time <step_name> <command> [args...]
# Returns: Command exit code
measure_step_time() {
    local step_name=$1
    shift

    local start_time=$(date +%s)
    "$@"
    local exit_code=$?
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))

    if [ $exit_code -eq 0 ]; then
        log_info "$step_name 完了 (所要時間: ${duration}秒)"
    else
        log_error "$step_name 失敗 (所要時間: ${duration}秒)"
    fi

    return $exit_code
}

# ==============================================================================
# ヘルスチェック
# ==============================================================================

# サービスのヘルスチェック待機
# Usage: wait_for_service <service_name> <check_type> [max_attempts]
# check_type: "health" (Docker health check) or "http:<url>" (HTTP endpoint check)
# Returns: 0 if healthy, 1 if timeout
wait_for_service() {
    local service_name=$1
    local check_type=$2
    local max_attempts=${3:-30}  # デフォルト30回（30秒）
    local attempt=1

    log_info "  $service_name のヘルスチェック待機中..."

    while [ $attempt -le $max_attempts ]; do
        case "$check_type" in
            health)
                # Dockerのヘルスチェックステータスを確認
                local health_status=$(docker compose ps -q "$service_name" | xargs docker inspect --format='{{.State.Health.Status}}' 2>/dev/null || echo "none")
                if [ "$health_status" = "healthy" ]; then
                    log_info "  ✅ $service_name が正常起動しました"
                    return 0
                fi
                ;;
            http:*)
                # HTTPエンドポイントを確認
                local url="${check_type#http:}"
                if curl -fsS "$url" &>/dev/null; then
                    log_info "  ✅ $service_name が応答しています"
                    return 0
                fi
                ;;
        esac

        log_debug "  試行 $attempt/$max_attempts..."
        sleep 1
        attempt=$((attempt + 1))
    done

    log_error "  ❌ $service_name のヘルスチェックがタイムアウトしました"
    return 1
}
