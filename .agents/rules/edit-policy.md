# Edit Policy — Surgical Edits Only

> **Last updated:** 2026-08-25 **Changes:** extracted from AGENTS.md into .agents/rules/ (AGENTS.md becomes navigation hub)

## Description

Guardrail against silent information loss: read before editing, edit minimally, and prove
losslessness with git.

---

Guardrail against silent information loss.

- **Read before edit** — read the full current content of every file you may touch (Step 2 — Plan).
- **Edit, don't rewrite** — change only what the instruction requires; preserve unrelated code,
  comments, formatting, and context. A full rewrite is justified only for small files where the
  rewrite IS the intent.
- **Verify with git** — compare `git diff` before/after each change to prove nothing unintended
  was altered or dropped (Step 4 — Verify). This is the final check that an edit was lossless.
- **Scope smallest** — keep the change surface minimal (Step 1 — Understand). Fewer touched files = fewer
  places for errors to hide.
