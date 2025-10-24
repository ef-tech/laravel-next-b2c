#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# Native Process Manager Script
# =============================================================================
# ネイティブプロセスを使ったサービス管理を提供します
#
# Requirements:
# - 12.2: concurrentlyコマンド生成機能
# - 12.3: ネイティブプロセス起動機能
# - 12.4: プロセス停止機能
# - 12.5: プロセスエラーハンドリング
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

# Concurrently color palette (6 colors)
readonly CONCURRENTLY_COLORS=("blue" "magenta" "cyan" "green" "yellow" "red")

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
handle_process_error() {
    local exit_code=$1
    local command=$2

    case $exit_code in
        0)
            return 0
            ;;
        1)
            log_error "Process failed: $command"
            log_error "General error occurred."
            ;;
        126)
            log_error "Process failed: $command"
            log_error "Command cannot be executed. Permission denied or file not executable."
            ;;
        127)
            log_error "Command not found: $command"
            log_error "Required command is not installed or not in PATH."
            ;;
        130)
            log_warn "Process interrupted by user (Ctrl+C)"
            return $exit_code
            ;;
        137)
            log_error "Process killed (SIGKILL): $command"
            log_error "Out of memory or force killed by system."
            ;;
        143)
            log_warn "Process terminated (SIGTERM): $command"
            return $exit_code
            ;;
        *)
            log_error "Process failed with exit code $exit_code: $command"
            log_error "Unknown error occurred."
            ;;
    esac

    return $exit_code
}

# -----------------------------------------------------------------------------
# Dependency Check
# -----------------------------------------------------------------------------
check_concurrently() {
    log_debug "Checking concurrently availability..."

    if ! command -v concurrently &>/dev/null; then
        log_error "concurrently is not installed"
        log_error "Please run: cd scripts/dev && npm install"
        return 1
    fi

    local concurrently_version
    concurrently_version=$(concurrently --version 2>/dev/null || echo "unknown")
    log_debug "concurrently version: $concurrently_version"

    return 0
}

# -----------------------------------------------------------------------------
# Build Concurrently Command (Requirement 12.2)
# -----------------------------------------------------------------------------
build_concurrently_command() {
    local services_json="$1"

    if [[ -z "$services_json" ]]; then
        log_error "build_concurrently_command: services_json is required"
        return 1
    fi

    log_debug "Building concurrently command from services JSON"

    # Parse service names and commands
    local service_count
    service_count=$(echo "$services_json" | jq 'length' 2>/dev/null)

    if [[ -z "$service_count" ]] || [[ "$service_count" == "0" ]]; then
        log_error "No services found in JSON"
        return 1
    fi

    log_debug "Found $service_count services"

    # Build command array
    local commands=()
    local names=()
    local colors_arg=""
    local index=0

    while IFS= read -r service; do
        local name command color

        name=$(echo "$service" | jq -r '.name' 2>/dev/null)
        command=$(echo "$service" | jq -r '.command' 2>/dev/null)

        if [[ -z "$name" ]] || [[ "$name" == "null" ]] || [[ -z "$command" ]] || [[ "$command" == "null" ]]; then
            log_warn "Skipping invalid service at index $index"
            ((index++))
            continue
        fi

        # Escape double quotes in command
        command="${command//\"/\\\"}"

        commands+=("\"$command\"")
        names+=("\"$name\"")

        # Assign color (cycle through palette)
        color_index=$((index % ${#CONCURRENTLY_COLORS[@]}))
        color="${CONCURRENTLY_COLORS[$color_index]}"

        if [[ -n "$colors_arg" ]]; then
            colors_arg="$colors_arg,$color"
        else
            colors_arg="$color"
        fi

        log_debug "Service $index: name=$name, color=$color"
        ((index++))
    done < <(echo "$services_json" | jq -c '.[]' 2>/dev/null)

    if [[ ${#commands[@]} -eq 0 ]]; then
        log_error "No valid services to run"
        return 1
    fi

    # Build concurrently command
    local concurrently_cmd="concurrently"

    # Add options
    concurrently_cmd="$concurrently_cmd --kill-others"
    concurrently_cmd="$concurrently_cmd --prefix-colors \"$colors_arg\""
    concurrently_cmd="$concurrently_cmd --names $(IFS=,; echo "${names[*]}")"
    concurrently_cmd="$concurrently_cmd --prefix \"[{name}]\""

    # Add commands
    concurrently_cmd="$concurrently_cmd ${commands[*]}"

    log_debug "Built concurrently command: $concurrently_cmd"

    echo "$concurrently_cmd"
}

# -----------------------------------------------------------------------------
# Start Native Processes (Requirement 12.3)
# -----------------------------------------------------------------------------
start_native_processes() {
    local services_json="$1"

    log_info "Starting native processes..."

    # Check concurrently availability
    if ! check_concurrently; then
        return 1
    fi

    # Build concurrently command
    local concurrently_cmd
    concurrently_cmd=$(build_concurrently_command "$services_json")

    if [[ $? -ne 0 ]] || [[ -z "$concurrently_cmd" ]]; then
        log_error "Failed to build concurrently command"
        return 1
    fi

    log_info "Executing concurrently with ${#CONCURRENTLY_COLORS[@]} color rotation"
    log_debug "Command: $concurrently_cmd"

    # Execute concurrently
    cd "$SCRIPT_DIR" || return 1

    if eval "$concurrently_cmd"; then
        log_success "All processes completed successfully"
        return 0
    else
        local exit_code=$?
        handle_process_error $exit_code "$concurrently_cmd"
        return $exit_code
    fi
}

# -----------------------------------------------------------------------------
# Stop Native Processes (Requirement 12.4)
# -----------------------------------------------------------------------------
stop_native_processes() {
    local pid_file="${1:-/tmp/dev-server-processes.pid}"

    log_info "Stopping native processes..."

    if [[ ! -f "$pid_file" ]]; then
        log_warn "PID file not found: $pid_file"
        log_info "Attempting to find and kill processes by name..."

        # Kill known process patterns
        pkill -f "npm run dev" 2>/dev/null || true
        pkill -f "php artisan serve" 2>/dev/null || true
        pkill -f "concurrently" 2>/dev/null || true

        log_success "Sent termination signals to matching processes"
        return 0
    fi

    # Read PIDs from file
    local pids
    pids=$(cat "$pid_file")

    if [[ -z "$pids" ]]; then
        log_warn "No PIDs found in file"
        rm -f "$pid_file"
        return 0
    fi

    log_debug "PIDs to stop: $pids"

    # Send SIGTERM to each PID
    local stopped_count=0
    for pid in $pids; do
        if ps -p "$pid" >/dev/null 2>&1; then
            log_info "Stopping process $pid..."
            kill -TERM "$pid" 2>/dev/null || true
            ((stopped_count++))
        else
            log_debug "Process $pid already stopped"
        fi
    done

    # Wait for processes to stop
    sleep 2

    # Force kill if still running
    for pid in $pids; do
        if ps -p "$pid" >/dev/null 2>&1; then
            log_warn "Force killing process $pid..."
            kill -KILL "$pid" 2>/dev/null || true
        fi
    done

    # Remove PID file
    rm -f "$pid_file"

    log_success "Stopped $stopped_count processes"
    return 0
}

# -----------------------------------------------------------------------------
# Get Running Processes
# -----------------------------------------------------------------------------
show_process_status() {
    log_info "Checking running native processes..."

    local found_processes=0

    # Check for npm dev servers
    if pgrep -f "npm run dev" >/dev/null 2>&1; then
        log_info "Found npm dev servers:"
        pgrep -af "npm run dev" || true
        ((found_processes++))
    fi

    # Check for PHP artisan serve
    if pgrep -f "php artisan serve" >/dev/null 2>&1; then
        log_info "Found PHP artisan servers:"
        pgrep -af "php artisan serve" || true
        ((found_processes++))
    fi

    # Check for concurrently
    if pgrep -f "concurrently" >/dev/null 2>&1; then
        log_info "Found concurrently processes:"
        pgrep -af "concurrently" || true
        ((found_processes++))
    fi

    if [[ $found_processes -eq 0 ]]; then
        log_info "No native processes found"
    fi

    return 0
}

# -----------------------------------------------------------------------------
# Main Function (for testing)
# -----------------------------------------------------------------------------
main() {
    local action="${1:-status}"

    case "$action" in
        start)
            # Example services JSON
            local services_json='[
                {
                    "name": "Laravel API",
                    "command": "cd ../../backend/laravel-api && php artisan serve --host=0.0.0.0 --port=13000"
                },
                {
                    "name": "Admin App",
                    "command": "cd ../../frontend/admin-app && npm run dev -- -p 13002"
                },
                {
                    "name": "User App",
                    "command": "cd ../../frontend/user-app && npm run dev -- -p 13001"
                }
            ]'
            start_native_processes "$services_json"
            ;;
        stop)
            stop_native_processes "${2:-/tmp/dev-server-processes.pid}"
            ;;
        status)
            show_process_status
            ;;
        *)
            echo "Usage: $0 {start|stop|status} [pid_file]"
            exit 1
            ;;
    esac
}

# Execute main if script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
