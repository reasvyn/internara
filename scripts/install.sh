#!/usr/bin/env bash
# Internara — One-line installer
# Fetches Internara via Git, installs PHP/JS deps, and runs the setup wizard.
#
# One-line (remote):
#   curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash
#   curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash -s -- --dir my-pkl --branch main
#
# Local (inside an already-cloned repo):
#   bash scripts/install.sh
#   bash scripts/install.sh --help
#
# Steps: git clone → composer install → npm install && npm run build → php artisan setup:install
# Requirements: git, php >=8.4, composer, node >=20, npm >=10

set -euo pipefail

REPO_URL="https://github.com/reasvyn/internara.git"
DEFAULT_BRANCH="main"
DEFAULT_DIR="internara"

BRANCH="$DEFAULT_BRANCH"
TARGET_DIR="$DEFAULT_DIR"
SKIP_COMPOSER=0
SKIP_NPM=0
SKIP_SETUP=0
SETUP_ARGS=()

# ── args ────────────────────────────────────────────────────────────────
usage() {
  cat <<'USAGE'
Usage:
  Remote (one-liner):
    curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash
    curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash -s -- [options]

  Local (inside repo):
    bash scripts/install.sh [options]

Options:
  --dir <name>        Target directory for git clone (default: internara). Ignored when run inside an existing checkout.
  --branch <name>     Git branch/tag to clone (default: main)
  --skip-composer     Skip composer install
  --skip-npm          Skip npm install && npm run build
  --skip-setup        Skip php artisan setup:install
  -h, --help          Show this help

Any extra arguments after -- are forwarded to `php artisan setup:install`
(e.g. --force, --check-only). Example:
  bash scripts/install.sh -- --force
  curl ... | bash -s -- -- --check-only
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -h|--help) usage; exit 0 ;;
    --dir) TARGET_DIR="${2:?--dir requires a value}"; shift 2 ;;
    --branch) BRANCH="${2:?--branch requires a value}"; shift 2 ;;
    --skip-composer) SKIP_COMPOSER=1; shift ;;
    --skip-npm) SKIP_NPM=1; shift ;;
    --skip-setup) SKIP_SETUP=1; shift ;;
    --) shift; SETUP_ARGS+=("$@"); break ;;
    --*) echo "Unknown option: $1" >&2; usage >&2; exit 2 ;;
    *) SETUP_ARGS+=("$1"); shift ;;
  esac
done

info()  { printf "\033[1;34m==>\033[0m %s\n" "$*"; }
ok()    { printf "\033[1;32m✔\033[0m %s\n" "$*"; }
warn()  { printf "\033[1;33m!\033[0m %s\n" "$*" >&2; }
die()   { printf "\033[1;31m✘\033[0m %s\n" "$*" >&2; exit 1; }

# ── helpers ─────────────────────────────────────────────────────────────
inside_checkout() {
  [[ -f "./artisan" && -f "./composer.json" ]] && grep -q '"name"[[:space:]]*:[[:space:]]*"reasvyn/internara"' ./composer.json 2>/dev/null || [[ -f "./artisan" && -f "./composer.json" ]]
}

check_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Missing required command: $1. Please install it first."
}

# ── main ────────────────────────────────────────────────────────────────
info "Internara one-line installer"

# When run via `curl | bash`, we are NOT inside a checkout → clone.
# When run via `bash scripts/install.sh` from inside the repo → skip clone.
if inside_checkout; then
  info "Detected existing checkout at $(pwd) — skipping git clone."
  PROJECT_DIR="$(pwd)"
else
  check_cmd git
  if [[ -e "$TARGET_DIR" ]]; then
    die "Target directory '$TARGET_DIR' already exists. Remove it or pass --dir <other-name>."
  fi
  info "Cloning $REPO_URL (branch: $BRANCH) → $TARGET_DIR/"
  git clone --branch "$BRANCH" --depth 1 "$REPO_URL" "$TARGET_DIR"
  PROJECT_DIR="$(cd "$TARGET_DIR" && pwd)"
  cd "$PROJECT_DIR"
fi

# From here, PROJECT_DIR is the app root. All steps run inside it.
cd "$PROJECT_DIR"

if [[ $SKIP_COMPOSER -eq 0 ]]; then
  check_cmd php
  check_cmd composer
  # php version check (>=8.4)
  if ! php -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);'; then
    die "PHP >= 8.4 is required (found $(php -r 'echo PHP_VERSION;'))."
  fi
  info "Installing PHP dependencies (composer install)…"
  composer install --no-interaction --prefer-dist
  ok "composer install done"
else
  warn "Skipping composer install (--skip-composer)"
fi

if [[ $SKIP_NPM -eq 0 ]]; then
  check_cmd npm
  if ! command -v node >/dev/null 2>&1; then
    warn "node not found — skipping npm build. Install Node.js 20+ and run 'npm install && npm run build' manually."
  else
    info "Installing JS dependencies (npm install)…"
    npm install
    info "Building frontend assets (npm run build)…"
    npm run build
    ok "frontend build done"
  fi
else
  warn "Skipping npm install/build (--skip-npm)"
fi

if [[ $SKIP_SETUP -eq 0 ]]; then
  check_cmd php
  info "Running installer (php artisan setup:install)…"
  php artisan setup:install "${SETUP_ARGS[@]}"
  ok "setup:install done"
  cat <<NEXT

Next steps:
  1. Copy the signed setup URL printed above and open it in your browser to finish the 6-step wizard.
  2. Start the dev servers:
       composer run dev
     Or individually:
       php artisan serve
       php artisan queue:work   # in another terminal

  Verify: php artisan system:health
  Docs:   docs/getting-started.md

NEXT
else
  warn "Skipping setup:install (--skip-setup). Run 'php artisan setup:install' when ready."
fi

ok "All done — project at $PROJECT_DIR"
