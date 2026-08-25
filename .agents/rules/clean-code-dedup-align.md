# Clean Code & Dedup-Align Doctrine — Non-Negotiable

> **Last updated:** 2026-08-25 **Changes:** extracted from AGENTS.md into .agents/rules/ (AGENTS.md becomes navigation hub)

## Description

Every instruction must leave the touched content and code deduplicated, aligned, and clean. This
rule defines the default bias (DRY) and how drift between spec/code/docs/tests is resolved.

---

**Every instruction must leave the touched content and code deduplicated, aligned, and clean.**

- **Deduplicate & align by default:** wherever the agent detects duplication or inconsistency —
  repeated code, copy-pasted docs, divergent patterns, stale references, duplicated requirements —
  deduplicate and align it as part of the work, even when the instruction did not ask for it
  explicitly. Never introduce a second copy of something that already exists; reuse or extract.
- **CLEAN CODE, DRY first:** apply clean-code principles with the **DRY** principle as the default
  bias — extract repeated logic into shared, named, modular units (helper, trait, Action, DTO,
  entity, doc cross-reference). Prefer **more, smaller, well-named modules** over one dense blob;
  modularization is not over-engineering, it is the prescribed direction.
- **Align spec ↔ code ↔ docs ↔ tests:** when one side drifts from the others, align the outlier to
  the spec ([Spec-First Doctrine](spec-first-doctrine.md)) instead of tolerating the drift. No
  documented behavior without code, no code without a requirement, no duplicated requirement
  across specs.
- **Surface structural decisions only:** decisions beyond the literal ask — extraction, dedup,
  refactor, doc merge, re-scoping — are stated to the user briefly when they change scope, structure,
  or behavior, and confirmed when they do. Routine dedup and alignment run silently (narration
  discipline); never narrate every decision, only the ones that affect the user.
- **Record decisions:** if a dedup/alignment decision affects a spec or an invariant, record it
  (ADR or spec amendment) rather than leaving it implicit.

## Quick References

- [spec-first-doctrine.md](spec-first-doctrine.md) — governing-spec authority
- [edit-policy.md](edit-policy.md) — surgical execution guardrails
