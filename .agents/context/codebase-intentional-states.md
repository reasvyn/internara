# Codebase Intentional States — Architecture Context

> **Last updated:** 2026-08-16 **Changes:** initial — moved from `docs/known-issues.md`

## Description

Deliberate or deferred states in the codebase that look like defects but are **intentional or
tracked**. Read this before touching exception behavior, running/acting on arch-guard scan output, or
creating a new spec. These are **not** things to casually "fix".

---

## Exception hierarchy is defined twice (tracked duplication)

- `docs/specs/SE5Q9-*.md` (FR-E1–E7) and `docs/specs/89SRA-*.md` (FR-EH1–9) both describe exception
  contracts. `ExceptionsTest` is mapped to `89SRA`.
- **Rule:** resolve the duplication in a spec pass **before** touching exception behavior.

## Pre-existing arch-guard baselines (deferred, not regressions)

These scan findings predate recent work and are unrelated to it. Do not treat them as regressions;
fix them in dedicated cleanup sessions, not as drive-by edits:

| Scanner | Baseline findings |
| ------- | ----------------- |
| `scripts/scan_violations.py` | 32 |
| `scripts/scan_security.py` | 11 (Blade templates) |
| `scripts/scan_conventions.py` | 232 (Blade templates) |

## Spec-ID convention (enforced)

- Specs are named `docs/specs/{XXXXX}-{description}.md` with a `> **Spec ID:** XXXXX` metadata line;
  the registry is `docs/specs/index.md`.
- **Rule:** use a 5-char alphanumeric ID and register new specs in `docs/specs/index.md`. Do not
  reintroduce sequential numbering.

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Change exception behavior | First reconcile the SE5Q9 vs 89SRA duplication, then update `ExceptionsTest` (mapped to 89SRA) |
| Act on arch-guard scan findings | Treat the three baselines as pre-existing; fix in a dedicated cleanup session, file an issue if out of scope |
| Add a new spec | Use a 5-char alphanumeric ID (`{XXXXX}-{description}.md`) + register in `docs/specs/index.md` |

---

## Quick References

- `docs/specs/SE5Q9-*.md`, `docs/specs/89SRA-*.md` — duplicate exception contracts
- `docs/specs/index.md` — spec registry (spec-ID convention)
- `scripts/scan_violations.py`, `scripts/scan_security.py`, `scripts/scan_conventions.py` — arch-guard scanners
- [Exception Pattern](../../docs/architecture/exception-pattern.md) — C8, RejectedException contract
