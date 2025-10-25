#!/usr/bin/env bash

#==============================================================================
# テスト環境診断スクリプト
#
# Requirement 11: 診断スクリプト
# - ポート使用状況確認（13000、13001、13002、13432、13379）
# - 必須環境変数の設定状態確認
# - Dockerコンテナの起動状態確認
# - データベース接続状態確認
# - ディスク空き容量確認
# - メモリ使用状況確認
# - 診断結果のコンソール出力
#==============================================================================

set -euo pipefail

#==============================================================================
# Script Directory & Library Loading
#==============================================================================
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

# Load shared libraries
# shellcheck source=scripts/lib/colors.sh
if [[ -z "${RED:-}" ]] && [[ -f "${SCRIPT_DIR}/../lib/colors.sh" ]]; then
    source "${SCRIPT_DIR}/../lib/colors.sh"
elif [[ ! -f "${SCRIPT_DIR}/../lib/colors.sh" ]]; then
    echo "ERROR: colors.sh not found at ${SCRIPT_DIR}/../lib/colors.sh" >&2
    exit 1
fi

# shellcheck source=scripts/lib/logging.sh
if [[ $(type -t log_info) != "function" ]] && [[ -f "${SCRIPT_DIR}/../lib/logging.sh" ]]; then
    source "${SCRIPT_DIR}/../lib/logging.sh"
elif [[ ! -f "${SCRIPT_DIR}/../lib/logging.sh" ]]; then
    echo "ERROR: logging.sh not found at ${SCRIPT_DIR}/../lib/logging.sh" >&2
    exit 1
fi

#==============================================================================
# Global Variables
#==============================================================================
REQUIRED_PORTS=(13000 13001 13002 13432 13379)
REQUIRED_ENV_VARS=(
    "DB_DATABASE"
    "DB_USERNAME"
    "DB_PASSWORD"
)

# Diagnostic status tracking
DIAGNOSTICS_PASSED=0
DIAGNOSTICS_FAILED=0

#==============================================================================
# Diagnostic Functions
#==============================================================================

# Requirement 11.2: ポート使用状況確認
check_port_usage() {
    log_info "Checking port usage for test services..."

    local all_ports_free=true

    for port in "${REQUIRED_PORTS[@]}"; do
        if lsof -i :"${port}" -sTCP:LISTEN > /dev/null 2>&1; then
            log_warn "Port ${port} is in use:"
            lsof -i :"${port}" -sTCP:LISTEN | tail -n +2 | awk '{print "  - PID " $2 " (" $1 ")"}'
            all_ports_free=false
            DIAGNOSTICS_FAILED=$((DIAGNOSTICS_FAILED + 1))
        else
            log_debug "Port ${port} is free"
        fi
    done

    if [[ "${all_ports_free}" == "true" ]]; then
        log_success "All required ports are available"
        DIAGNOSTICS_PASSED=$((DIAGNOSTICS_PASSED + 1))
        return 0
    else
        log_error "Some ports are in use. Run 'make dev-stop' to stop all services."
        return 1
    fi
}

# Requirement 11.3: 必須環境変数の設定状態確認
check_environment_variables() {
    log_info "Checking required environment variables..."

    local all_vars_set=true

    # Load .env file if it exists
    if [[ -f "${PROJECT_ROOT}/backend/laravel-api/.env" ]]; then
        # Export variables from .env (simplified approach)
        set -a
        # shellcheck source=/dev/null
        source "${PROJECT_ROOT}/backend/laravel-api/.env"
        set +a
    else
        log_warn ".env file not found at ${PROJECT_ROOT}/backend/laravel-api/.env"
    fi

    for var_name in "${REQUIRED_ENV_VARS[@]}"; do
        if [[ -z "${!var_name:-}" ]]; then
            log_error "Environment variable ${var_name} is not set"
            all_vars_set=false
        else
            log_debug "Environment variable ${var_name} is set"
        fi
    done

    if [[ "${all_vars_set}" == "true" ]]; then
        log_success "All required environment variables are set"
        DIAGNOSTICS_PASSED=$((DIAGNOSTICS_PASSED + 1))
        return 0
    else
        log_error "Some environment variables are missing. Check .env file."
        DIAGNOSTICS_FAILED=$((DIAGNOSTICS_FAILED + 1))
        return 1
    fi
}

# Requirement 11.4: Dockerコンテナの起動状態確認
check_docker_containers() {
    log_info "Checking Docker container status..."

    if ! command -v docker &> /dev/null; then
        log_warn "Docker command not found. Skipping container check."
        return 0
    fi

    local running_containers
    running_containers=$(docker ps --format "{{.Names}}" 2>/dev/null | wc -l || echo "0")

    if [[ "${running_containers}" -gt 0 ]]; then
        log_success "Docker containers are running:"
        docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || true
        DIAGNOSTICS_PASSED=$((DIAGNOSTICS_PASSED + 1))
        return 0
    else
        log_warn "No Docker containers are running"
        log_info "Run 'make dev' to start development services"
        DIAGNOSTICS_FAILED=$((DIAGNOSTICS_FAILED + 1))
        return 1
    fi
}

# Requirement 11.5: データベース接続状態確認
check_database_connection() {
    log_info "Checking database connection..."

    if ! command -v docker &> /dev/null; then
        log_warn "Docker command not found. Skipping database check."
        return 0
    fi

    # Check if PostgreSQL container is running
    local postgres_container
    postgres_container=$(docker ps --filter "name=postgres" --format "{{.Names}}" 2>/dev/null | head -n 1)

    if [[ -z "${postgres_container}" ]]; then
        log_warn "PostgreSQL container is not running"
        DIAGNOSTICS_FAILED=$((DIAGNOSTICS_FAILED + 1))
        return 1
    fi

    # Try to connect to PostgreSQL
    if docker exec "${postgres_container}" pg_isready -U "${DB_USERNAME:-postgres}" > /dev/null 2>&1; then
        log_success "PostgreSQL is accepting connections"
        DIAGNOSTICS_PASSED=$((DIAGNOSTICS_PASSED + 1))
        return 0
    else
        log_error "PostgreSQL is not accepting connections"
        DIAGNOSTICS_FAILED=$((DIAGNOSTICS_FAILED + 1))
        return 1
    fi
}

# Requirement 11.6: ディスク空き容量確認
check_disk_space() {
    log_info "Checking disk space..."

    local available_space
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        available_space=$(df -h . | tail -n 1 | awk '{print $4}')
    else
        # Linux
        available_space=$(df -h . | tail -n 1 | awk '{print $4}')
    fi

    log_success "Available disk space: ${available_space}"
    DIAGNOSTICS_PASSED=$((DIAGNOSTICS_PASSED + 1))
    return 0
}

# Requirement 11.7: メモリ使用状況確認
check_memory_usage() {
    log_info "Checking memory usage..."

    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        local total_mem
        local free_mem
        total_mem=$(sysctl -n hw.memsize | awk '{print $1 / 1024 / 1024 / 1024 " GB"}')
        free_mem=$(vm_stat | grep "Pages free" | awk '{print $3 * 4096 / 1024 / 1024 / 1024 " GB"}' | sed 's/\..*//')

        log_success "Total memory: ${total_mem}, Free: ~${free_mem} GB"
    else
        # Linux
        local mem_info
        mem_info=$(free -h | grep "Mem:" | awk '{print "Total: " $2 ", Free: " $4}')

        log_success "Memory: ${mem_info}"
    fi

    DIAGNOSTICS_PASSED=$((DIAGNOSTICS_PASSED + 1))
    return 0
}

# Requirement 11.8: 診断結果統合出力
print_diagnostic_summary() {
    echo ""
    log_info "========================================="
    log_info "   Diagnostic Summary"
    log_info "========================================="
    log_success "Passed: ${DIAGNOSTICS_PASSED} checks"

    if [[ "${DIAGNOSTICS_FAILED}" -gt 0 ]]; then
        log_error "Failed: ${DIAGNOSTICS_FAILED} checks"
        echo ""
        log_warn "Some diagnostics failed. Please review the output above."
        log_info "Run 'make setup' to initialize the environment"
        log_info "Run 'make dev' to start development services"
        return 1
    else
        echo ""
        log_success "All diagnostics passed! Environment is ready for testing."
        return 0
    fi
}

#==============================================================================
# Main Execution
#==============================================================================
main() {
    log_info "Starting test environment diagnostics..."
    echo ""

    # Requirement 11.1: 診断スクリプト起動
    # Run all diagnostic checks (continue even if some fail)
    set +e

    check_port_usage
    check_environment_variables
    check_docker_containers
    check_database_connection
    check_disk_space
    check_memory_usage

    set -e

    # Requirement 11.8: 診断結果統合出力
    print_diagnostic_summary

    # Return appropriate exit code
    if [[ "${DIAGNOSTICS_FAILED}" -gt 0 ]]; then
        exit 1
    else
        exit 0
    fi
}

# Execute main function
main "$@"
