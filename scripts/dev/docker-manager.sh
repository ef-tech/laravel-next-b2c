#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# Docker Compose Manager Script
# =============================================================================
# Docker Composeを使ったサービス管理を提供します
#
# Requirements:
# - 11.2: プロファイルフラグ生成機能
# - 11.3: Docker Compose起動機能
# - 11.4: Docker Compose停止機能
# - 11.5: Docker Composeエラーハンドリング
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
# Error Handling
# -----------------------------------------------------------------------------
handle_docker_error() {
    local exit_code=$1
    local command=$2

    case $exit_code in
        0)
            return 0
            ;;
        1)
            log_error "Docker Compose command failed: $command"
            log_error "General error occurred. Check Docker Compose configuration."
            ;;
        2)
            log_error "Docker Compose command failed: $command"
            log_error "Command line syntax error or invalid option."
            ;;
        125)
            log_error "Docker daemon error: $command"
            log_error "Docker daemon might not be running. Try 'docker info' to check."
            ;;
        126)
            log_error "Docker Compose command failed: $command"
            log_error "Command cannot be executed. Permission denied or file not executable."
            ;;
        127)
            log_error "Docker Compose not found: $command"
            log_error "Docker Compose is not installed or not in PATH."
            ;;
        130)
            log_warn "Docker Compose interrupted by user (Ctrl+C)"
            return $exit_code
            ;;
        137)
            log_error "Docker Compose killed (SIGKILL): $command"
            log_error "Out of memory or force killed by system."
            ;;
        *)
            log_error "Docker Compose command failed with exit code $exit_code: $command"
            log_error "Unknown error occurred."
            ;;
    esac

    return $exit_code
}

# -----------------------------------------------------------------------------
# Docker Compose Validation
# -----------------------------------------------------------------------------
check_docker_compose() {
    log_debug "Checking Docker Compose availability..."

    if ! command -v docker &>/dev/null; then
        log_error "Docker is not installed or not in PATH"
        log_error "Please install Docker Desktop or Docker Engine"
        return 1
    fi

    if ! docker compose version &>/dev/null; then
        log_error "Docker Compose is not available"
        log_error "Please install Docker Compose v2 or newer"
        return 1
    fi

    local compose_version
    compose_version=$(docker compose version --short 2>/dev/null || echo "unknown")
    log_debug "Docker Compose version: $compose_version"

    # Check Docker daemon
    if ! docker info &>/dev/null; then
        log_error "Docker daemon is not running"
        log_error "Please start Docker Desktop or Docker daemon"
        return 1
    fi

    log_debug "Docker Compose is available and Docker daemon is running"
    return 0
}

# -----------------------------------------------------------------------------
# Profile Flag Generation (Requirement 11.2)
# -----------------------------------------------------------------------------
get_docker_profiles() {
    local profiles_json="$1"

    if [[ -z "$profiles_json" ]]; then
        log_error "get_docker_profiles: profiles_json is required"
        return 1
    fi

    log_debug "Parsing Docker profiles from JSON: $profiles_json"

    # JSONから配列を抽出してスペース区切りに変換
    local profiles_array
    profiles_array=$(echo "$profiles_json" | jq -r '.[]' 2>/dev/null)

    if [[ -z "$profiles_array" ]]; then
        log_debug "No profiles found in JSON"
        echo ""
        return 0
    fi

    local profile_flags=""
    while IFS= read -r profile; do
        if [[ -n "$profile" ]]; then
            profile_flags+="--profile $profile "
            log_debug "Added profile flag: --profile $profile"
        fi
    done <<< "$profiles_array"

    # 末尾のスペースを削除
    profile_flags="${profile_flags% }"

    log_debug "Generated profile flags: $profile_flags"
    echo "$profile_flags"
}

# -----------------------------------------------------------------------------
# Docker Compose Start (Requirement 11.3)
# -----------------------------------------------------------------------------
start_docker_compose() {
    local profiles_json="$1"
    local detached="${2:-true}"

    log_info "Starting Docker Compose services..."

    # Docker Compose availability check
    if ! check_docker_compose; then
        return 1
    fi

    # Generate profile flags
    local profile_flags
    profile_flags=$(get_docker_profiles "$profiles_json")

    if [[ -z "$profile_flags" ]]; then
        log_warn "No profiles specified, starting all services"
    else
        log_info "Using profiles: $profile_flags"
    fi

    # Build docker compose command
    local compose_cmd="docker compose"
    if [[ -n "$profile_flags" ]]; then
        compose_cmd="$compose_cmd $profile_flags"
    fi

    # Add up command with options
    if [[ "$detached" == "true" ]]; then
        compose_cmd="$compose_cmd up -d"
    else
        compose_cmd="$compose_cmd up"
    fi

    log_debug "Executing: $compose_cmd"

    # Execute Docker Compose
    cd "$PROJECT_ROOT" || return 1

    if eval "$compose_cmd"; then
        log_success "Docker Compose services started successfully"

        # Show running containers
        log_info "Running containers:"
        docker compose ps

        return 0
    else
        local exit_code=$?
        handle_docker_error $exit_code "$compose_cmd"
        return $exit_code
    fi
}

# -----------------------------------------------------------------------------
# Docker Compose Stop (Requirement 11.4)
# -----------------------------------------------------------------------------
stop_docker_compose() {
    local remove_volumes="${1:-false}"

    log_info "Stopping Docker Compose services..."

    # Docker Compose availability check
    if ! check_docker_compose; then
        return 1
    fi

    # Build docker compose command
    local compose_cmd="docker compose down"

    if [[ "$remove_volumes" == "true" ]]; then
        compose_cmd="$compose_cmd -v"
        log_warn "Volumes will be removed (data will be lost)"
    fi

    log_debug "Executing: $compose_cmd"

    # Execute Docker Compose
    cd "$PROJECT_ROOT" || return 1

    if eval "$compose_cmd"; then
        log_success "Docker Compose services stopped successfully"
        return 0
    else
        local exit_code=$?
        handle_docker_error $exit_code "$compose_cmd"
        return $exit_code
    fi
}

# -----------------------------------------------------------------------------
# Docker Compose Restart
# -----------------------------------------------------------------------------
restart_docker_compose() {
    local profiles_json="$1"
    local remove_volumes="${2:-false}"

    log_info "Restarting Docker Compose services..."

    if stop_docker_compose "$remove_volumes"; then
        sleep 2  # Wait for clean shutdown
        start_docker_compose "$profiles_json" "true"
    else
        log_error "Failed to stop services, cannot restart"
        return 1
    fi
}

# -----------------------------------------------------------------------------
# Docker Compose Status
# -----------------------------------------------------------------------------
show_docker_status() {
    log_info "Docker Compose service status:"

    cd "$PROJECT_ROOT" || return 1

    if docker compose ps; then
        return 0
    else
        local exit_code=$?
        handle_docker_error $exit_code "docker compose ps"
        return $exit_code
    fi
}

# -----------------------------------------------------------------------------
# Docker Compose Logs
# -----------------------------------------------------------------------------
show_docker_logs() {
    local service="${1:-}"
    local follow="${2:-false}"

    cd "$PROJECT_ROOT" || return 1

    local logs_cmd="docker compose logs"

    if [[ "$follow" == "true" ]]; then
        logs_cmd="$logs_cmd -f"
    fi

    if [[ -n "$service" ]]; then
        logs_cmd="$logs_cmd $service"
    fi

    log_debug "Executing: $logs_cmd"

    if eval "$logs_cmd"; then
        return 0
    else
        local exit_code=$?
        handle_docker_error $exit_code "$logs_cmd"
        return $exit_code
    fi
}

# -----------------------------------------------------------------------------
# Main Function (for testing)
# -----------------------------------------------------------------------------
main() {
    local action="${1:-status}"

    case "$action" in
        start)
            local profiles_json='["infra","api","frontend"]'
            start_docker_compose "$profiles_json" "true"
            ;;
        stop)
            stop_docker_compose "false"
            ;;
        restart)
            local profiles_json='["infra","api","frontend"]'
            restart_docker_compose "$profiles_json" "false"
            ;;
        status)
            show_docker_status
            ;;
        logs)
            show_docker_logs "${2:-}" "${3:-false}"
            ;;
        *)
            echo "Usage: $0 {start|stop|restart|status|logs} [service] [follow]"
            exit 1
            ;;
    esac
}

# Execute main if script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
