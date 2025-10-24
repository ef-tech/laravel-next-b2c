#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# Dev Server Main Entry Point
# =============================================================================
# 開発サーバー起動のメインエントリーポイント
#
# Requirements:
# - 10.2: コマンドライン引数解析
# - 10.3: ヘルプメッセージ表示
# - 6.1-6.7: 初回セットアップ統合機能
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# -----------------------------------------------------------------------------
# Color Definitions
# -----------------------------------------------------------------------------
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly MAGENTA='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly NC='\033[0m' # No Color

# -----------------------------------------------------------------------------
# Logging Functions
# -----------------------------------------------------------------------------
log_info() {
    echo -e "${BLUE}[INFO]${NC} $*" >&2
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $*" >&2
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $*" >&2
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $*" >&2
}

log_debug() {
    if [[ "${DEBUG:-}" == "1" ]]; then
        echo -e "${MAGENTA}[DEBUG]${NC} $*" >&2
    fi
}

# -----------------------------------------------------------------------------
# Cleanup Function (Graceful Shutdown)
# -----------------------------------------------------------------------------
cleanup() {
    log_info "Shutting down services..."

    # Stop services based on MODE
    case "${MODE:-docker}" in
        docker)
            # Docker mode: stop Docker Compose
            source "$SCRIPT_DIR/docker-manager.sh"
            stop_docker_compose || true
            ;;
        native)
            # Native mode: stop native processes
            source "$SCRIPT_DIR/process-manager.sh"
            stop_native_processes || true
            ;;
        hybrid)
            # Hybrid mode: stop both native and Docker
            source "$SCRIPT_DIR/process-manager.sh"
            stop_native_processes || true
            source "$SCRIPT_DIR/docker-manager.sh"
            stop_docker_compose || true
            ;;
        *)
            log_warn "Unknown mode: ${MODE:-docker}"
            ;;
    esac

    log_success "Shutdown complete"
    exit 0
}

# -----------------------------------------------------------------------------
# Help Message (Requirement 10.3)
# -----------------------------------------------------------------------------
show_help() {
    cat << EOF
${GREEN}Dev Server Startup Script${NC}

${CYAN}USAGE:${NC}
  $0 [OPTIONS]

${CYAN}OPTIONS:${NC}
  ${YELLOW}Mode Selection:${NC}
    --mode <docker|native|hybrid>
        起動モード (デフォルト: docker)
        - docker:  Dockerコンテナでサービス起動
        - native:  ネイティブプロセスでサービス起動
        - hybrid:  インフラはDocker、アプリはネイティブ

  ${YELLOW}Profile/Service Selection:${NC}
    --profile <name>
        プロファイル名 (full, api-only, frontend-only, infra-only, minimal)
        --servicesと排他

    --services <service1,service2,...>
        起動するサービス名のカンマ区切りリスト
        --profileと排他

  ${YELLOW}Setup Control:${NC}
    --setup
        初回セットアップを強制実行

    --skip-setup
        初回セットアップをスキップ

  ${YELLOW}Other Options:${NC}
    --detached, -d
        デタッチモード（Dockerバックグラウンド起動）

    --debug
        デバッグログ出力有効化

    --help, -h
        このヘルプメッセージを表示

${CYAN}EXAMPLES:${NC}
  # デフォルト（Dockerでフルスタック起動）
  $0

  # APIのみDocker起動
  $0 --profile api-only

  # フロントエンドのみネイティブ起動
  $0 --mode native --profile frontend-only

  # ハイブリッドモード（インフラDocker、アプリネイティブ）
  $0 --mode hybrid

  # 特定サービスのみ起動
  $0 --services laravel-api,admin-app

  # セットアップから実行
  $0 --setup --mode docker

  # デバッグモード
  DEBUG=1 $0 --mode native

${CYAN}ENVIRONMENT VARIABLES:${NC}
  DEBUG=1           デバッグログ出力

EOF
}

# -----------------------------------------------------------------------------
# Command Line Argument Parsing (Requirement 10.2)
# -----------------------------------------------------------------------------
MODE="docker"
PROFILE=""
SERVICES=""
SETUP_MODE="auto"  # auto | force | skip
DETACHED="false"
SHOW_HELP="false"

# Check if required argument is provided
check_required_arg() {
    local option_name="$1"
    local arg_value="${2:-}"

    if [[ -z "$arg_value" ]]; then
        log_error "$option_name requires an argument"
        exit 1
    fi
}

parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --mode)
                check_required_arg "--mode" "${2:-}"
                MODE="$2"
                shift 2
                ;;
            --profile)
                check_required_arg "--profile" "${2:-}"
                PROFILE="$2"
                shift 2
                ;;
            --services)
                check_required_arg "--services" "${2:-}"
                SERVICES="$2"
                shift 2
                ;;
            --setup)
                SETUP_MODE="force"
                shift
                ;;
            --skip-setup)
                SETUP_MODE="skip"
                shift
                ;;
            --detached|-d)
                DETACHED="true"
                shift
                ;;
            --debug)
                export DEBUG=1
                shift
                ;;
            --help|-h)
                SHOW_HELP="true"
                shift
                ;;
            *)
                log_error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done

    # Validate mode
    if [[ ! "$MODE" =~ ^(docker|native|hybrid)$ ]]; then
        log_error "Invalid mode: $MODE (must be docker, native, or hybrid)"
        exit 1
    fi

    # Validate profile and services exclusivity
    if [[ -n "$PROFILE" ]] && [[ -n "$SERVICES" ]]; then
        log_error "--profile and --services are mutually exclusive"
        exit 1
    fi

    log_debug "Parsed arguments: MODE=$MODE, PROFILE=$PROFILE, SERVICES=$SERVICES, SETUP_MODE=$SETUP_MODE, DETACHED=$DETACHED"
}

# -----------------------------------------------------------------------------
# Setup Check (Requirement 6.1)
# -----------------------------------------------------------------------------
check_setup_completed() {
    log_debug "Checking if setup is completed..."

    local issues=()

    # Check backend setup
    if [[ ! -f "$PROJECT_ROOT/backend/laravel-api/.env" ]]; then
        issues+=("Laravel .env file not found")
    fi

    if [[ ! -d "$PROJECT_ROOT/backend/laravel-api/vendor" ]]; then
        issues+=("Laravel vendor directory not found (composer install not run)")
    fi

    # Check frontend setup
    if [[ ! -d "$PROJECT_ROOT/frontend/admin-app/node_modules" ]]; then
        issues+=("Admin app node_modules not found (npm install not run)")
    fi

    if [[ ! -d "$PROJECT_ROOT/frontend/user-app/node_modules" ]]; then
        issues+=("User app node_modules not found (npm install not run)")
    fi

    # Check scripts/dev setup
    if [[ ! -d "$SCRIPT_DIR/node_modules" ]]; then
        issues+=("Scripts dev node_modules not found (npm install not run)")
    fi

    if [[ ${#issues[@]} -gt 0 ]]; then
        log_warn "Setup issues detected:"
        for issue in "${issues[@]}"; do
            log_warn "  - $issue"
        done
        return 1
    fi

    log_debug "Setup is completed"
    return 0
}

# -----------------------------------------------------------------------------
# Run Setup (Requirement 6.2-6.7)
# -----------------------------------------------------------------------------
run_setup() {
    log_info "Running initial setup..."
    log_info "This may take several minutes..."

    cd "$PROJECT_ROOT" || exit 1

    # Check if Makefile exists
    if [[ ! -f "$PROJECT_ROOT/Makefile" ]]; then
        log_error "Makefile not found in project root"
        log_error "Cannot run 'make setup'"
        return 1
    fi

    # Run make setup
    log_info "Executing: make setup"

    if make setup; then
        log_success "Setup completed successfully"
        return 0
    else
        local exit_code=$?
        log_error "Setup failed with exit code $exit_code"
        log_error "Please fix the errors and try again"
        return $exit_code
    fi
}

# -----------------------------------------------------------------------------
# Handle Setup (Requirement 6.1-6.7)
# -----------------------------------------------------------------------------
handle_setup() {
    case "$SETUP_MODE" in
        force)
            log_info "Forced setup mode"
            run_setup
            return $?
            ;;
        skip)
            log_info "Skipping setup (--skip-setup specified)"
            return 0
            ;;
        auto)
            if check_setup_completed; then
                log_info "Setup already completed"
                return 0
            else
                log_warn "Setup not completed"
                log_info "Running automatic setup..."
                run_setup
                return $?
            fi
            ;;
        *)
            log_error "Invalid SETUP_MODE: $SETUP_MODE"
            return 1
            ;;
    esac
}

# -----------------------------------------------------------------------------
# Load Configuration (TypeScript Integration)
# -----------------------------------------------------------------------------
load_config() {
    log_info "Loading configuration..."

    cd "$SCRIPT_DIR" || exit 1

    # Build TypeScript arguments
    local ts_args="--mode=$MODE"

    if [[ -n "$PROFILE" ]]; then
        ts_args="$ts_args --profile=$PROFILE"
    fi

    if [[ -n "$SERVICES" ]]; then
        ts_args="$ts_args --services=$SERVICES"
    fi

    log_debug "TypeScript arguments: $ts_args"

    # Execute dev-server.ts to generate configuration
    local config_json
    config_json=$(npx tsx dev-server.ts $ts_args 2>&1)

    if [[ $? -ne 0 ]]; then
        log_error "Failed to load configuration"
        log_error "$config_json"
        return 1
    fi

    log_debug "Configuration loaded successfully"

    # Export configuration as JSON string
    echo "$config_json"
}

# -----------------------------------------------------------------------------
# Check Dependencies (TypeScript Integration)
# -----------------------------------------------------------------------------
check_dependencies() {
    log_info "Checking dependencies..."

    cd "$SCRIPT_DIR" || exit 1

    # Execute health-check.ts
    if npx tsx health-check.ts check-dependencies; then
        log_success "All dependencies are available"
        return 0
    else
        log_error "Dependency check failed"
        return 1
    fi
}

# -----------------------------------------------------------------------------
# Check Ports (TypeScript Integration)
# -----------------------------------------------------------------------------
check_ports() {
    local ports_json="$1"

    log_info "Checking port availability..."

    cd "$SCRIPT_DIR" || exit 1

    # Execute health-check.ts with ports
    if echo "$ports_json" | npx tsx health-check.ts check-ports; then
        log_success "All ports are available"
        return 0
    else
        log_warn "Some ports are in use"
        log_warn "Services using conflicting ports may fail to start"
        return 0  # Don't fail, just warn
    fi
}

# -----------------------------------------------------------------------------
# Start Services
# -----------------------------------------------------------------------------
start_services() {
    local config_json="$1"

    log_info "Starting services in $MODE mode..."

    # Parse mode-specific configuration
    local docker_profiles
    docker_profiles=$(echo "$config_json" | jq -r '.dockerProfiles // []')

    local native_services
    native_services=$(echo "$config_json" | jq -r '.nativeServices // []')

    log_debug "Docker profiles: $docker_profiles"
    log_debug "Native services: $native_services"

    case "$MODE" in
        docker)
            # Start all services with Docker
            source "$SCRIPT_DIR/docker-manager.sh"
            start_docker_compose "$docker_profiles" "$DETACHED"
            ;;
        native)
            # Start all services natively
            source "$SCRIPT_DIR/process-manager.sh"
            start_native_processes "$native_services"
            ;;
        hybrid)
            # Start infra with Docker, apps natively
            source "$SCRIPT_DIR/docker-manager.sh"
            source "$SCRIPT_DIR/process-manager.sh"

            log_info "Starting infrastructure with Docker..."
            start_docker_compose "$docker_profiles" "true"

            log_info "Waiting for infrastructure to be ready..."
            sleep 5

            log_info "Starting applications natively..."
            start_native_processes "$native_services"
            ;;
        *)
            log_error "Invalid mode: $MODE"
            return 1
            ;;
    esac
}

# -----------------------------------------------------------------------------
# Main Function
# -----------------------------------------------------------------------------
main() {
    # Parse command line arguments
    parse_arguments "$@"

    # Setup signal handlers for graceful shutdown
    trap cleanup INT TERM

    # Show help if requested
    if [[ "$SHOW_HELP" == "true" ]]; then
        show_help
        exit 0
    fi

    log_info "${GREEN}========================================${NC}"
    log_info "${GREEN}Dev Server Startup${NC}"
    log_info "${GREEN}========================================${NC}"

    # Handle setup
    if ! handle_setup; then
        log_error "Setup failed"
        exit 1
    fi

    # Check dependencies
    if ! check_dependencies; then
        log_error "Dependency check failed"
        exit 1
    fi

    # Load configuration
    local config_json
    config_json=$(load_config)

    if [[ $? -ne 0 ]]; then
        log_error "Configuration loading failed"
        exit 1
    fi

    # Check ports
    local ports_json
    ports_json=$(echo "$config_json" | jq -r '.ports // []')
    check_ports "$ports_json"

    # Start services
    if ! start_services "$config_json"; then
        log_error "Failed to start services"
        exit 1
    fi

    log_success "${GREEN}========================================${NC}"
    log_success "${GREEN}Dev Server Started Successfully${NC}"
    log_success "${GREEN}========================================${NC}"
}

# Execute main
main "$@"
