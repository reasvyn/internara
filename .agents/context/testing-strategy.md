# Testing Strategy — Spec-Driven Minimalism

## Description

Internara's test suite passes at **~98%** but coverage is uneven: `Core`/`User`/`Settings`/`Setup`/`Academics` are solid; domain modules (`Assessment`, `Evaluation`, `Certification`, `Document`) are thin. Full suite costs **~2GB+ RAM, 10+ min** — too slow for per-edit loops. This file is the agent's fast path for **what to test, how, and when** without wasting cycles.

---

## Core Principle

**Tests verify the spec — nothing more.** Every test traces to a requirement ID (`FR-*`/`NFR-*`/`UC-*`) in `docs/specs/{ID}-{feature}.md`. Format: `describe("{SpecID}: ...")` + `it("{SpecID}-{ReqID}: ...")`. No orphan tests, no padding. Coverage = **spec requirements covered**, not lines.

- Requirement with no test → **spec gap** (fill it)
- Test with no requirement → **orphan noise** (remove it)

See `pest-testing` skill `rules/spec-driven-minimalism.md` for the full rule.

---

## What to Run When (batched, once)

| Change | Command | When |
|--------|---------|------|
| One file, mechanical edit | `vendor/bin/pint --dirty --test --format agent` | Pre-commit, always |
| Blade/CSS/JS | `npm run build` | Pre-commit if frontend touched |
| Single method refactor | `php artisan test --compact --filter={ClassName}` | Targeted, seconds |
| Cross-module refactor | `vendor/bin/pest --testsuite={Module}` | Per-module, seconds-minutes |
| New business logic | `php artisan test --compact` **once after all batches** | On-demand only (user asks or merge) |
| PHPStan | `vendor/bin/phpstan analyse --no-progress` | On-demand only |

Full suite + PHPStan are **on-demand only** — never per-edit. This is `agent-workflow` Step 4 — Verify.

---

## Patterns by Layer

| Layer | Pattern | Example |
|-------|---------|---------|
| **Command Action** | Arrange factory+DTO → Act `execute()` → Assert `assertModelExists()` + `ActionResponse` | `tests/{Module}/{Action}Test.php` |
| **Read Action** | Seed data → Act → Assert typed return / collection shape | — |
| **Entity** | Test `bool` rule methods only for spec-named rules; no DB | — |
| **DTO** | Test shape from spec §6; no DB | — |
| **Enum** | Test `label()` / transitions only for spec-listed cases | — |
| **Livewire** | `actingAs()` → mount/render → submit → assert auth + flash | — |
| **Policy** | `allow`/`deny` per role; minimal DB | — |

Use `LazilyRefreshDatabase` (not `RefreshDatabase`), `assertModelExists` (not `assertDatabaseHas`), never mock Eloquent — use factories + real DB. Mock only external boundaries (HTTP, mail, queue, filesystem).

---

## Module-Specific Guidance

| Module health | Testing focus |
|---------------|---------------|
| **Healthy** (`Core`, `Academics`, etc.) | Add tests only for new `FR-*` you ship; keep existing suite green |
| **Needs Attention** (`Journals`, `Enrollment`, etc.) | Add tests for the bug you fix + adjacent spec gap if cheap; don't boil the ocean |
| **Needs Work / Skeleton** (`Assessment`, `Document`, `Evaluation`) | Budget extra: scaffold tests alongside the 4-layer stack; run `scan_tests.py --module {Name}` to baseline |

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Write a test for a new requirement | Find `FR-*` in `docs/specs/{ID}-{feature}.md` → `tests/{Module}/{SubModule}/{Action}Test.php` per `testing-pattern.md` |
| Fix a failing test in a sick module | Check `module-health.md` tier first — may be schema mismatch, not logic |
| Run verification in Step 4 — Verify | Follow `agent-workflow` Step 4: `git status`+`git diff`, `pint --dirty`, targeted `pest --testsuite`, `scan_violations` batch |
| Check coverage | `python3 tools/scan_tests.py --module {Module}`; map `FR-*` ↔ tests, not `phpunit --coverage` |

---

## Quick References

- `docs/guides/arch/testing-pattern.md` — full testing patterns
- `.agents/skills/pest-testing/SKILL.md` + `rules/*` — spec-driven minimalism, layer patterns, mocking boundaries
- `AGENTS.md §Agent Workflow` Step 4 — Verify — verification strategy
- `tests/Pest.php`, `tests/TestCase.php` — base test setup
