#!/usr/bin/env bash

# Color definitions for terminal output
# Supports both color and no-color modes for CI compatibility

# Check if output is a terminal and colors are supported
if [[ -t 1 ]] && [[ "${NO_COLOR:-}" != "1" ]] && [[ "${TERM:-}" != "dumb" ]]; then
    # ANSI color codes
    readonly RED='\033[0;31m'
    readonly GREEN='\033[0;32m'
    readonly YELLOW='\033[1;33m'
    readonly BLUE='\033[0;34m'
    readonly MAGENTA='\033[0;35m'
    readonly CYAN='\033[0;36m'
    readonly WHITE='\033[0;37m'
    readonly BOLD='\033[1m'
    readonly NC='\033[0m' # No Color
else
    # No colors for non-terminal or CI environments
    readonly RED=''
    readonly GREEN=''
    readonly YELLOW=''
    readonly BLUE=''
    readonly MAGENTA=''
    readonly CYAN=''
    readonly WHITE=''
    readonly BOLD=''
    readonly NC=''
fi

# Export color variables for use in other scripts
export RED GREEN YELLOW BLUE MAGENTA CYAN WHITE BOLD NC
