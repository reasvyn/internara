# Pre-Commit Checklist — Verify Before You Commit

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive pre-commit gate

Run this gate before committing any change. Each check prevents a class of defects that the code
review and the scanner suite would otherwise catch late.

- **`declare(strict_types=1)` present** (D1) — verify every PHP file (except migrations/config)
  opens with strict types; without it, silent scalar coercion can ship subtle bugs.
- **No debug calls in code** (D2) — `dd/dump/ray/var_dump/print_r/die` in committed code halts a
  request at runtime or leaks internals. Rescan after the last edit — debug calls like to reappear.
- **Action uses the correct triad base class** — Command (mutations, transaction+log), Read (pure
  reads, no transaction/log), Process (orchestration). A wrong base class loses or gains guarantees
  the callers rely on.
- **DTO for 3+ params; ActionResponse for structured returns** (C7) — 3+ positional parameters are
  error-prone; a DTO names the inputs. Structured results have a consistent `success/data/errors`
  shape for Livewire.
- **Business rules in Entity, not inline** (C5) — a rule in the Action body duplicates the domain
  logic instead of living once in the Entity where tests can hit it directly.
- **Cache keys registered** (C4) — every cache key lives in `config/cache-keys.php`; an inline key
  can never be flushed from another caller.
- **No N+1 queries** — relationship access in loops must be backed by `->with()`; verify list and
  dashboard views use eager loading.
- **Tests pass; Pint clean; PHPStan passes** — the spec-scoped tests prove behavior, Pint proves
  style, PHPStan (on demand / refactor completion) proves types. Run the targeted gates for the
  change type (AGENTS.md §Verification Strategy); full suite + PHPStan stay on-demand only.
- **Docs updated for new/changed behavior** (documentation-first) — module/reference docs, pattern
  docs, and metadata (`> **Last updated:**` + `**Changes:**`) reflect the change so the next agent
  and the `spec-audit`/`sync-docs` loop read correct facts.

**Ordering:** hygiene first (strict types, debug calls, docs), then contracts (base class, DTO,
Entity, cache), then verification (tests → pint → phpstan). A failing test gate blocks everything
after it.