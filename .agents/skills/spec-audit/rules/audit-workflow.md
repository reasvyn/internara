# Audit Workflow — Pipeline, Report Structure & Scope Discipline

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

`spec-audit` runs a **custom pipeline** rather than the standard implementation flow:

```
SCOPE → DISCOVER → AUDIT (6 areas + run tests) → TRIAGE → FIX/ISSUE → WRITE TESTS →
FINALIZE → REPORT
```

This skill is an ANALYSIS skill — it does not implement features and it does not fix code. Its
outputs are findings, auto-fixes (specs/docs/tests), GitHub Issues, and a report. This rule defines
the full pipeline, the report structure, and the key-rules discipline that keeps the audit honest.

---

## Pipeline Phases

### Phase 1 — Scope & Discovery

- **Load context:** `docs/specs/index.md`, `docs/modules/index.md`, GitHub Issues.
- **Size Triage** the scope (see `scope-configuration.md`) — never run a full `--all` audit in one
  pass; split by phase.
- **Resolve scope** arguments (spec name, `--module`, `--phase`, `--area`, `--work`, `--guides`) to a
  concrete spec list or guides surface.
- **Discover artifacts** per spec and **build the audit map** (spec ID, phase, module, referenced
  files/classes/routes, FR/NFR IDs, cross-refs, prerequisites/next steps).

### Phase 2 — Audit

Execute Areas 1-8 in order (`audit-areas.md`), including **running the spec's tests** and
**writing missing spec'd-component tests** (`run-and-write-tests.md`).

### Phase 3 — Triage

Classify each finding (direction, root cause, source of truth, severity) and resolve it through the
decision matrix (`decision-matrix.md`).

### Phase 4 — Fix or Issue

- Auto-fix minor / spec-lagging / test-gap findings in-run (`fix-or-issue.md`)
- File GitHub Issues for the rest

### Phase 5 — Finalize

Update existing GitHub Issues (phase status, active work, blockers, spec count), commit the
auto-fixes and new tests, push if requested or for a full audit.

### Phase 6 — Report

Deliver the visual report (structure below) even with zero findings.

---

## Report Structure

```markdown
# Spec Audit Report

**Scope:** {scope description}
**Specs audited:** {N}/{total}
**Date:** {date}

## Executive Summary

| Metric | Count |
|--------|-------|
| Specs audited | {N} |
| Total findings | {N} |
| Auto-fixed (minor) | {N} |
| Tests run | {N} ({pass/fail}) |
| Tests written | {N} |
| GitHub Issues created | {N} |
| Specs fully synced | {N}/{N} ({percent}%) |
| Specs with drift | {N} |

## Sync Status by Spec

| Spec | # | Paths | Contracts | Reqts | Tests | X-Refs | Status |
|------|---|-------|-----------|-------|-------|--------|--------|
| authentication.md | 17 | ✅ | ✅ | ⚠️ | ❌ | ✅ | ⚠️ |
```

Legend: ✅ synced | ⚠️ drift (auto-fixed) | ❌ drift (issue created)

For a **work scope**, replace the flat findings list with a **per-channel verdict**:

| Channel | Verdict | Findings | Resolved |
|---------|---------|----------|----------|
| Implementation | ✅ / ⚠️ / ❌ | {count} | {auto-fixed / issues} |
| Testing | ✅ / ⚠️ / ❌ | {count} | {tests written / fixed} |
| Documentation | ✅ / ⚠️ / ❌ | {count} | {auto-fixed / issues} |

Then detail sections:

- **Findings Detail** — Auto-Fixed (Minor) table, GitHub Issues Created table, Tests Written
  (Test-Gap Fill) table
- **Drift Analysis** — Spec-Forward (code needs to catch up) / Code-Forward (spec needs to catch up) /
  Both Stale (contracts diverged)
- **Phase Status Impact** — before/after table
- **Recommendations** — prioritized list

**Why a visual report with zero findings:** the report is the audit's deliverable even when nothing is
wrong — "fully synced" is a fact worth recording, and the Executive Summary metrics only compute if the
report is produced every time.

---

## Key Rules (Scope Discipline)

1. **Spec is the starting point** — always read the spec first, then verify against code
2. **Bidirectional check** — always check both Spec→Code AND Code→Spec
3. **Evidence-based** — every finding includes file path, line number, concrete evidence
4. **Decision transparency** — always explain WHY one side should be updated over the other
5. **Minor auto-fix** — only fix trivial issues (typos, cross-refs, metadata); never change behavior
6. **Major → Issue** — non-trivial findings become GitHub Issues with full context
7. **No spec rewriting** — if a spec needs major rewrites, create an Issue; don't rewrite in-place
   (except spec-lagging catch-up)
8. **No code fixing** — if code needs changes, create an Issue; don't modify business logic
   (writing **tests** for existing code is NOT code fixing — it is the audit's Test-Gap Fill Rule)
9. **Always update GitHub Issues** — reflect audit findings in GitHub Issues
10. **Always report** — deliver the visual report even if zero findings
11. **Audit every module** — not just the one being changed
12. **Record issues even if fixing is out of scope** — prioritization happens downstream
13. **Do NOT fix issues during audit** — that is the refactoring phase (except minor auto-fix,
    **spec-lagging catch-up**, and **test-gap filling**)
14. **Verify findings against actual code** — docs and skills may be stale
15. **Check existing issues before filing** — prevent duplicates
16. **Audit agent guides & skills too** — they must stay consistent with the specs (Area 8)
17. **Run the spec's tests** — execute the audited spec's test suite and confirm it passes; a failing
    or absent test suite is a finding, not a silent pass

**Why these rules exist:** items 5-8 create the audit's *fix boundary* — without them the analysis
skill silently becomes an implementation skill, rewriting behavior it was asked only to verify.
Items 17 and the Test-Gap Fill duty are the recent strengthening: the audit does not *assume* tests
are green or present — it runs and writes them.

---

## Verification Checklist

- Scope determined and confirmed; all specs in scope read; audit maps built
- Work scope — all three channels audited (Implementation, Testing, Documentation)
- Code — all 4 layers audited
- Areas 1-8 each verified (paths, contracts, requirements, tests, cross-refs, completeness,
  dependencies, guides)
- **Area 4 tests RAN and PASSED**; **Test-Gap Fill**: spec-traceable tests written in-run and passing
- Findings triaged with the decision matrix
- **Spec-lagging drift fixed immediately**; **test gaps filled immediately**
- Minor issues auto-fixed; major issues created as GitHub Issues with scope/severity/recommendation
- No fixes applied during audit (except minor auto-fix, spec-lagging catch-up, test-gap filling)
- Existing issues checked for duplicates; GitHub Issues updated
- Changes committed; pushed (if requested or full audit); visual report delivered

---

## Downstream Skills

| Skill | When it follows |
|-------|-----------------|
| `issue-writing` | File issues for missing implementation / contract violations |
| `code-refactoring` | Fix code-side findings when the spec is authoritative |
| `feature-building` | Implement spec'd-but-missing functionality |
| `pest-testing` | Write the spec-traceable tests the audit's Test-Gap Fill writes |
| `sync-docs` | Propagate corrected contracts into module/docs surface |

## Verification / Detection

- Full pipeline executed in order for the scoped surface.
- Report delivered with Executive Summary, sync-status, drift analysis, and recommendations.
- The audit's own checklist (above) is fully ticked — this skill audits itself the same way it
  audits specs.