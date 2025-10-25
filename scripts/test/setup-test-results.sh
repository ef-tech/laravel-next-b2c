#!/usr/bin/env bash

# Setup test results directory structure
# Creates: test-results/{junit,coverage,reports,logs}
# Preserves existing directories and handles cleanup for CI

set -euo pipefail

# Source logging functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/lib/logging.sh
source "${PROJECT_ROOT}/scripts/lib/logging.sh"

# Test results root directory
TEST_RESULTS_DIR="${PROJECT_ROOT}/test-results"

# Subdirectories
JUNIT_DIR="${TEST_RESULTS_DIR}/junit"
COVERAGE_DIR="${TEST_RESULTS_DIR}/coverage"
REPORTS_DIR="${TEST_RESULTS_DIR}/reports"
LOGS_DIR="${TEST_RESULTS_DIR}/logs"

# CI mode flag
CI_MODE="${CI:-false}"

# Setup function
setup_test_results_dirs() {
    log_info "Setting up test results directory structure..."

    # Create root directory
    if [[ ! -d "${TEST_RESULTS_DIR}" ]]; then
        mkdir -p "${TEST_RESULTS_DIR}"
        log_info "Created ${TEST_RESULTS_DIR}"
    else
        log_debug "Directory ${TEST_RESULTS_DIR} already exists"
    fi

    # Create subdirectories
    for dir in "${JUNIT_DIR}" "${COVERAGE_DIR}" "${REPORTS_DIR}" "${LOGS_DIR}"; do
        if [[ ! -d "${dir}" ]]; then
            mkdir -p "${dir}"
            log_info "Created ${dir}"
        else
            log_debug "Directory ${dir} already exists"
        fi
    done

    # Cleanup old files in CI mode (but preserve directory structure)
    if [[ "${CI_MODE}" == "true" ]]; then
        log_info "CI mode: Cleaning up old test results..."
        find "${TEST_RESULTS_DIR}" -type f -mtime +7 -delete 2>/dev/null || true
        log_info "Old files cleaned up"
    fi

    log_success "Test results directory structure is ready"
}

# Cleanup function for CI
cleanup_test_results() {
    if [[ "${CI_MODE}" != "true" ]]; then
        log_debug "Not in CI mode, skipping cleanup"
        return 0
    fi

    log_info "Cleaning up temporary test files..."

    # Remove log files older than 1 day in CI
    find "${LOGS_DIR}" -type f -mtime +1 -delete 2>/dev/null || true

    log_success "Temporary files cleaned up"
}

# Export functions
export -f setup_test_results_dirs
export -f cleanup_test_results

# If script is executed directly (not sourced)
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    setup_test_results_dirs
fi
