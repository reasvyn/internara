#!/usr/bin/env bash
# Architecture guard scanners. Verifies the codebase respects the C1-C8 / D1-D6
# invariants (scan_violations), security anti-patterns (scan_security), and
# conventions (scan_conventions). Each scanner is run with --strict so the job
# fails (exit 1) on any finding. Runs in GitHub Actions CI only.
set -euo pipefail

fail=0

run_guard() {
    local name="$1"
    shift
    echo "==> $name"
    if ! python3 "tools/$name.py" --strict --format summary "$@"; then
        echo "::error::$name reported violations"
        fail=1
    fi
}

run_guard scan_violations
run_guard scan_security
run_guard scan_conventions

if [ "$fail" -ne 0 ]; then
    echo "==> Architecture guards FAILED"
    exit 1
fi

echo "==> Architecture guards passed"
