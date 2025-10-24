#!/usr/bin/env bash

# Backend test execution script
# Supports SQLite/PostgreSQL environment switching and coverage reporting

set -euo pipefail

# Source logging functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/lib/logging.sh
source "${PROJECT_ROOT}/scripts/lib/logging.sh"

# Default values
DB_ENV="${1:-sqlite}"
PARALLEL="${2:-4}"
ENABLE_COVERAGE="${3:-false}"

# Paths
BACKEND_DIR="${PROJECT_ROOT}/backend/laravel-api"
LOG_FILE="${PROJECT_ROOT}/test-results/logs/backend.log"
JUNIT_OUTPUT="${PROJECT_ROOT}/test-results/junit/backend-test-results.xml"
COVERAGE_OUTPUT="${PROJECT_ROOT}/test-results/coverage/backend"

# Validate DB environment
validate_db_env() {
    if [[ ! "${DB_ENV}" =~ ^(sqlite|postgres)$ ]]; then
        log_error "Invalid DB environment: ${DB_ENV}. Must be 'sqlite' or 'postgres'"
        return 1
    fi
}

# Validate parallel count
validate_parallel() {
    if [[ ! "${PARALLEL}" =~ ^[1-8]$ ]]; then
        log_error "Invalid parallel count: ${PARALLEL}. Must be 1-8"
        return 1
    fi
}

# Run backend tests
run_backend_tests() {
    log_info "Running backend tests (DB: ${DB_ENV}, Parallel: ${PARALLEL}, Coverage: ${ENABLE_COVERAGE})"

    # Validate inputs
    validate_db_env || return 1
    validate_parallel || return 1

    # Change to backend directory
    cd "${BACKEND_DIR}" || {
        log_error "Failed to change to backend directory: ${BACKEND_DIR}"
        return 1
    }

    # Prepare test command based on DB environment
    local test_cmd
    if [[ "${DB_ENV}" == "sqlite" ]]; then
        log_info "Using SQLite environment (fast test mode)"
        test_cmd="./vendor/bin/pest"
    else
        log_info "Using PostgreSQL environment (production-equivalent)"
        # Setup parallel test databases if needed
        if [[ -f "${PROJECT_ROOT}/scripts/parallel-test-setup.sh" ]]; then
            log_info "Setting up parallel test databases..."
            bash "${PROJECT_ROOT}/scripts/parallel-test-setup.sh" "${PARALLEL}" 2>&1 | tee -a "${LOG_FILE}"
        fi
        test_cmd="./vendor/bin/pest --parallel --processes=${PARALLEL}"
    fi

    # Add coverage option if enabled
    if [[ "${ENABLE_COVERAGE}" == "true" ]]; then
        log_info "Enabling coverage reporting..."
        test_cmd="${test_cmd} --coverage-html=${COVERAGE_OUTPUT}"
    fi

    # Add JUnit reporter (if phpunit.xml is configured)
    if grep -q "junit" phpunit.xml 2>/dev/null; then
        log_debug "JUnit reporter configured in phpunit.xml"
    else
        log_warn "JUnit reporter not configured in phpunit.xml"
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
        log_success "Backend tests passed"
        return 0
    else
        log_error "Backend tests failed with exit code: ${exit_code}"
        log_error "Check log file: ${LOG_FILE}"
        return ${exit_code}
    fi
}

# Export functions
export -f run_backend_tests
export -f validate_db_env
export -f validate_parallel

# If script is executed directly (not sourced)
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    run_backend_tests
    exit $?
fi
