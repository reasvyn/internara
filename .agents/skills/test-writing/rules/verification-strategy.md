# Verification Strategy — Verify First, Test Second

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Before reaching for the test suite, choose the **lightest verification that gives confidence** for
the change type at hand. The full test suite is a last resort, not a default. This rule defines the
change-type matrix, the lightweight verification toolkit, and when the full suite fire drill is
actually warranted.

## Rationale

The full suite consumes ~2GB+ RAM and takes 10+ minutes. Running it for a translation-key change or
a Blade tweak is a massive waste — and worse, it trains agents to skip verification entirely when
the suite is "too expensive". The fix is proportionality: each change type has a check that is
seconds-fast and catches the exact class of error that change can introduce.

Two failure modes this prevents:

1. **Verification by reflex** — running the full suite for every edit. Wastes 10+ minutes per change
   and makes verification the bottleneck instead of the gate.
2. **No verification at all** — skipping checks because "the suite is too slow". A translation key
   typo, a Vite build error, or a boot-time config failure slips through uncaught.

## How to Apply

### Core Principles

1. **Verify first, test second.** Always ask: *can I verify this change without running tests?*
2. **Size first.** Classify the change per the `agent-workflow` Size Triage. If the verification/test
   work spans multiple modules or is **L** size, split it into sessions — inform the user and propose
   a plan before running anything.

### Change-Type Matrix

| Change Type | What to Run | Why Not Full Suite |
|---|---|---|
| **Translation keys** (`lang/*.php`) | `vendor/bin/pint --dirty --test` each file, then `php artisan tinker --execute="echo __('key');"` for both `en` and `id` | No logic change; full suite won't catch missing keys |
| **Config files** (`config/*.php`) | `php artisan config:cache` (dry run) or visual inspection | Config is loaded at boot; full suite irrelevant |
| **Docs / Markdown** | Visual inspection only | Zero runtime impact |
| **Blade templates** | `npm run build` (if using Vite) | Frontend only; no PHP test needed |
| **CSS / JS / NPM** | `npm run build`, check for errors | Purely frontend |
| **Helper / utility function** | Quick tinker test: `php artisan tinker --execute="dump(myFunction('test'));"` | Validate contract before writing test |
| **Single method refactor** | Targeted: `php artisan test --compact --filter={ClassName}` | Isolated change, test only the affected class |
| **Cross-module refactor** | `vendor/bin/pest --testsuite={ModuleName}` (run affected module suites) | Integration risk — module integration tests cover real DB |
| **New Action / Model / Service** | Full suite ONCE after all changes batched | Highest risk — verify nothing broke elsewhere |
| **Composer dependency bump** | Run affected module suites: `vendor/bin/pest --testsuite={ModuleName}` | Lock-only changes rarely break unit tests |
| **NPM dependency bump** | `npm run build` | Frontend only |

### Lightweight Verification Toolkit

```bash
# PHP syntax + style check (0.2s)
vendor/bin/pint --dirty --test --format agent

# Frontend/Blade formatting check (0.5s)
npx prettier --check path/to/file.blade.php

# Translation resolve check (0.5s)
php artisan tinker --execute="echo __('my.key', [], 'en'); echo PHP_EOL; echo __('my.key', [], 'id');"

# Quick class autoload check (0.3s)
php artisan tinker --execute="new MyClass();"

# Quick boot/health check (2s)
php artisan system:health

# Config cache test (1s)
php artisan config:cache

# Vite build check (30s)
npm run build
```

### Full Suite Fire Drill

Only run the full suite when:

1. **Before pushing a branch** that modifies `app/` logic.
2. **After upgrading** any Composer or NPM dependency.
3. **After refactoring** core infrastructure (Base classes, Traits, Contracts).

```bash
# Full quality gate (15-20 min)
php artisan test --compact \
  && vendor/bin/pint --dirty --format agent \
  && vendor/bin/phpstan analyse --no-progress
```

Before that gate, verify with git — `git status` + `git diff` — that only intended files changed and
nothing was dropped (Edit Policy). Run the arch-guard scripts (`scan_violations.py`,
`scan_class_contracts.py`, `scan_security.py`, `scan_naming.py`, `scan_conventions.py`,
`scan_doc_links.py`) before committing.

During full suite run:
- Do NOT interrupt — let it finish.
- If it fails, note the failing test(s) and diagnose after completion.
- Do NOT fix and re-run the full suite — just run the failing test class.

## Anti-Patterns & Pitfalls

- **Full suite per-edit:** the ~2GB/10-min gate run after every small change — the exact behavior
  this rule replaces.
- **Skipping verification because "it's just docs/config":** docs get visual inspection, config gets
  `config:cache`; both are seconds and both catch real defects (broken links, boot failures).
- **Tinker `dump()` as a committed debugging tool:** the toolkit's tinker calls are throwaway checks,
  never left in code (D2: no debug calls in committed code).
- **Full-suite-on-any-failure loop:** a failing test mid-suite does not mean re-run the whole suite —
  run the failing test class (see `rules/test-diagnosis.md`).

## Verification / Detection

- Run the cheapest check in the matrix for the change type; escalate only when it passes but
  confidence is low.
- `git status` + `git diff` before the fire drill — only intended files changed.
- `python3 scripts/scan_doc_links.py` for markdown-only changes.
- The matrix is the decision procedure — if you cannot name a check for a change type, the change
  type may not be verified at all, which is itself a finding.
