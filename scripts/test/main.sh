#!/usr/bin/env bash

# Main test orchestration script
# Supports flexible test suite selection, DB environment switching, and parallel execution

set -euo pipefail

# Source logging functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/lib/logging.sh
source "${PROJECT_ROOT}/scripts/lib/logging.sh"

# Default values
SUITE="all"
DB_ENV="sqlite"
PARALLEL="4"
ENABLE_COVERAGE="false"
ENABLE_REPORT="false"
CI_MODE="${CI:-false}"
FAST_MODE="false"

# Port definitions for conflict checking
readonly TEST_PORTS=(13000 13001 13002 13432 13379)

# Exit codes tracking
BACKEND_EXIT=0
FRONTEND_EXIT=0
E2E_EXIT=0
declare -a FAILED_SUITES

# Show help message
show_help() {
    cat <<EOF
Usage: $0 [OPTIONS]

Test execution orchestration script for monorepo (Backend + Frontend + E2E).

OPTIONS:
  --suite SUITE        Test suite to run: all, backend, frontend, e2e (default: all)
  --env ENV            DB environment: sqlite, postgres (default: sqlite)
  --parallel N         Parallel execution count: 1-8 (default: 4)
  --coverage           Enable coverage reporting
  --report             Generate integrated test report
  --ci                 CI mode (cleanup and optimizations)
  --fast               Fast mode (SQLite + no coverage)
  --help               Show this help message

EXAMPLES:
  # Run all tests with SQLite (fast mode)
  $0 --fast

  # Run backend tests with PostgreSQL and coverage
  $0 --suite backend --env postgres --coverage

  # Run all tests with coverage and report
  $0 --coverage --report

  # CI mode execution
  $0 --ci --coverage --report

EOF
}

# Validate environment variables
validate_env_vars() {
    log_info "Validating environment variables..."

    local required_vars=()
    local missing_vars=()

    # Check required environment variables (if any)
    # Currently, no mandatory env vars for test execution

    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        log_error "Missing required environment variables:"
        for var in "${missing_vars[@]}"; do
            log_error "  - ${var}"
        done
        return 1
    fi

    log_success "Environment variables validation passed"
    return 0
}

# Check port conflicts
check_port_conflicts() {
    log_info "Checking for port conflicts..."

    local conflicts=()

    for port in "${TEST_PORTS[@]}"; do
        if lsof -Pi :${port} -sTCP:LISTEN -t > /dev/null 2>&1; then
            local process_info
            process_info=$(lsof -Pi :${port} -sTCP:LISTEN | tail -1)
            log_warn "Port ${port} is in use: ${process_info}"
            conflicts+=("${port}")
        fi
    done

    if [[ ${#conflicts[@]} -gt 0 ]]; then
        log_warn "Found ${#conflicts[@]} port(s) in use: ${conflicts[*]}"
        log_warn "This may be expected if services are already running"
    else
        log_success "No port conflicts detected"
    fi

    return 0
}

# Parse CLI arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --suite)
                SUITE="$2"
                shift 2
                ;;
            --env)
                DB_ENV="$2"
                shift 2
                ;;
            --parallel)
                PARALLEL="$2"
                shift 2
                ;;
            --coverage)
                ENABLE_COVERAGE="true"
                shift
                ;;
            --report)
                ENABLE_REPORT="true"
                shift
                ;;
            --ci)
                CI_MODE="true"
                shift
                ;;
            --fast)
                FAST_MODE="true"
                DB_ENV="sqlite"
                ENABLE_COVERAGE="false"
                shift
                ;;
            --help)
                show_help
                exit 0
                ;;
            *)
                log_error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Run backend tests
run_backend_suite() {
    log_info "Running backend test suite..."

    bash "${PROJECT_ROOT}/scripts/test/test-backend.sh" "${DB_ENV}" "${PARALLEL}" "${ENABLE_COVERAGE}"
    BACKEND_EXIT=$?

    if [[ ${BACKEND_EXIT} -ne 0 ]]; then
        FAILED_SUITES+=("backend")
        log_error "Backend tests failed"
    else
        log_success "Backend tests passed"
    fi

    return ${BACKEND_EXIT}
}

# Run frontend tests
run_frontend_suite() {
    log_info "Running frontend test suite..."

    bash "${PROJECT_ROOT}/scripts/test/test-frontend.sh" "${ENABLE_COVERAGE}"
    FRONTEND_EXIT=$?

    if [[ ${FRONTEND_EXIT} -ne 0 ]]; then
        FAILED_SUITES+=("frontend")
        log_error "Frontend tests failed"
    else
        log_success "Frontend tests passed"
    fi

    return ${FRONTEND_EXIT}
}

# Run E2E tests
run_e2e_suite() {
    log_info "Running E2E test suite..."

    bash "${PROJECT_ROOT}/scripts/test/test-e2e.sh"
    E2E_EXIT=$?

    if [[ ${E2E_EXIT} -ne 0 ]]; then
        FAILED_SUITES+=("e2e")
        log_error "E2E tests failed"
    else
        log_success "E2E tests passed"
    fi

    return ${E2E_EXIT}
}

# Run tests with parallel execution control
run_tests() {
    log_info "Starting test execution (Suite: ${SUITE}, DB: ${DB_ENV}, Parallel: ${PARALLEL})"

    # Setup test results directory
    bash "${PROJECT_ROOT}/scripts/test/setup-test-results.sh"

    # Disable errexit for test execution (we want to run all tests even if some fail)
    set +e

    case "${SUITE}" in
        all)
            log_info "Running all test suites in parallel..."

            # Run backend and frontend in parallel
            run_backend_suite &
            local backend_pid=$!

            run_frontend_suite &
            local frontend_pid=$!

            # Wait for parallel tests
            wait ${backend_pid}
            wait ${frontend_pid}

            # Run E2E tests sequentially (requires services)
            run_e2e_suite
            ;;
        backend)
            run_backend_suite
            ;;
        frontend)
            run_frontend_suite
            ;;
        e2e)
            run_e2e_suite
            ;;
        *)
            log_error "Invalid test suite: ${SUITE}"
            log_error "Valid options: all, backend, frontend, e2e"
            set -e
            return 1
            ;;
    esac

    # Re-enable errexit
    set -e
}

# Print summary
print_summary() {
    log_info "Test execution summary:"

    local total_suites=0
    local passed_suites=0
    local failed_suites=0

    # Check backend suite
    if [[ "${SUITE}" == "all" ]] || [[ "${SUITE}" == "backend" ]]; then
        total_suites=$((total_suites + 1))
        if [[ ${BACKEND_EXIT} -eq 0 ]]; then
            passed_suites=$((passed_suites + 1))
            log_success "  backend: PASSED"
        else
            failed_suites=$((failed_suites + 1))
            log_error "  backend: FAILED (exit code: ${BACKEND_EXIT})"
        fi
    fi

    # Check frontend suite
    if [[ "${SUITE}" == "all" ]] || [[ "${SUITE}" == "frontend" ]]; then
        total_suites=$((total_suites + 1))
        if [[ ${FRONTEND_EXIT} -eq 0 ]]; then
            passed_suites=$((passed_suites + 1))
            log_success "  frontend: PASSED"
        else
            failed_suites=$((failed_suites + 1))
            log_error "  frontend: FAILED (exit code: ${FRONTEND_EXIT})"
        fi
    fi

    # Check E2E suite
    if [[ "${SUITE}" == "all" ]] || [[ "${SUITE}" == "e2e" ]]; then
        total_suites=$((total_suites + 1))
        if [[ ${E2E_EXIT} -eq 0 ]]; then
            passed_suites=$((passed_suites + 1))
            log_success "  e2e: PASSED"
        else
            failed_suites=$((failed_suites + 1))
            log_error "  e2e: FAILED (exit code: ${E2E_EXIT})"
        fi
    fi

    log_info "Total: ${total_suites} suites, ${passed_suites} passed, ${failed_suites} failed"

    if [[ ${#FAILED_SUITES[@]} -gt 0 ]]; then
        log_error "Failed suites: ${FAILED_SUITES[*]}"
        return 1
    else
        log_success "All test suites passed!"
        return 0
    fi
}

# Main execution
main() {
    parse_arguments "$@"

    log_info "Test Execution Script"
    log_info "====================="

    # Validate environment
    validate_env_vars || exit 1
    check_port_conflicts

    # Run tests
    run_tests

    # Print summary
    print_summary
    local final_exit=$?

    # Generate report if requested
    if [[ "${ENABLE_REPORT}" == "true" ]]; then
        log_info "Generating integrated test report..."
        bash "${PROJECT_ROOT}/scripts/test/test-report.sh"
        local report_exit=$?

        if [[ ${report_exit} -ne 0 ]]; then
            log_warn "Report generation failed (exit code: ${report_exit})"
        else
            log_success "Test report generated successfully"
        fi
    fi

    exit ${final_exit}
}

# Execute main function
main "$@"
