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

# Common test execution function
run_app_tests() {
    local app_name="$1"
    local app_dir="$2"
    local log_file="$3"

    log_info "Running ${app_name} tests (Coverage: ${ENABLE_COVERAGE})"

    # Change to app directory
    cd "${app_dir}" || {
        log_error "Failed to change to ${app_name} directory: ${app_dir}"
        return 1
    }

    # Prepare test command
    local test_cmd="npm run test"
    if [[ "${ENABLE_COVERAGE}" == "true" ]]; then
        log_info "Enabling coverage reporting for ${app_name}..."
        test_cmd="npm run test:coverage"
    fi

    # Execute tests with error handling
    log_info "Executing: ${test_cmd} (${app_name})"

    # Disable exit-on-error for this section to capture exit code
    set +e
    ${test_cmd} 2>&1 | tee "${log_file}"
    local exit_code=$?
    set -e

    # Check test result
    if [[ ${exit_code} -eq 0 ]]; then
        log_success "${app_name} tests passed"
        return 0
    else
        log_error "${app_name} tests failed with exit code: ${exit_code}"
        log_error "Check log file: ${log_file}"
        return ${exit_code}
    fi
}

# Run Admin App tests
run_admin_tests() {
    run_app_tests "Admin App" "${ADMIN_APP_DIR}" "${ADMIN_LOG_FILE}"
}

# Run User App tests
run_user_tests() {
    run_app_tests "User App" "${USER_APP_DIR}" "${USER_LOG_FILE}"
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

# If script is executed directly (not sourced)
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    run_frontend_tests_parallel
    exit $?
fi
