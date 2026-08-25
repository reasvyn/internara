# Spec-First Doctrine — Non-Negotiable

> **Last updated:** 2026-08-25 **Changes:** extracted from AGENTS.md into .agents/rules/ (AGENTS.md becomes navigation hub)

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
- **Code and spec disagree?** The spec is authoritative. Align code to the spec
  ("fix code, assert spec"). If the spec is demonstrably wrong, amend the spec with a recorded
  decision first, then align the code and tests.
- **Tests assert the spec:** every test traces to a requirement ID (spec-driven testing); no
  orphan tests, no spec gaps (see [verification-strategy.md](verification-strategy.md)).
- **Docs reflect the spec:** `docs/` and module docs stay in sync with specs and code.
- Failing to consult or follow the governing spec — for any instruction — is a workflow violation.

## Quick References

- [`docs/specs/index.md`](../../docs/specs/index.md) — spec registry
- [clean-code-dedup-align.md](clean-code-dedup-align.md) — companion alignment doctrine
