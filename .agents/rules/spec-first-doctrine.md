# Spec-First Doctrine — Non-Negotiable

## Description

Every action, on every instruction, in any form, must be driven by the governing spec. This rule
defines what that means operationally for agents.

---

**Every action, on every instruction, in any form, must be driven by the governing spec.**
`docs/specs/*.md` is the single authoritative source of truth — it defines intent, requirements
(FR/NFR/UC IDs), scope, and acceptance criteria. The spec outranks the user's literal words,
ad-hoc reasoning, and existing code. This applies to every task type: bug fixes, features,
refactors, tests, docs updates, audits, scripts, config tweaks, maintenance, and one-line
questions alike.

- **Consult the spec before any work:** locate the governing spec (foundation, module, or
  feature) in Step 1 and treat it as the source of truth for what "done" means.
- **No behavior without a requirement:** never change behavior, add features, or fix bugs
  without a corresponding requirement ID (FR/NFR/UC). If none exists, write it into the spec
  first — spec-first, never fix-first.
- **Code and spec disagree?** The spec is authoritative over code. Align code to the spec
  ("fix code, assert spec"). If the spec is demonstrably wrong, amend the spec with a recorded
  decision first, then align the code and tests.
- **Tests assert the spec:** every test traces to a requirement ID (spec-driven testing); no
  orphan tests, no spec gaps (see [verification-strategy.md](verification-strategy.md)).
- **Docs reflect the spec:** `docs/` and module docs stay in sync with specs and code.
- Failing to consult or follow the governing spec — for any instruction — is a workflow violation.

## Conflict Resolution — Precedence Hierarchy (arch patterns > spec)

When governing sources disagree, the following absolute precedence applies:

1.  **Arch Patterns `docs/guides/arch/*.md` — HIGHEST** — The 16 dedicated arch patterns are the ultimate SSOT for structural invariants (C1-C8, D1-D6, Non-Negotiable, Anti-Patterns). They encode global industry standards and non-negotiable contracts. **No spec, code, or user instruction may violate an arch pattern.**
2.  **Feature Specs `docs/specs/*.md` — SECOND** — Specs are SSOT for feature behavior (FR/NFR/UC) *within* arch constraints. If a spec requirement contradicts an arch pattern, **the spec is wrong and must be amended** (arch pattern wins). Amend the spec first with a recorded decision (as done for 81SMS FR-SP3 → FR-SP3/FR-SP3a with GetSchoolEntityAction), then align code and tests.
3.  **Code `app/`/`resources/` — LOWEST** — Code must align to both. If code disagrees with spec *or* arch pattern, fix code.

**Operational rule:** Before any spec change that touches an arch concern (Entity purity C5, Livewire C1, Action triad, DTO C7, cache C4, etc.), load the relevant `docs/guides/arch/*-pattern.md` §Non-Negotiable and verify compliance. If spec and arch pattern conflict, **amend spec immediately** (do not implement violating code) and note the arch pattern as higher authority in the commit/spec Changes.

**Why arch > spec:** Arch patterns protect system-wide invariants (security, modularity, scalability, testability) across all 19 modules and 600+ files; a single spec cannot override them without systemic risk. This was explicitly affirmed as user directive on 2026-08-27.

## Quick References

- [`docs/specs/index.md`](../../docs/specs/index.md) — spec registry
- [clean-code-dedup-align.md](clean-code-dedup-align.md) — companion alignment doctrine
