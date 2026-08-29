# GitHub Issues Output — Findings to Tracked Issues

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Phase 6 turns the audit into tracked work. Every finding becomes a structured GitHub Issue
(`[QA] ...` title, severity + category labels, evidence-backed body), findings are deduplicated and
severity-assigned first, the skill changes are committed, and the user receives a consolidated
report. This rule governs that output pipeline — consolidation, issue creation, commit, and report.

## Rationale

An audit that ends with a text dump is an audit that ends. Issues are the *tracking* mechanism:
- **Structured issues are triageable** — title, labels, severity, and evidence let the team assign
  work without re-reading the code.
- **Deduplication prevents noise** — the same XSS may surface in Phase 2 (security) and Phase 3
  (input validation). Filing both buries the real signal; keeping the highest-severity instance with
  an overlap note preserves it.
- **Independent filing preserves perspective** — a QA finding that overlaps an `arch-guard` finding
  is still filed independently, because the QA framing (global standard) differs from the internal
  framing (project invariant).
- **The report closes the loop** — an audit without a summary to the user leaves the outcome
  uncaptured; a blocked `gh` CLI must not silently drop the findings.

## How to Apply — Phase 6

### 6.1 Consolidate findings

1. **Deduplicate** — if the same issue appears in multiple phases, keep the highest-severity
   instance and note the other phases where it appeared.
2. **Cross-reference with `arch-guard`** — check whether QA findings overlap internal audit findings.
   Note overlaps but file independently — the QA perspective may differ.
3. **Assign final severity** using CVSS mapping (`rules/compliance-scorecard.md` §CVSS).

### 6.2 Create GitHub Issues

Per finding, using the `issue-writing` skill template format:

- **Title:** `[QA] {severity_emoji} {concise_title}`
- **Labels:** `qa-audit`, `security` (if applicable), and a severity label
  (`critical`/`high`/`medium`/`low`)
- **Body:**
  - Summary
  - Affected standard (OWASP A01, CWE-79, PSR-12, WCAG 2.1.1, etc.)
  - Evidence (file path, line number, code snippet)
  - Recommended fix direction (not implementation)
  - Overlap note (if also found by `arch-guard`)

**Batch creation:** use `gh issue create` per finding. If many findings, group related ones into a
single issue (e.g. "All XSS findings in the handbook module").

### 6.3 Commit skill changes

Commit the skill file and any rules files created/updated during the session. Verify with git first:

```bash
git status
git diff
git add .agents/skills/qa-protocol/
git commit -m "docs(qa-protocol): add QA protocol skill with rules files"
```

### 6.4 User report

Deliver the final summary — phases executed 6/6, total findings by severity, issues created,
overlaps, and the compliance scorecard (`rules/compliance-scorecard.md`).

## Examples

```bash
gh issue create \
  --title "[QA] 🔴 Unescaped user content rendered in handbook view" \
  --label "qa-audit,security,critical" \
  --body "Summary: ...\nStandard: OWASP A03 / CWE-79\nEvidence: file:line\nFix direction: escape or sanitize\nOverlap: arch-guard XSS scan, filed independently"
```

Consolidation rule in practice: `{!! !!}` XSS found in Phase 2 (security) and again in Phase 3
(validation) → one issue at the Phase-2 severity, body noting it also appeared in Phase 3.

## Anti-Patterns & Pitfalls

- **Duplicating the same finding across phases** — consolidate first; note other phases in the body.
- **Issues without evidence** — a body with no file:line cannot be triaged or verified after the fix.
- **Filing `arch-guard`-only findings unchanged** — a QA issue must cite the *global* standard, not
  an internal invariant; if it is purely internal, it belongs only in `arch-guard`.
- **Skipping the commit or the report when `gh` fails** — the blocker table says: if `gh` is not
  authenticated, report findings directly and skip issue creation; never lose the findings.
- **Recording an empty audit as "nothing happened"** — a clean audit still produces the report and
  commit (per the Phase 6 blocker: "No findings… still create the report and commit").

## Verification & Detection

- Every finding in the consolidated list has one GitHub Issue (or an explicit reason it was grouped
  into an existing issue).
- The final report shows: phases 6/6, findings by severity, issues created, arch-guard overlaps, and
  commit SHA (if committed).
- Skills/rules changes from the session are committed and visible in `git log`.