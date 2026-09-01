#!/usr/bin/env bash
# Lint gate: PHP CS (Pint) code style.
# Runs in GitHub Actions CI only. Exits non-zero on any style failure.
set -euo pipefail

echo "==> Running Pint (code style)"
vendor/bin/pint --test

echo "==> Lint passed"
