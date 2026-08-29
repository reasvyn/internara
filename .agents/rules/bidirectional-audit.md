# Bidirectional Audit — Spec→Code and Code→Spec Drift

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

`spec-audit` is a **bidirectional** synchronization check between feature specifications and their
implementation. It detects three categories of drift, and — crucially — it determines *which side*
(spec or code) is authoritative in each case. This is what distinguishes it from `arch-guard`
(code-first, checks conventions) and `sync-docs` (one-directional, code → docs). This rule defines
the drift model that every audit area and decision matrix builds on.

---

## The Three Drift Categories

1. **Spec → Code:** Spec promises something the code doesn't deliver (missing implementation)
2. **Code → Spec:** Code does something the spec doesn't document (unspecified behavior)
3. **Both stale:** Spec and code disagree on shared contracts (signatures, paths, names)

**Why these categories exist:** every finding in the audit must be classifiable into one of them
before triage, because the *resolution* depends on the direction (see `decision-matrix.md`). A
missing implementation is filed as an issue; an undocumented behavior gets the spec updated in-place;
a diverged contract requires a git-history decision about who moved first.

**Agent guides & skills are part of the surface.** A spec change with no matching update in the
skill/guide that documents it is a **Code → Spec** drift (guide lagging) — see `audit-areas.md`
Area 8.

---

## Key Distinction from `arch-guard`

| | `arch-guard` | `spec-audit` |
|---|--------------|--------------|
| Checks code against | Conventions and architecture rules (C1-C8, D1-D6) | Feature specifications (FR/NFR/contracts) |
| Direction | Code-first | Spec-first |
| Output | Contract violation findings | Drift findings + which side to fix |

**Why it matters:** an `arch-guard` scan will never tell you that FR-A3 (a feature requirement) has
no implementation; a `spec-audit` will never tell you that a class violates `final readonly`. When
reviewing code, run both and keep their findings separate — do not try to force a spec finding into
an arch-guard severity or vice versa. `arch-guard` remains the severity-classification reference for
contract violations; `spec-audit` supplies the spec-side evidence.

---

## Key Distinction from `sync-docs`

| | `sync-docs` | `spec-audit` |
|---|-------------|--------------|
| Purpose | Update docs to match code | Verify bidirectional sync and decide which side to fix |
| Direction | One: code → docs | Both: spec ↔ code |
| Phase | MAINTENANCE | ANALYSIS |

**Why it matters:** after a `spec-audit` fixes a spec-lagging finding, `sync-docs` may be the
downstream skill that propagates the corrected contracts into module docs. `spec-audit` decides
*what's wrong and which side is authoritative*; `sync-docs` applies the doc-side corrections.

---

## Drift Direction Drives Resolution

The audit always records the **drift direction** per finding:

| Drift Direction | Meaning | Default resolution |
|----------------|---------|--------------------|
| Spec→Code (missing impl) | Spec exists, code doesn't | **Create GitHub Issue** — spec is ahead of code |
| Code→Spec (unspecified) | Code exists, spec doesn't | **Update spec immediately** — code is ahead, spec lags |
| Both stale | Contracts diverged | Decide via git history (who changed first) |

**Why direction-first:** if you fix the wrong side you *increase* the drift. "Update the code to match
the spec" is only correct when the spec is authoritative; if the code is the newer, correct behavior,
you should update the spec instead. Never guess — use the evidence (git log, implementation, spec text)
from Phase 1 discovery.

---

## Evidence-Based Audit (Non-Negotiable)

Every finding must include:

- **File path** and **line number**
- **Concrete evidence** (code snippet, signature, git log entry)
- **Drift direction** (Spec→Code / Code→Spec / Both)
- **The correct source of truth** and why (decision transparency)

**Why it matters:** a finding without evidence cannot be triaged honestly, cannot be quoted in a
GitHub issue body, and invites a wrong-side fix. `spec-audit` verifies findings against **actual
code** — docs and skills may be stale, so a claim that "the spec says X" must be confirmed against
the spec file and "the code does Y" against the code file before it becomes a finding.

---

## Anti-Patterns / Pitfalls

- **One-directional checking** — auditing only Spec→Code (missing impl) and never the reverse leaves
  all undocumented behavior undetected, which is exactly the drift that accumulates fastest.
- **Assume code is authoritative** — both sides can be stale; the git history decides.
- **Assume spec is authoritative because it was written first** — the implementation may have moved
  on intentionally.
- **Silently deciding on ambiguous contract mismatch** — if neither side's history explains the
  change, treat it as a finding and report it; don't pick a side.
- **Mixing arch-guard and spec-audit findings** in one bucket — they measure different contracts.

---

## Verification / Detection

A bidirectional audit is complete when, for each spec in scope:

- Every spec-referenced artifact was checked for existence (Spec→Code) AND every implementation in the
  module directory was checked for a spec reference (Code→Spec).
- Every finding carries a drift direction and evidence.
- The audit surface included agent guides & skills (`AGENTS.md`, `.agents/skills/*/SKILL.md`,
  `.agents/context/`, `.agents/plans/`) for guide-lagging drift.
- The report distinguishes auto-fixed, issue-filed, and deferred findings with their directions.