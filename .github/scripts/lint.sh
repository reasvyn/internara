#!/usr/bin/env bash
# Lint gates: PHP CS (Pint) + static analysis (PHPStan).
# Runs in GitHub Actions CI only. Exits non-zero on any style/analysis failure.
set -euo pipefail

echo "==> Running Pint (code style)"
vendor/bin/pint --test

echo "==> Running PHPStan (static analysis)"
vendor/bin/phpstan analyse --no-progress

echo "==> Lint passed"
