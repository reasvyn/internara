# Codebase Intentional States — Architecture Context

> **Folded (S7, 2026-09-06):** generic doctrine (regression = delta above pre-existing baseline,
> intentional states are not casually fixed, fix-or-file) moved to homespace. This file keeps only
> the internara-specific baselines and spec-ID convention.

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
fix them in dedicated cleanup sessions, not as drive-by edits.

> **Counts refreshed 2026-09** after the scanner upgrade (`4bad2c8fa`, `409dabb75`) — the scanners now
> use stronger analysis heuristics, so the old figures (32/11/232) are **not** comparable. Treat the
> current counts as the baseline going forward; a "regression" is a delta above these, not a match.

| Scanner | Baseline findings |
| ------- | ----------------- |
| `tools/scan_violations.py` | 264 — 200 medium / 64 low: 153 `ARCH_ACT_RESPONSE` (Action returns `Model`/`void` instead of `ActionResponse`, C7), 28 `ARCH_D4_FILLABLE`, 2 `ARCH_C4_INLINE_CACHE`, 30 `SRP_GOD_CLASS`, 51 `SRP_LONG_METHOD` |
| `tools/scan_security.py` | 0 — clean (was 11 Blade findings) |
| `tools/scan_conventions.py` | 2 — `L10N` hardcoded user-facing strings (Blade `ui/widgets/app-info` "Internara", `ui/widgets/stat-card` "Supervised Students") |

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
| Act on arch-guard scan findings | Treat the refreshed baselines (264 violations / 0 security / 2 conventions) as pre-existing; only a delta above them is a regression — fix in a dedicated cleanup session, file an issue if out of scope |
| Add a new spec | Use a 5-char alphanumeric ID (`{XXXXX}-{description}.md`) + register in `docs/specs/index.md` |

---

## Quick References

- `docs/specs/SE5Q9-*.md`, `docs/specs/89SRA-*.md` — duplicate exception contracts
- `docs/specs/index.md` — spec registry (spec-ID convention)
- `tools/scan_violations.py`, `tools/scan_security.py`, `tools/scan_conventions.py` — arch-guard scanners
- [Exception Pattern](../../docs/guides/arch/exception-pattern.md) — C8, RejectedException contract
