#!/usr/bin/env bash

# E2E test execution script
# Supports service health checks and Playwright parallel execution

set -euo pipefail

# Source logging functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/lib/logging.sh
source "${PROJECT_ROOT}/scripts/lib/logging.sh"

# Default values
SHARD="${1:-}"

# Paths
E2E_DIR="${PROJECT_ROOT}/e2e"
LOG_FILE="${PROJECT_ROOT}/test-results/logs/e2e.log"
JUNIT_OUTPUT="${PROJECT_ROOT}/test-results/junit/e2e-test-results.xml"

# Service health endpoints (from e2e/.env)
LARAVEL_API_URL="${E2E_API_URL:-http://localhost:13000}"
USER_APP_URL="${E2E_USER_URL:-http://localhost:13001}"
ADMIN_APP_URL="${E2E_ADMIN_URL:-http://localhost:13002}"

LARAVEL_API_HEALTH="${LARAVEL_API_URL}/api/health"
USER_APP_HEALTH="${USER_APP_URL}/api/health"
ADMIN_APP_HEALTH="${ADMIN_APP_URL}/api/health"

# Health check configuration
MAX_RETRIES=120
RETRY_INTERVAL=1

# Check service health endpoint
check_service_health() {
    local service_name="$1"
    local health_url="$2"
    local retry_count=0

    log_info "Checking ${service_name} health at ${health_url}..."

    while [[ ${retry_count} -lt ${MAX_RETRIES} ]]; do
        # Attempt to curl the health endpoint
        if curl -sf "${health_url}" > /dev/null 2>&1; then
            log_success "${service_name} is healthy (${health_url})"
            return 0
        fi

        retry_count=$((retry_count + 1))

        if [[ $((retry_count % 10)) -eq 0 ]]; then
            log_debug "${service_name} not ready yet (attempt ${retry_count}/${MAX_RETRIES})..."
        fi

        sleep ${RETRY_INTERVAL}
    done

    log_error "${service_name} health check timed out after ${MAX_RETRIES} seconds"
    log_error "Health endpoint: ${health_url}"
    return 1
}

# Check all services
check_all_services() {
    log_info "Starting health checks for all services..."

    # Check Laravel API
    check_service_health "Laravel API" "${LARAVEL_API_HEALTH}" || {
        log_error "Laravel API health check failed"
        return 1
    }

    # Check User App
    check_service_health "User App" "${USER_APP_HEALTH}" || {
        log_error "User App health check failed"
        return 1
    }

    # Check Admin App
    check_service_health "Admin App" "${ADMIN_APP_HEALTH}" || {
        log_error "Admin App health check failed"
        return 1
    }

    log_success "All services are healthy and ready for E2E tests"
    return 0
}

# Run E2E tests
run_e2e_tests() {
    log_info "Running E2E tests (Shard: ${SHARD:-all})"

    # Check all services first
    check_all_services || {
        log_error "Service health checks failed - aborting E2E tests"
        return 1
    }

    # Change to e2e directory
    cd "${E2E_DIR}" || {
        log_error "Failed to change to e2e directory: ${E2E_DIR}"
        return 1
    }

    # Prepare test command
    local test_cmd="npx playwright test"

    # Add shard option if specified
    if [[ -n "${SHARD}" ]]; then
        log_info "Running E2E tests with shard: ${SHARD}"
        test_cmd="${test_cmd} --shard=${SHARD}"
    fi

    # Execute tests with error handling
    log_info "Executing: ${test_cmd}"

    # Disable exit-on-error for this section to capture exit code
    set +e
    ${test_cmd} 2>&1 | tee "${LOG_FILE}"
    local exit_code=$?
    set -e

    # Check test result
    if [[ ${exit_code} -eq 0 ]]; then
        log_success "E2E tests passed"
        return 0
    else
        log_error "E2E tests failed with exit code: ${exit_code}"
        log_error "Check log file: ${LOG_FILE}"
        return ${exit_code}
    fi
}

# Export functions
export -f check_service_health
export -f check_all_services
export -f run_e2e_tests

# If script is executed directly (not sourced)
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    run_e2e_tests
    exit $?
fi
