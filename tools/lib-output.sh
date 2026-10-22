#!/usr/bin/env bash
# Shared terminal output. Colors only on a terminal, or FORCE_COLOR=1.
MDO_GREEN='' MDO_RED='' MDO_YELLOW='' MDO_RESET=''
if [[ -z ${NO_COLOR:-} ]] && { [[ -t 1 ]] || [[ ${FORCE_COLOR:-0} == 1 ]]; }; then
    MDO_GREEN=$'\033[1;32m'
    MDO_RED=$'\033[1;31m'
    MDO_YELLOW=$'\033[1;33m'
    MDO_RESET=$'\033[0m'
fi
mdo_ok()   { printf '%s[OK]%s %s\n' "$MDO_GREEN" "$MDO_RESET" "$*"; }
mdo_err()  { printf '%s[ERRORE]%s %s\n' "$MDO_RED" "$MDO_RESET" "$*"; }
mdo_warn() { printf '%s[AVVISO]%s %s\n' "$MDO_YELLOW" "$MDO_RESET" "$*"; }
mdo_info() { printf '[INFO] %s\n' "$*"; }
