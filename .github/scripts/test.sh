#!/usr/bin/env bash
# Full test suite with a coverage gate (default 80%).
# Runs in GitHub Actions CI only. Assumes composer deps are installed and the
# environment file is set up by the caller.
set -euo pipefail

MIN_COVERAGE="${MIN_COVERAGE:-80}"
COVERAGE="${COVERAGE_RUN:-xdebug}"

echo "==> Running Pest test suite (coverage gate: ${MIN_COVERAGE}%)"
vendor/bin/pest --coverage --min="${MIN_COVERAGE}"

echo "==> Tests passed"
