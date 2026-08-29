# Triage & Decision Matrix — Classifying Findings and Choosing the Fix Side

Every finding from Phase 2 must be classified (drift direction, root cause, authoritative side,
severity) and then resolved through the decision matrix. The matrix is what turns "spec and code
disagree" into a concrete, defensible action. Skipping triage means fixing the wrong side — which
*worsens* drift.

---

## Phase 3.1 — Classify Each Finding

For each finding, determine:

1. **Drift direction** — Spec→Code, Code→Spec, or Both
2. **Root cause** — which side changed first?
3. **Correct source of truth** — which side should be updated?
4. **Severity** — Critical / High / Medium / Low

**Why this order:** direction → root cause → source of truth → severity. The severity is *assigned*,
not discovered first; a finding's severity without a direction cannot be argued in an issue body.

---

## Phase 3.2 — Decision Matrix

| Drift Direction | Evidence | Resolution |
|----------------|----------|------------|
| Spec→Code (missing impl) | Spec exists, code doesn't | **Create GitHub Issue** — spec is ahead of code |
| Code→Spec (unspecified) | Code exists, spec doesn't | **Update spec immediately** — code is ahead of spec, spec lags |
| Contract mismatch (spec older) | Git log shows code changed after spec | **Update spec immediately** to match code |
| Contract mismatch (code older) | Git log shows spec changed after code | **Update code** to match spec (or update spec if behavior is intentional) |
| Broken cross-ref | Wrong ID/name in spec | **Fix spec** — trivial fix |
| Missing test | Code exists, no test | **Write tests immediately** — Test-Gap Fill Rule (spec-traceable, per `pest-testing`) |
| Failing test | Spec'd component test fails | **Fix test immediately** — align test to spec (or fix code if spec is authoritative) |
| Spec incomplete | Section missing/empty | **Update spec** — fill gap from code |
| FR not implemented | Spec FR has no code | **Create GitHub Issue** — track as TODO |
| Guide lags spec | Guide documents old value/section, spec amended | **Fix guide immediately** — align guide to spec |

**How to read the matrix:** the resolution is chosen by *which side is authoritative*, established by
the evidence — never by convenience. Two rows hinge on git history: "Contract mismatch (spec older)"
vs "(code older)". That's why Phase 1 discovery records the git context: without it you cannot
decide the authoritative side of a contract mismatch.

**Why "fix immediately" vs "create issue":** immediate fixes (spec lagging, test gaps, broken
cross-refs, guides) are low-risk, high-confidence, and their delay compounds (a stale spec misleads
the next implementer). Issues are for work that changes behavior or rewrites scope — missing
implementations and contract violations.

---

## Phase 3.3 — Severity Classification

| Severity | Criteria |
|----------|----------|
| **Critical** | Spec and code fundamentally disagree on behavior; data integrity risk |
| **High** | Missing implementation for a spec'd FR; broken cross-reference chain |
| **Medium** | Contract signature mismatch; missing test for critical Action |
| **Low** | Stale metadata; minor path typo; missing NFR documentation |

**Why this scale exists:** severity drives triage order and whether a finding blocks the audit's
"spec fully synced" verdict. A spec with a Critical finding is ❌, High ⚠️, Low ✅ in the sync-status
table.

**How to apply:** map the finding to the row, then sanity-check the reachable impact. A `C-1`
signature mismatch on a core login Action is Medium-High; the same mismatch on a rarely-used report
utility is Medium. Severity must be defensible in the issue body.

---

## Anti-Patterns / Pitfalls

- **No direction recorded** — a finding without Spec→Code/Code→Spec cannot use the matrix and gets
  "fixed" by vibes.
- **Skipping git history on contract mismatches** — the "(spec older)/(code older)" rows are
  undecidable without it; guessing picks the wrong side.
- **Auto-fixing without the auto-fix criteria** — see `fix-or-issue.md`: only trivial, no-behavior,
  high-confidence, low-risk fixes are auto-fixed.
- **Filing spec-lagging drift as an issue** — a spec behind the implementation is fixed in-run by the
  Spec-Lagging Fix Rule; issuing it defers the exact catch-up the audit must do.
- **Severity inflation/deflection** — a Critical "fundamental behavior disagreement" downgraded to Low
  because the code "looks fine" hides the data-integrity risk.

---

## Verification / Detection

- Every finding classified with drift direction + root cause + authoritative side + severity.
- Every finding mapped to exactly one decision-matrix row (or explicitly deferred with reason).
- The report lists each finding's decision-matrix outcome (see report structure in `audit-workflow.md`).
- Git history consulted for every contract mismatch; the chosen side is justified in the report.
