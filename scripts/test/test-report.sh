#!/usr/bin/env bash

# Test report generation script
# Integrates JUnit XML reports and generates unified test summary

set -euo pipefail

# Source logging functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/lib/logging.sh
source "${PROJECT_ROOT}/scripts/lib/logging.sh"

# Paths
JUNIT_DIR="${PROJECT_ROOT}/test-results/junit"
REPORTS_DIR="${PROJECT_ROOT}/test-results/reports"
LOGS_DIR="${PROJECT_ROOT}/test-results/logs"

# Check for XML parsing tools availability
HAS_XMLLINT=false
if command -v xmllint >/dev/null 2>&1; then
    HAS_XMLLINT=true
    log_debug "xmllint available - using robust XML parsing"
else
    log_debug "xmllint not available - using grep/sed fallback"
fi

# Parse JUnit XML attributes (robust version with xmllint fallback)
parse_junit_xml_attribute() {
    local xml_file="$1"
    local attribute="$2"

    if [[ "${HAS_XMLLINT}" == "true" ]]; then
        # Use xmllint for robust parsing
        xmllint --xpath "string(//testsuites/@${attribute} | //testsuite/@${attribute})" "${xml_file}" 2>/dev/null || echo "0"
    else
        # Fallback to grep/sed for portability
        grep -o "${attribute}=\"[0-9]*\"" "${xml_file}" | head -1 | grep -o '[0-9]*' || echo "0"
    fi
}

# Collect JUnit XML reports
collect_junit_reports() {
    log_info "Collecting JUnit XML reports..."

    local report_count=0

    # Check for each test suite's JUnit report
    for report in "${JUNIT_DIR}"/*.xml; do
        if [[ -f "${report}" ]]; then
            report_count=$((report_count + 1))
            log_debug "Found report: ${report}"
        fi
    done

    if [[ ${report_count} -eq 0 ]]; then
        log_warn "No JUnit XML reports found in ${JUNIT_DIR}"
        return 1
    fi

    log_success "Found ${report_count} JUnit XML report(s)"
    return 0
}

# Generate integrated test summary JSON
generate_test_summary_json() {
    log_info "Generating integrated test summary JSON..."

    local summary_file="${REPORTS_DIR}/test-summary.json"
    local timestamp
    timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

    # Initialize counters
    local total_tests=0
    local total_passed=0
    local total_failed=0
    local start_time
    local end_time
    local duration=0

    # Parse JUnit XML reports
    # Uses xmllint if available for robust parsing, falls back to grep/sed for portability

    local suite_results="{"

    # Backend tests
    if [[ -f "${JUNIT_DIR}/backend-test-results.xml" ]]; then
        local backend_tests backend_failures
        # Extract test count and failures using helper function
        backend_tests=$(parse_junit_xml_attribute "${JUNIT_DIR}/backend-test-results.xml" "tests")
        backend_failures=$(parse_junit_xml_attribute "${JUNIT_DIR}/backend-test-results.xml" "failures")
        local backend_passed=$((backend_tests - backend_failures))

        total_tests=$((total_tests + backend_tests))
        total_passed=$((total_passed + backend_passed))
        total_failed=$((total_failed + backend_failures))

        suite_results="${suite_results}\"backend\": {\"tests\": ${backend_tests}, \"passed\": ${backend_passed}, \"failed\": ${backend_failures}},"
    fi

    # Frontend Admin tests
    if [[ -f "${JUNIT_DIR}/frontend-admin-results.xml" ]]; then
        local admin_tests admin_failures
        admin_tests=$(parse_junit_xml_attribute "${JUNIT_DIR}/frontend-admin-results.xml" "tests")
        admin_failures=$(parse_junit_xml_attribute "${JUNIT_DIR}/frontend-admin-results.xml" "failures")
        local admin_passed=$((admin_tests - admin_failures))

        total_tests=$((total_tests + admin_tests))
        total_passed=$((total_passed + admin_passed))
        total_failed=$((total_failed + admin_failures))

        suite_results="${suite_results}\"frontend-admin\": {\"tests\": ${admin_tests}, \"passed\": ${admin_passed}, \"failed\": ${admin_failures}},"
    fi

    # Frontend User tests
    if [[ -f "${JUNIT_DIR}/frontend-user-results.xml" ]]; then
        local user_tests user_failures
        user_tests=$(parse_junit_xml_attribute "${JUNIT_DIR}/frontend-user-results.xml" "tests")
        user_failures=$(parse_junit_xml_attribute "${JUNIT_DIR}/frontend-user-results.xml" "failures")
        local user_passed=$((user_tests - user_failures))

        total_tests=$((total_tests + user_tests))
        total_passed=$((total_passed + user_passed))
        total_failed=$((total_failed + user_failures))

        suite_results="${suite_results}\"frontend-user\": {\"tests\": ${user_tests}, \"passed\": ${user_passed}, \"failed\": ${user_failures}},"
    fi

    # E2E tests
    if [[ -f "${JUNIT_DIR}/e2e-test-results.xml" ]]; then
        local e2e_tests e2e_failures
        e2e_tests=$(parse_junit_xml_attribute "${JUNIT_DIR}/e2e-test-results.xml" "tests")
        e2e_failures=$(parse_junit_xml_attribute "${JUNIT_DIR}/e2e-test-results.xml" "failures")
        local e2e_passed=$((e2e_tests - e2e_failures))

        total_tests=$((total_tests + e2e_tests))
        total_passed=$((total_passed + e2e_passed))
        total_failed=$((total_failed + e2e_failures))

        suite_results="${suite_results}\"e2e\": {\"tests\": ${e2e_tests}, \"passed\": ${e2e_passed}, \"failed\": ${e2e_failures}},"
    fi

    # Remove trailing comma
    suite_results="${suite_results%,}}"

    # Generate JSON summary
    cat > "${summary_file}" <<EOF
{
  "timestamp": "${timestamp}",
  "duration_seconds": ${duration},
  "total_tests": ${total_tests},
  "passed": ${total_passed},
  "failed": ${total_failed},
  "suites": ${suite_results}
}
EOF

    log_success "Test summary JSON generated: ${summary_file}"
    log_info "Total: ${total_tests} tests, ${total_passed} passed, ${total_failed} failed"

    return 0
}

# Generate GitHub Actions Summary Markdown
generate_github_summary() {
    if [[ -z "${GITHUB_STEP_SUMMARY:-}" ]]; then
        log_debug "GITHUB_STEP_SUMMARY not set, skipping GitHub Actions summary"
        return 0
    fi

    log_info "Generating GitHub Actions Summary..."

    local summary_json="${REPORTS_DIR}/test-summary.json"

    if [[ ! -f "${summary_json}" ]]; then
        log_error "Test summary JSON not found: ${summary_json}"
        return 1
    fi

    # Parse JSON using basic grep/sed (portable approach)
    local total_tests total_passed total_failed
    total_tests=$(grep -o '"total_tests": [0-9]*' "${summary_json}" | grep -o '[0-9]*')
    total_passed=$(grep -o '"passed": [0-9]*' "${summary_json}" | head -1 | grep -o '[0-9]*')
    total_failed=$(grep -o '"failed": [0-9]*' "${summary_json}" | head -1 | grep -o '[0-9]*')

    # Determine status emoji
    local status_emoji
    if [[ ${total_failed} -eq 0 ]]; then
        status_emoji="✅"
    else
        status_emoji="❌"
    fi

    # Generate Markdown summary
    {
        echo "## ${status_emoji} Test Execution Summary"
        echo ""
        echo "**Total Tests:** ${total_tests}"
        echo "**Passed:** ✅ ${total_passed}"
        echo "**Failed:** ❌ ${total_failed}"
        echo ""
        echo "### Test Suites"
        echo ""
        echo "| Suite | Tests | Passed | Failed |"
        echo "|-------|-------|--------|--------|"

        # Backend
        if grep -q '"backend"' "${summary_json}"; then
            local backend_tests backend_passed backend_failed
            backend_tests=$(grep -A 3 '"backend"' "${summary_json}" | grep '"tests"' | grep -o '[0-9]*')
            backend_passed=$(grep -A 3 '"backend"' "${summary_json}" | grep '"passed"' | grep -o '[0-9]*')
            backend_failed=$(grep -A 3 '"backend"' "${summary_json}" | grep '"failed"' | grep -o '[0-9]*')
            echo "| Backend (Pest) | ${backend_tests} | ${backend_passed} | ${backend_failed} |"
        fi

        # Frontend Admin
        if grep -q '"frontend-admin"' "${summary_json}"; then
            local admin_tests admin_passed admin_failed
            admin_tests=$(grep -A 3 '"frontend-admin"' "${summary_json}" | grep '"tests"' | grep -o '[0-9]*')
            admin_passed=$(grep -A 3 '"frontend-admin"' "${summary_json}" | grep '"passed"' | grep -o '[0-9]*')
            admin_failed=$(grep -A 3 '"frontend-admin"' "${summary_json}" | grep '"failed"' | grep -o '[0-9]*')
            echo "| Frontend Admin (Jest) | ${admin_tests} | ${admin_passed} | ${admin_failed} |"
        fi

        # Frontend User
        if grep -q '"frontend-user"' "${summary_json}"; then
            local user_tests user_passed user_failed
            user_tests=$(grep -A 3 '"frontend-user"' "${summary_json}" | grep '"tests"' | grep -o '[0-9]*')
            user_passed=$(grep -A 3 '"frontend-user"' "${summary_json}" | grep '"passed"' | grep -o '[0-9]*')
            user_failed=$(grep -A 3 '"frontend-user"' "${summary_json}" | grep '"failed"' | grep -o '[0-9]*')
            echo "| Frontend User (Jest) | ${user_tests} | ${user_passed} | ${user_failed} |"
        fi

        # E2E
        if grep -q '"e2e"' "${summary_json}"; then
            local e2e_tests e2e_passed e2e_failed
            e2e_tests=$(grep -A 3 '"e2e"' "${summary_json}" | grep '"tests"' | grep -o '[0-9]*')
            e2e_passed=$(grep -A 3 '"e2e"' "${summary_json}" | grep '"passed"' | grep -o '[0-9]*')
            e2e_failed=$(grep -A 3 '"e2e"' "${summary_json}" | grep '"failed"' | grep -o '[0-9]*')
            echo "| E2E (Playwright) | ${e2e_tests} | ${e2e_passed} | ${e2e_failed} |"
        fi

    } >> "${GITHUB_STEP_SUMMARY}"

    log_success "GitHub Actions Summary generated"

    return 0
}

# Generate all reports
generate_reports() {
    log_info "Starting report generation..."

    # Collect JUnit XML reports
    collect_junit_reports || {
        log_warn "No JUnit reports found, skipping report generation"
        return 0
    }

    # Generate test summary JSON
    generate_test_summary_json

    # Generate GitHub Actions summary (if in CI environment)
    generate_github_summary

    log_success "Report generation completed"

    return 0
}

# Export functions
export -f collect_junit_reports
export -f generate_test_summary_json
export -f generate_github_summary
export -f generate_reports

# If script is executed directly (not sourced)
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    generate_reports
    exit $?
fi
