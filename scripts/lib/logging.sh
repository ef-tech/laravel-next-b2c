#!/usr/bin/env bash

# Logging functions for unified output format
# Supports 5 log levels: info, success, warn, error, debug
# All output goes to stderr for proper stream separation

# Guard: Skip if logging functions are already defined
if [[ $(type -t log_info) != "function" ]]; then
    # Source color definitions
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    # shellcheck source=scripts/lib/colors.sh
    if [[ -f "${SCRIPT_DIR}/colors.sh" ]]; then
        source "${SCRIPT_DIR}/colors.sh"
    else
        # Fallback: try relative to current script
        source "$(dirname "$0")/colors.sh" 2>/dev/null || true
    fi

    # Get timestamp in ISO8601 format
    get_timestamp() {
        date -u +"%Y-%m-%dT%H:%M:%SZ"
    }

    # Log info message (blue)
    log_info() {
        local message="$*"
        echo -e "${BLUE}[INFO]${NC} $(get_timestamp) ${message}" >&2
    }

    # Log success message (green)
    log_success() {
        local message="$*"
        echo -e "${GREEN}[SUCCESS]${NC} $(get_timestamp) ${message}" >&2
    }

    # Log warning message (yellow)
    log_warn() {
        local message="$*"
        echo -e "${YELLOW}[WARN]${NC} $(get_timestamp) ${message}" >&2
    }

    # Log error message (red)
    log_error() {
        local message="$*"
        echo -e "${RED}[ERROR]${NC} $(get_timestamp) ${message}" >&2
    }

    # Log debug message (magenta, only if DEBUG=1)
    log_debug() {
        if [[ "${DEBUG:-}" == "1" ]]; then
            local message="$*"
            echo -e "${MAGENTA}[DEBUG]${NC} $(get_timestamp) ${message}" >&2
        fi
    }

    # Export log functions
    export -f get_timestamp
    export -f log_info
    export -f log_success
    export -f log_warn
    export -f log_error
    export -f log_debug
fi
