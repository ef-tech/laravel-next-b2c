#!/usr/bin/env bash
# OS Detection Test Suite

set -e

TEST_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$TEST_DIR/../.." && pwd)"
SETUP_LIB="$PROJECT_ROOT/scripts/lib/setup-lib.sh"

# テストカウンタ
TESTS_PASSED=0
TESTS_FAILED=0

# テストヘルパー関数
assert_equals() {
    local expected="$1"
    local actual="$2"
    local message="$3"

    if [ "$expected" = "$actual" ]; then
        echo "  ✅ PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo "  ❌ FAIL: $message"
        echo "     Expected: $expected"
        echo "     Actual:   $actual"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

assert_not_empty() {
    local value="$1"
    local message="$2"

    if [ -n "$value" ]; then
        echo "  ✅ PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo "  ❌ FAIL: $message"
        echo "     Value is empty"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

echo "🧪 OS Detection Test Suite"
echo "=========================="

# Source the library
LOG_FILE="/dev/null"  # ログを無効化
source "$SETUP_LIB"

# Test 1: detect_os function exists
echo ""
echo "Test 1: detect_os function exists"
if type detect_os &>/dev/null; then
    echo "  ✅ PASS: detect_os function should exist"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo "  ❌ FAIL: detect_os function should exist"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi

# Test 2: detect_os sets DETECTED_OS variable
echo ""
echo "Test 2: detect_os sets DETECTED_OS variable"
detect_os
assert_not_empty "$DETECTED_OS" "DETECTED_OS should be set"

# Test 3: detect_os detects current OS
echo ""
echo "Test 3: detect_os detects current OS"
current_os=$(uname -s)
case "$current_os" in
    Darwin*)
        assert_equals "macos" "$DETECTED_OS" "Should detect macOS"
        ;;
    Linux*)
        if grep -qi microsoft /proc/version 2>/dev/null; then
            assert_equals "wsl2" "$DETECTED_OS" "Should detect WSL2"
        else
            assert_equals "linux" "$DETECTED_OS" "Should detect Linux"
        fi
        ;;
esac

# Test 4: detect_os sets PACKAGE_MANAGER variable
echo ""
echo "Test 4: detect_os sets PACKAGE_MANAGER variable"
assert_not_empty "$PACKAGE_MANAGER" "PACKAGE_MANAGER should be set"

# Test 5: get_install_guide function exists
echo ""
echo "Test 5: get_install_guide function exists"
if type get_install_guide &>/dev/null; then
    echo "  ✅ PASS: get_install_guide function should exist"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo "  ❌ FAIL: get_install_guide function should exist"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi

# Test 6: get_install_guide returns appropriate command
echo ""
echo "Test 6: get_install_guide returns appropriate command"
guide=$(get_install_guide "docker")
assert_not_empty "$guide" "Install guide should not be empty"

# Test Summary
echo ""
echo "=========================="
echo "Test Summary:"
echo "  Passed: $TESTS_PASSED"
echo "  Failed: $TESTS_FAILED"
echo "  Total:  $((TESTS_PASSED + TESTS_FAILED))"

if [ $TESTS_FAILED -eq 0 ]; then
    echo "✅ All tests passed!"
    exit 0
else
    echo "❌ Some tests failed!"
    exit 1
fi
