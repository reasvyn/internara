# Verification Strategy — Batched Quality Gates

## Description

Batch all changes first, then verify once. Tests verify the spec — nothing more. Full suite is on-demand only; default verification is targeted per-change checks.

---

**Batch ALL changes first, then verify ONCE.** Full suite is ~2GB+ memory, 10+ minutes.

**Tests verify the spec — nothing more.** Every test traces to a requirement ID (`FR-*` / `NFR-*` /
`UC-*`) in `docs/specs/{ID}-{feature}.md`. Test descriptions use the `{SpecID}-{ReqID}: Test
description...` format, grouped under `describe("{SpecID}: Test description...")`. Coverage is
measured in spec requirements covered, not lines
of code. A requirement with no test is a spec gap (fill it); a test with no requirement is orphan
noise (remove it).

**Spec-driven minimalism — write only the tests the spec requires, then stop.** This is deliberate:
it speeds up development and verification (spec-scoped tests run in seconds vs. 10+ minutes for the
full suite), reduces resource usage (~2GB+ RAM for the full suite), and reduces cognitive overwhelm
— a suite mapping 1:1 to requirement IDs is self-explaining. When tempted to add a test "for
safety", ask which requirement it verifies; no requirement means don't write it.

**Full suite is on-demand only.** Do NOT run `php artisan test --compact` (full suite) as part of routine work — it is slow (~2GB+ RAM, 10+ minutes) and is only run when the user explicitly asks for it. Default verification is the targeted per-change checks in the table below (module suite, `--filter`, pint, prettier, arch-guard scanners). The full suite stays reserved for merge-day or user-requested full verification.

| Change Type | Verification |
|-------------|-------------|
| Translation keys (`lang/*.php`) | `vendor/bin/pint --dirty --test --format agent` + `php artisan tinker --execute="echo __('key');"` |
| Blade templates | `vendor/bin/pint --dirty --test --format agent` (Blade via `Pint/laravel_blade` rule) + `npm run build` |
| Config/docs/markdown | Visual inspection (`*.md` is prettier-ignored — specs/docs use deliberate compact tables; see issue #384) |
| CSS/JS/JSON/non-PHP | `npx prettier --check` + `npm run build` |
| Refactoring (rename, extract) | Targeted test: `php artisan test --compact --filter={TestSuite}` |
| New feature / business logic | Full suite ONCE after all changes batched |
| Dependency updates | `vendor/bin/pest --testsuite={ModuleName}` (run affected module suites) |
| Test pruning / spec-gap filling | Manual per-module audit — map tests ↔ spec requirements, batch edits, then run targeted module tests once |

```bash
# Version-control verification (before/after every change — Edit Policy)
git status
git diff                  # review every change before/after editing
git diff --stat           # confirm only intended files were touched

# Targeted tests
vendor/bin/pest --testsuite={ModuleName}   # Run tests for a specific module (replace {ModuleName})
php artisan test --compact --filter={ClassName}
vendor/bin/pint --dirty --test --format agent   # PHP + Blade syntax & style (Pint/laravel_blade rule)
npx prettier --check <file>                     # Non-PHP only (CSS/JS/JSON — *.php, *.blade.php, *.md ignored)
php artisan system:health

# Full verification (after refactoring or before merge) — ONLY when the user explicitly asks
php artisan test --compact   # Run full test suite (all modules)
vendor/bin/pint --dirty --format agent

# Architecture enforcement
python3 tools/scan_violations.py         # C1-C8, D1-D6
python3 tools/scan_class_contracts.py    # Action/Entity/DTO/Model/Enum
python3 tools/scan_security.py           # XSS, SQLi, CSRF, auth
python3 tools/scan_naming.py             # Naming conventions
python3 tools/scan_conventions.py        # strict_types, Fillable, debug
python3 tools/scan_doc_links.py          # Broken links in docs + .agents/context/ + .agents/memory/ + outdated/missing metadata detection
```

## Quick References

- [pre-commit-checklist.md](pre-commit-checklist.md) — final gate before every commit
- [edit-policy.md](edit-policy.md) — git-diff lossless proof during edits
- [automation-first.md](automation-first.md) — scanner-first quality gates
