# Conflic Resolution — Precedence Hierarchy

## Description

Defines the absolute precedence when governing sources disagree. This rule is the tie-breaker for spec-first vs arch-pattern conflicts.

---

## Precedence (highest → lowest)

1. **Arch Patterns `docs/guides/arch/*.md` — HIGHEST, NON-NEGOTIABLE**
   - The 16 dedicated arch patterns are the ultimate SSOT for structural invariants.
   - Covers: C1-C8, D1-D6, `Non-Negotiable`, `Anti-Patterns`, `How to Apply`, module boundaries, Entity/DTO/Model/Action/Livewire contracts.
   - Encodes global industry standards; protects security, modularity, scalability across all 19 modules.
   - **No spec, code, test, doc, or user instruction may violate an arch pattern.**

2. **Feature Specs `docs/specs/*.md` — SECOND**
   - SSOT for feature behavior (FR/NFR/UC IDs) *within* arch constraints.
   - If a spec requirement contradicts an arch pattern, **the spec is wrong and must be amended** — arch pattern wins.
   - Example: 81SMS FR-SP3 (`SchoolEntity::get()` calling `Settings::get()` directly) violated C5/MOD; spec was amended to FR-SP3/FR-SP3a with `GetSchoolEntityAction` (arch pattern > spec).

3. **Code `app/`/`resources/`/`tests/` — LOWEST**
   - Must align to both arch patterns and specs. If code disagrees with either, fix code.

## Operational Rules

- **Load arch pattern before spec change** that touches an arch concern (Entity purity C5, Livewire C1, Action triad, DTO C7, cache C4, etc.): read `docs/guides/arch/*-pattern.md` §Non-Negotiable first.
- **Spec violates arch?** Amend spec *immediately* with a recorded decision (commit with `type(scope): desc` citing the arch pattern as higher authority), then align code/tests. Do not implement violating code.
- **Code violates arch/spec?** Fix code. Never amend arch pattern to accommodate code.
- **Tests:** Must trace to spec FR/NFR, but spec itself must already be arch-compliant. No orphan tests that would require arch violation.

## Why Arch > Spec

Arch patterns protect system-wide invariants across 600+ files and 19 modules. A single feature spec cannot override them without systemic risk (security, modularity, testability). This hierarchy was explicitly affirmed as user directive on 2026-08-27 and is now recorded here as the durable conflict-resolution rule.

## Quick References

- `docs/guides/arch/*.md` — 16 arch patterns (SSOT for invariants)
- `docs/specs/index.md` — spec registry
- `.agents/rules/spec-first-doctrine.md` — spec-first doctrine + conflict resolution section (mirrors this file)
- `.agents/rules/clean-code-dedup-align.md` — alignment doctrine
