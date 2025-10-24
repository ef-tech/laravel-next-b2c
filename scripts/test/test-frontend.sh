#!/usr/bin/env bash

# Frontend test execution script
# Supports parallel execution for Admin App and User App

set -euo pipefail

# Source logging functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/lib/logging.sh
source "${PROJECT_ROOT}/scripts/lib/logging.sh"

# Default values
ENABLE_COVERAGE="${1:-false}"

# Paths
ADMIN_APP_DIR="${PROJECT_ROOT}/frontend/admin-app"
USER_APP_DIR="${PROJECT_ROOT}/frontend/user-app"
ADMIN_LOG_FILE="${PROJECT_ROOT}/test-results/logs/frontend-admin.log"
USER_LOG_FILE="${PROJECT_ROOT}/test-results/logs/frontend-user.log"
ADMIN_JUNIT_OUTPUT="${PROJECT_ROOT}/test-results/junit/frontend-admin-test-results.xml"
USER_JUNIT_OUTPUT="${PROJECT_ROOT}/test-results/junit/frontend-user-test-results.xml"
ADMIN_COVERAGE_OUTPUT="${PROJECT_ROOT}/test-results/coverage/frontend-admin"
USER_COVERAGE_OUTPUT="${PROJECT_ROOT}/test-results/coverage/frontend-user"

# Run Admin App tests
run_admin_tests() {
    log_info "Running Admin App tests (Coverage: ${ENABLE_COVERAGE})"

    # Change to admin-app directory
    cd "${ADMIN_APP_DIR}" || {
        log_error "Failed to change to admin-app directory: ${ADMIN_APP_DIR}"
        return 1
    }

    # Prepare test command
    local test_cmd="npm run test"

    # Add coverage option if enabled
    if [[ "${ENABLE_COVERAGE}" == "true" ]]; then
        log_info "Enabling coverage reporting for Admin App..."
        test_cmd="npm run test:coverage"
    fi

    # Execute tests with error handling
    log_info "Executing: ${test_cmd} (Admin App)"

    # Disable exit-on-error for this section to capture exit code
    set +e
    ${test_cmd} 2>&1 | tee "${ADMIN_LOG_FILE}"
    local exit_code=$?
    set -e

    # Check test result
    if [[ ${exit_code} -eq 0 ]]; then
        log_success "Admin App tests passed"
        return 0
    else
        log_error "Admin App tests failed with exit code: ${exit_code}"
        log_error "Check log file: ${ADMIN_LOG_FILE}"
        return ${exit_code}
    fi
}

# Run User App tests
run_user_tests() {
    log_info "Running User App tests (Coverage: ${ENABLE_COVERAGE})"

    # Change to user-app directory
    cd "${USER_APP_DIR}" || {
        log_error "Failed to change to user-app directory: ${USER_APP_DIR}"
        return 1
    }

    # Prepare test command
    local test_cmd="npm run test"

    # Add coverage option if enabled
    if [[ "${ENABLE_COVERAGE}" == "true" ]]; then
        log_info "Enabling coverage reporting for User App..."
        test_cmd="npm run test:coverage"
    fi

    # Execute tests with error handling
    log_info "Executing: ${test_cmd} (User App)"

    # Disable exit-on-error for this section to capture exit code
    set +e
    ${test_cmd} 2>&1 | tee "${USER_LOG_FILE}"
    local exit_code=$?
    set -e

    # Check test result
    if [[ ${exit_code} -eq 0 ]]; then
        log_success "User App tests passed"
        return 0
    else
        log_error "User App tests failed with exit code: ${exit_code}"
        log_error "Check log file: ${USER_LOG_FILE}"
        return ${exit_code}
    fi
}

# Run frontend tests in parallel
run_frontend_tests_parallel() {
    log_info "Running frontend tests in parallel (Admin App + User App)"

    # Run tests in background and capture PIDs
    run_admin_tests &
    local admin_pid=$!

    run_user_tests &
    local user_pid=$!

    # Wait for both processes and capture exit codes
    local admin_exit=0
    local user_exit=0

    wait ${admin_pid} || admin_exit=$?
    wait ${user_pid} || user_exit=$?

    # Check combined results
    if [[ ${admin_exit} -eq 0 ]] && [[ ${user_exit} -eq 0 ]]; then
        log_success "All frontend tests passed"
        return 0
    elif [[ ${admin_exit} -ne 0 ]] && [[ ${user_exit} -ne 0 ]]; then
        log_error "Both Admin App and User App tests failed"
        return 1
    elif [[ ${admin_exit} -ne 0 ]]; then
        log_error "Admin App tests failed"
        return ${admin_exit}
    else
        log_error "User App tests failed"
        return ${user_exit}
    fi
}

# Export functions
export -f run_admin_tests
export -f run_user_tests
export -f run_frontend_tests_parallel

# If script is executed directly (not sourced)
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    run_frontend_tests_parallel
    exit $?
fi
