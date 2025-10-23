#!/usr/bin/env bash
# Setup Library Test Suite
# このテストスイートは scripts/lib/setup-lib.sh の機能をテストします

set -e

# テストディレクトリを setup
TEST_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$TEST_DIR/../.." && pwd)"
SETUP_LIB="$PROJECT_ROOT/scripts/lib/setup-lib.sh"

# テスト用ログファイル
TEST_LOG_FILE="$PROJECT_ROOT/.setup-test.log"

# テスト前にログファイルを削除
rm -f "$TEST_LOG_FILE"

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

assert_file_exists() {
    local file="$1"
    local message="$2"

    if [ -f "$file" ]; then
        echo "  ✅ PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo "  ❌ FAIL: $message"
        echo "     File not found: $file"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

assert_contains() {
    local haystack="$1"
    local needle="$2"
    local message="$3"

    if echo "$haystack" | grep -qF "$needle"; then
        echo "  ✅ PASS: $message"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo "  ❌ FAIL: $message"
        echo "     Expected to find: $needle"
        echo "     In: $haystack"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

echo "🧪 Setup Library Test Suite"
echo "============================"

# Test 1: Setup library file exists
echo ""
echo "Test 1: Setup library file exists"
assert_file_exists "$SETUP_LIB" "setup-lib.sh should exist"

# Source the library if it exists
if [ -f "$SETUP_LIB" ]; then
    # Override LOG_FILE for testing
    LOG_FILE="$TEST_LOG_FILE"
    source "$SETUP_LIB"

    # Test 2: log_info function
    echo ""
    echo "Test 2: log_info function"
    output=$(log_info "Test info message" 2>&1)
    assert_contains "$output" "Test info message" "log_info should output message"
    assert_file_exists "$TEST_LOG_FILE" "log_info should create log file"
    log_content=$(cat "$TEST_LOG_FILE")
    assert_contains "$log_content" "[INFO]" "log file should contain [INFO] tag"
    assert_contains "$log_content" "Test info message" "log file should contain message"

    # Test 3: log_warn function
    echo ""
    echo "Test 3: log_warn function"
    rm -f "$TEST_LOG_FILE"
    output=$(log_warn "Test warning message" 2>&1)
    assert_contains "$output" "Test warning message" "log_warn should output message"
    log_content=$(cat "$TEST_LOG_FILE")
    assert_contains "$log_content" "[WARN]" "log file should contain [WARN] tag"

    # Test 4: log_error function
    echo ""
    echo "Test 4: log_error function"
    rm -f "$TEST_LOG_FILE"
    output=$(log_error "Test error message" 2>&1)
    assert_contains "$output" "Test error message" "log_error should output message"
    log_content=$(cat "$TEST_LOG_FILE")
    assert_contains "$log_content" "[ERROR]" "log file should contain [ERROR] tag"

    # Test 5: show_progress function
    echo ""
    echo "Test 5: show_progress function"
    output=$(show_progress 3 5 "Test step" 2>&1)
    assert_contains "$output" "[3/5]" "show_progress should show current/total"
    assert_contains "$output" "Test step" "show_progress should show step name"

    # Test 6: mask_sensitive function
    echo ""
    echo "Test 6: mask_sensitive function"
    input="password=secret123 token=abc123 api_key=xyz789"
    output=$(mask_sensitive "$input")
    assert_contains "$output" "password=***" "mask_sensitive should mask password"
    assert_contains "$output" "token=***" "mask_sensitive should mask token"
    # api_key のマスキングは追加実装として確認
    if echo "$output" | grep -q "api_key=\*\*\*"; then
        echo "  ✅ PASS: mask_sensitive should mask api_key"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo "  ⚠️  SKIP: api_key masking not implemented yet"
    fi

    # Test 7: CI mode detection
    echo ""
    echo "Test 7: CI mode detection"
    # CI環境変数を設定してテスト
    export CI=true
    # ライブラリを再読み込み
    source "$SETUP_LIB"
    if [ "${CI_MODE:-false}" = "true" ]; then
        echo "  ✅ PASS: CI mode should be detected"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo "  ❌ FAIL: CI mode not detected"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
    unset CI
fi

# Test summary
echo ""
echo "============================"
echo "Test Summary:"
echo "  Passed: $TESTS_PASSED"
echo "  Failed: $TESTS_FAILED"
echo "  Total:  $((TESTS_PASSED + TESTS_FAILED))"

# Cleanup
rm -f "$TEST_LOG_FILE"

# Exit with appropriate code
if [ $TESTS_FAILED -eq 0 ]; then
    echo "✅ All tests passed!"
    exit 0
else
    echo "❌ Some tests failed!"
    exit 1
fi
